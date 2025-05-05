const apiURL = import.meta.env.VITE_API_URL;

// Track refresh promise to prevent multiple simultaneous refreshes
let refreshingPromise: Promise<boolean> | null = null;

async function refreshAccessToken(): Promise<boolean> {
  //   The idea here is that:
  // // If multiple API requests fail with 401 simultaneously, only the first one will trigger an actual token refresh
  // All subsequent requests will wait for that same refresh operation to complete
  // Once the refresh is done, all queued requests will continue with the new token
  // If there's already a refresh in progress, return that promise
  if (refreshingPromise) {
    return refreshingPromise;
  }

  const refreshToken = localStorage.getItem("refresh_token");
  if (!refreshToken) return false;

  // Create a new refresh promise
  refreshingPromise = (async () => {
    try {
      const response = await fetch(`${apiURL}/api/token/refresh`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ refresh_token: refreshToken }),
      });

      if (!response.ok) {
        let errorMsg = "Refresh failed";
        try {
          const errorData = await response.json();
          errorMsg = errorData.message || `Refresh failed (${response.status})`;
        } catch {
          errorMsg = `Refresh failed (${response.status})`;
        }
        throw new Error(errorMsg);
      }

      const data = await response.json();

      localStorage.setItem("access_token", data.token);
      localStorage.setItem("refresh_token", data.refresh_token);

      return true;
    } catch (error) {
      console.error("Token refresh error:", error);
      return false;
    } finally {
      // Clear the refresh promise
      refreshingPromise = null;
    }
  })();

  return refreshingPromise;
}

export async function apiRequest(url: string, options: RequestInit = {}) {
  let token = localStorage.getItem("access_token");

  const headers: HeadersInit = {
    "Content-Type": "application/json",
    ...options.headers,
  };

  // Add authorization header if token exists
  if (token) {
    (headers as Record<string, string>)["Authorization"] = `Bearer ${token}`;
  }

  let response = await fetch(url, {
    ...options,
    headers,
  });

  // Handle 401 unauthorized by attempting to refresh token first
  if (response.status === 401) {
    const refreshSuccessful = await refreshAccessToken();

    if (refreshSuccessful) {
      console.log("apiRequest.ts ~ token refreshed");
      // If refresh was successful, update the token and retry the request
      token = localStorage.getItem("access_token");

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
    localStorage.removeItem("access_token");
    localStorage.removeItem("refresh_token");
    localStorage.removeItem("user");

    console.log("apiRequest.ts ~ apiRequest ~ redirect to login");

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
