import { useState, ReactNode, useEffect } from "react";
import { AuthContext, User } from "./AuthContext";

const apiURL = import.meta.env.VITE_API_URL;

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(
    localStorage.getItem("token"),
  );

  // Check if token exists on load
  useEffect(() => {
    if (token) {
      setUser({ email: localStorage.getItem("userEmail") || "" });
    }
  }, [token]);

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
      localStorage.setItem("userEmail", data.user);

      setToken(data.token);
      setUser({ email: data.user });
    } catch (error) {
      console.error("Login error:", error);
      throw "ERROR (T/C): " + error;
    }
  };

  const logout = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("userEmail");
    setToken(null);
    setUser(null);
  };

  return (
    <AuthContext.Provider
      value={{ user, token, isAuthenticated: !!token, login, logout }}
    >
      {children}
    </AuthContext.Provider>
  );
};
