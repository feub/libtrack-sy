import { useState, ReactNode } from "react";
import { AuthContext, User } from "./AuthContext";

const apiURL = import.meta.env.VITE_API_URL;

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const currentToken = localStorage.getItem("access_token") || null;
  const currentRefreshToken = localStorage.getItem("refresh_token") || null;
  const currentUser = localStorage.getItem("user")
    ? JSON.parse(localStorage.getItem("user") || "")
    : null;

  const [token, setToken] = useState<string | null>(currentToken);
  const [refreshToken, setRefreshToken] = useState<string | null>(
    currentRefreshToken,
  );
  const [user, setUser] = useState<User | null>(currentUser);

  const loginUser = async (email: string, password: string) => {
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

    setToken(data.token);
    setRefreshToken(data.refresh_token);
    setUser({ email: data.user });
    localStorage.setItem("access_token", data.token);
    localStorage.setItem("refresh_token", data.refresh_token);
    localStorage.setItem("user", JSON.stringify({ email: data.user }));

    console.log("AuthProvider ~ login ~ refresh_token", data.refresh_token);
  };

  const logoutUser = () => {
    setToken(null);
    setRefreshToken(null);
    setUser(null);
    localStorage.removeItem("access_token");
    localStorage.removeItem("refresh_token");
    localStorage.removeItem("user");
  };

  const contextData = {
    user: user,
    token: token,
    loginUser: loginUser,
    logoutUser: logoutUser,
  };

  return (
    <AuthContext.Provider value={contextData}>{children}</AuthContext.Provider>
  );
};
