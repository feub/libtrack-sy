import { Navigate, Outlet } from "react-router";
import { useAuth } from "../hooks/useAuth";

export function ProtectedRoute() {
  const { user } = useAuth();

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return <Outlet />;
}
