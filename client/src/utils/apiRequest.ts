const apiURL = import.meta.env.VITE_API_URL;

async function refreshAccessToken(): Promise<boolean> {
  const refreshToken = localStorage.getItem("refreshToken");
  if (!refreshToken) return false;

  try {
    const response = await fetch(`${apiURL}/api/token/refresh`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ refresh_token: refreshToken }),
    });

    if (!response.ok) {
      return false;
    }

    const data = await response.json();

    localStorage.setItem("token", data.token);
    localStorage.setItem("refreshToken", data.refresh_token);
    localStorage.setItem("tokenExpiresAt", data.token_expires_at);

    return true;
  } catch (error) {
    console.error("Token refresh error:", error);
    return false;
  }
}

export async function apiRequest(url: string, options: RequestInit = {}) {
  let token = localStorage.getItem("token");

  const headers: HeadersInit = {
    "Content-Type": "application/json",
    ...options.headers,
  };

  // Add authorization header if token exists
  if (token) {
    console.log("apiRequest ~ token exists, Add authorization header");

    (headers as Record<string, string>)["Authorization"] = `Bearer ${token}`;
  }

  let response = await fetch(url, {
    ...options,
    headers,
  });

  // Handle 401 unauthorized by attempting to refresh token first
  if (response.status === 401) {
    console.log("apiRequest ~ 401, attempting to refresh token");
    const refreshSuccessful = await refreshAccessToken();

    if (refreshSuccessful) {
      console.log("apiRequest ~ token refreshed");
      // If refresh was successful, update the token and retry the request
      token = localStorage.getItem("token");

      // Update authorization header with new token
      (headers as Record<string, string>)["Authorization"] = `Bearer ${token}`;

      // Retry the original request with the new token
      response = await fetch(url, {
        ...options,
        headers,
      });

      // If the retry is successful, return the response
      if (response.ok) {
        return response;
      }
    }

    // If we got here, either the refresh failed or the retry failed
    localStorage.removeItem("token");
    localStorage.removeItem("refreshToken");
    localStorage.removeItem("tokenExpiresAt");
    localStorage.removeItem("userEmail");

    window.location.href = "/login";
    throw new Error("Authentication failed");
  }

  return response;
}

// Helper methods for common HTTP operations
export const api = {
  get: (url: string) => apiRequest(url, { method: "GET" }),
  post: (url: string, data: Record<string, unknown>) =>
    apiRequest(url, {
      method: "POST",
      body: JSON.stringify(data),
    }),
  put: (url: string, data: Record<string, unknown>) =>
    apiRequest(url, {
      method: "PUT",
      body: JSON.stringify(data),
    }),
  delete: (url: string) => apiRequest(url, { method: "DELETE" }),
};
