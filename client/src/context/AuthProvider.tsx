import { useState, ReactNode, useEffect, useCallback } from "react";
import { AuthContext, User } from "./AuthContext";

const apiURL = import.meta.env.VITE_API_URL;

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(
    localStorage.getItem("token"),
  );
  const [refreshToken, setRefreshToken] = useState<string | null>(
    localStorage.getItem("refreshToken"),
  );
  const [tokenExpiresAt, setTokenExpiresAt] = useState<Date | null>(
    localStorage.getItem("tokenExpiresAt")
      ? new Date(localStorage.getItem("tokenExpiresAt") || "")
      : null,
  );

  // Check if token exists on load
  useEffect(() => {
    if (token) {
      setUser({ email: localStorage.getItem("userEmail") || "" });
    }
  }, [token]);

  // Set up automatic token refresh
  useEffect(() => {
    if (!token || !refreshToken || !tokenExpiresAt) return;

    // Calculate when to refresh the token (1 minute before expiry)
    const refreshTime = new Date(tokenExpiresAt.getTime() - 60000);
    const now = new Date();

    // If token is already expired or will expire in less than a minute, refresh it
    if (refreshTime <= now) {
      refreshAccessToken();
      return;
    }

    // Schedule token refresh
    const timeUntilRefresh = refreshTime.getTime() - now.getTime();
    const refreshTimer = setTimeout(() => {
      refreshAccessToken();
    }, timeUntilRefresh);

    return () => clearTimeout(refreshTimer);
  }, [token, refreshToken, tokenExpiresAt]);

  const refreshAccessToken = useCallback(async () => {
    if (!refreshToken) return;

    try {
      const response = await fetch(`${apiURL}/api/token/refresh`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ refresh_token: refreshToken }),
      });

      if (!response.ok) {
        logout();
        return;
      }

      const data = await response.json();

      localStorage.setItem("token", data.token);
      localStorage.setItem("refreshToken", data.refresh_token);
      localStorage.setItem("tokenExpiresAt", data.token_expires_at);

      setToken(data.token);
      setRefreshToken(data.refresh_token);
      setTokenExpiresAt(new Date(data.token_expires_at));
    } catch (error) {
      console.error("Token refresh error:", error);
      logout();
    }
  }, [refreshToken]);

  const login = async (email: string, password: string) => {
    try {
      const response = await fetch(`${apiURL}/api/login`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ username: email, password }),
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(
          "ERROR (response): " + errorData.message || "Login failed",
        );
      }

      const data = await response.json();

      localStorage.setItem("token", data.token);
      localStorage.setItem("refreshToken", data.refresh_token);
      localStorage.setItem("tokenExpiresAt", data.token_expires_at);
      localStorage.setItem("userEmail", data.user);

      setToken(data.token);
      setRefreshToken(data.refresh_token);
      setTokenExpiresAt(new Date(data.token_expires_at));
      setUser({ email: data.user });
    } catch (error) {
      console.error("Login error:", error);
      throw "ERROR (T/C): " + error;
    }
  };

  const logout = async () => {
    try {
      // If we have a refresh token, try to invalidate it on the server
      if (refreshToken) {
        await fetch(`${apiURL}/api/logout`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({ refresh_token: refreshToken }),
        }).catch(() => {
          // Silently fail
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
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isAuthenticated: !!token,
        login,
        logout,
        refreshToken: refreshAccessToken,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};
