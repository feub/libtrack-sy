import { useState, ReactNode, useEffect, useCallback, useRef } from "react";
import { AuthContext, User } from "./AuthContext";

const apiURL = import.meta.env.VITE_API_URL;
const REFRESH_BUFFER_MS = 60 * 1000; // Refresh 1 minute before expiry
const CLOCK_SKEW_TOLERANCE_MS = 5 * 60 * 1000; // 5 minute tolerance for clock differences

// Helper function to standardize date handling
const parseTokenExpiryDate = (dateString: string | null): Date | null => {
  if (!dateString) return null;

  try {
    const date = new Date(dateString);
    // Validate the date is valid
    if (isNaN(date.getTime())) return null;
    return date;
  } catch (e) {
    console.warn("Error parsing date:", e);
    return null;
  }
};

// Helper to log time information for debugging
const logTimeInfo = (message: string, date: Date | null) => {
  console.log(
    `${message} ` +
      `Local: ${new Date().toISOString()} (${new Date().toString()}), ` +
      `UTC now: ${new Date().toUTCString()}, ` +
      `Token date: ${date ? date.toISOString() : "none"}`,
  );
};

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(
    localStorage.getItem("token"),
  );
  const [refreshToken, setRefreshToken] = useState<string | null>(
    localStorage.getItem("refreshToken"),
  );
  const [tokenExpiresAt, setTokenExpiresAt] = useState<Date | null>(() => {
    const storedDate = localStorage.getItem("tokenExpiresAt");
    return parseTokenExpiryDate(storedDate);
  });

  // Use a ref to track if a refresh is currently in progress
  const isRefreshing = useRef<boolean>(false);
  // Use a ref for the timer ID to ensure cleanup uses the correct ID
  const refreshTimerId = useRef<NodeJS.Timeout | null>(null);

  const logout = useCallback(async (reason?: string) => {
    console.log(`Logging out (${reason || "user action"})...`);

    // Clear any pending refresh timer
    if (refreshTimerId.current) {
      clearTimeout(refreshTimerId.current);
      refreshTimerId.current = null;
    }
    isRefreshing.current = false; // Reset refresh flag

    const currentToken = localStorage.getItem("token");
    const currentRefreshToken = localStorage.getItem("refreshToken");

    try {
      if (currentRefreshToken) {
        // Attempt server-side invalidation, but don't block logout if it fails
        fetch(`${apiURL}/api/logout`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${currentToken}`,
          },
          body: JSON.stringify({ refresh_token: currentRefreshToken }),
        }).catch((error) => {
          console.warn(
            "Server-side logout failed (token might already be invalid):",
            error,
          );
        });
      }
    } finally {
      localStorage.removeItem("token");
      localStorage.removeItem("refreshToken");
      localStorage.removeItem("tokenExpiresAt");
      localStorage.removeItem("userEmail");

      setToken(null);
      setRefreshToken(null);
      setTokenExpiresAt(null);
      setUser(null);
      console.log("Client-side logout complete.");
    }
  }, []);

  const refreshAccessToken = useCallback(async () => {
    // Prevent multiple concurrent refresh attempts
    if (isRefreshing.current) {
      console.log("Refresh already in progress, skipping.");
      return;
    }

    const currentRefreshToken = localStorage.getItem("refreshToken");
    if (!currentRefreshToken) {
      console.log("No refresh token available, logging out.");
      await logout("no refresh token");
      return;
    }

    isRefreshing.current = true;
    console.log("Attempting token refresh...");

    try {
      const response = await fetch(`${apiURL}/api/token/refresh`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ refresh_token: currentRefreshToken }),
      });

      if (!response.ok) {
        console.error(
          `Token refresh failed (${response.status}), logging out.`,
        );
        await logout(`refresh failed ${response.status}`);
        return;
      }

      const data = await response.json();

      // Debug the actual value received from the server
      console.log("Received token_expires_at:", data.token_expires_at);

      // Improved date parsing with fallback mechanism
      let newExpiryDate = parseTokenExpiryDate(data.token_expires_at);

      if (!newExpiryDate) {
        console.warn("Using fallback expiry calculation");
        newExpiryDate = new Date(Date.now() + 55 * 60 * 1000); // 55 minutes
        data.token_expires_at = newExpiryDate.toISOString();
      }

      // If we couldn't get a valid date, use a fallback
      if (!newExpiryDate || isNaN(newExpiryDate.getTime())) {
        // Fallback: Use current time plus a reasonable interval (e.g., 55 minutes)
        // JWT tokens are typically valid for 1 hour, so we make it slightly less
        console.warn("Using fallback expiry calculation");
        newExpiryDate = new Date(Date.now() + 55 * 60 * 1000); // 55 minutes

        // Update stored value to match our calculated one
        data.token_expires_at = newExpiryDate.toISOString();
      }

      // Check if the new token is already expired or expires immediately
      if (newExpiryDate.getTime() - REFRESH_BUFFER_MS <= Date.now()) {
        console.warn(
          "New token expires too soon, potential server issue or clock skew. Logging out.",
        );
        // Instead of logging out, use the fallback again
        newExpiryDate = new Date(Date.now() + 55 * 60 * 1000);
        data.token_expires_at = newExpiryDate.toISOString();
      }

      console.log(
        `Token refresh successful. New expiry: ${newExpiryDate.toISOString()}`,
      );

      localStorage.setItem("token", data.token);
      localStorage.setItem("refreshToken", data.refresh_token);
      localStorage.setItem("tokenExpiresAt", data.token_expires_at); // Store as string

      setToken(data.token);
      setRefreshToken(data.refresh_token);
      setTokenExpiresAt(newExpiryDate);
    } catch (error) {
      console.error("Error during token refresh:", error);
      await logout("refresh exception");
    } finally {
      // Always reset the flag when the attempt finishes (success or error)
      isRefreshing.current = false;
    }
  }, [logout]);

  // Effect to initialize user state from token
  useEffect(() => {
    if (token) {
      const email = localStorage.getItem("userEmail");
      setUser(email ? { email } : null); // Ensure user is null if email is missing
    } else {
      setUser(null);
    }
  }, [token]);

  // Effect for scheduling the token refresh
  useEffect(() => {
    // Clear any existing timer on re-run or unmount
    if (refreshTimerId.current) {
      clearTimeout(refreshTimerId.current);
      refreshTimerId.current = null;
    }

    // Only schedule if we have the necessary info and a valid date
    if (
      !token ||
      !refreshToken ||
      !tokenExpiresAt ||
      isNaN(tokenExpiresAt.getTime())
    ) {
      console.log(
        "Refresh scheduler: Missing token info or invalid expiry date.",
      );
      return;
    }

    const now = Date.now();
    logTimeInfo("Refresh scheduler:", tokenExpiresAt);

    const expiryTime = tokenExpiresAt.getTime();
    // Add skew tolerance to avoid premature logout if clocks are slightly different
    const effectiveExpiryTime = expiryTime + CLOCK_SKEW_TOLERANCE_MS;
    const timeUntilRefresh = effectiveExpiryTime - now - REFRESH_BUFFER_MS;

    console.log(
      `Refresh scheduler: Now=${new Date(
        now,
      ).toISOString()}, Expiry=${tokenExpiresAt.toISOString()}, ` +
        `Time until refresh: ${Math.round(
          timeUntilRefresh / 1000,
        )}s (includes ${CLOCK_SKEW_TOLERANCE_MS / 1000}s tolerance)`,
    );

    // If the token is already expired or needs immediate refresh
    if (timeUntilRefresh <= 0) {
      console.log("Refresh scheduler: Token requires immediate refresh.");
      // Use setTimeout with 0 delay to avoid deep recursion if refresh fails immediately
      refreshTimerId.current = setTimeout(() => {
        refreshAccessToken();
        refreshTimerId.current = null; // Clear ref after execution
      }, 100);
    } else {
      console.log(
        `Refresh scheduler: Scheduling refresh in ${Math.round(
          timeUntilRefresh / 1000,
        )}s.`,
      );
      refreshTimerId.current = setTimeout(() => {
        console.log("Refresh scheduler: Timer triggered.");
        refreshAccessToken();
        refreshTimerId.current = null; // Clear ref after execution
      }, timeUntilRefresh);
    }

    // Cleanup function to clear the timer when the effect re-runs or component unmounts
    return () => {
      if (refreshTimerId.current) {
        console.log(
          "Refresh scheduler: Clearing timer ID:",
          refreshTimerId.current,
        );
        clearTimeout(refreshTimerId.current);
        refreshTimerId.current = null;
      }
    };
    // Re-run this effect if the token info changes, or if the stable refresh function reference changes (it shouldn't often)
  }, [token, refreshToken, tokenExpiresAt, refreshAccessToken]);

  const login = useCallback(
    async (email: string, password: string) => {
      // Clear any potential old state before attempting login
      await logout("new login attempt");

      console.log("Attempting login...");
      try {
        const response = await fetch(`${apiURL}/api/login`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ username: email, password }),
        });

        if (!response.ok) {
          let errorMsg = "Login failed";
          try {
            const errorData = await response.json();
            errorMsg = errorData.message || `Login failed (${response.status})`;
          } catch {
            errorMsg = `Login failed (${response.status})`;
          }
          throw new Error(errorMsg);
        }

        const data = await response.json();
        const newExpiryDate = parseTokenExpiryDate(data.token_expires_at);

        if (!newExpiryDate) {
          console.warn("Using fallback expiry calculation during login");
          // Fallback: Use current time plus a reasonable interval
          const fallbackExpiry = new Date(Date.now() + 55 * 60 * 1000); // 55 minutes
          data.token_expires_at = fallbackExpiry.toISOString();
          setTokenExpiresAt(fallbackExpiry);
        } else {
          logTimeInfo("Login success with expiry:", newExpiryDate);
          setTokenExpiresAt(newExpiryDate);
        }

        console.log(
          `Login successful. Token expires at: ${
            newExpiryDate ? newExpiryDate.toISOString() : "unknown"
          }`,
        );

        localStorage.setItem("token", data.token);
        localStorage.setItem("refreshToken", data.refresh_token);
        localStorage.setItem("tokenExpiresAt", data.token_expires_at); // Store as string
        localStorage.setItem("userEmail", data.user);

        setToken(data.token);
        setRefreshToken(data.refresh_token);
        setTokenExpiresAt(newExpiryDate); // Store as Date object
        setUser({ email: data.user });
      } catch (error) {
        console.error("Login error:", error);
        // Ensure cleanup happens even if login fails after the initial logout call
        await logout("login failed");
        // Re-throw the error so the UI can handle it
        throw error instanceof Error ? error : new Error(String(error));
      }
    },
    [logout],
  );

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isAuthenticated: !!token,
        login,
        logout: () => logout("user action"), // Wrap logout to provide a default reason
        refreshToken: refreshAccessToken, // Expose manual refresh if needed
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};
