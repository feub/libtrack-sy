import { toast } from "react-hot-toast";

interface ApiResponse {
  type: string;
  message?: string;
  data?: any;
}

export const handleApiError = (
  error: unknown,
  context: string = "API operation",
): never => {
  let errorMessage = "An unexpected error occurred";

  if (error instanceof Response) {
    errorMessage = `${context} failed with status: ${error.status}`;
  } else if (error instanceof Error) {
    errorMessage = error.message;
  } else if (typeof error === "string") {
    errorMessage = error;
  } else if (error && typeof error === "object" && "message" in error) {
    errorMessage = String((error as any).message);
  }

  toast.error(`${context}: ${errorMessage}`);
  throw new Error(errorMessage);
};

export const validateApiResponse = async (
  response: Response,
  context: string,
): Promise<ApiResponse> => {
  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    const errorMessage =
      errorData.message || `${context} failed with status: ${response.status}`;
    toast.error(errorMessage);
    throw new Error(errorMessage);
  }

  const data = await response.json();

  if (data.type !== "success") {
    const errorMessage =
      data.message || `Problem with ${context.toLowerCase()}`;
    toast.error(errorMessage);
    throw new Error(errorMessage);
  }

  return data;
};
