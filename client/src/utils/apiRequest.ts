export async function apiRequest(url: string, options: RequestInit = {}) {
  const token = localStorage.getItem("token");

  const headers: HeadersInit = {
    "Content-Type": "application/json",
    ...options.headers,
  };

  // Add authorization header if token exists
  if (token) {
    (headers as Record<string, string>)["Authorization"] = `Bearer ${token}`;
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  // Handle 401 unauthorized by redirecting to login
  if (response.status === 401) {
    localStorage.removeItem("token");
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
