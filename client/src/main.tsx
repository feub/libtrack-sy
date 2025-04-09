import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { createBrowserRouter, RouterProvider } from "react-router";
import { ThemeProvider } from "./components/theme-provider";
import { AuthProvider } from "./context/AuthProvider";
import Layout from "./Layout.tsx";
import ErrorPage from "./pages/ErrorPage.tsx";
import ReleasePage from "./pages/ReleasePage.tsx";
import ArtistPage from "./pages/ArtistPage.tsx";
import LoginPage from "./pages/LoginPage.tsx";
import "./index.css";
import { ProtectedRoute } from "./components/ProtectedRoute.tsx";

const router = createBrowserRouter([
  {
    path: "/login",
    element: <LoginPage />,
    errorElement: <ErrorPage />,
  },
  {
    element: <ProtectedRoute />,
    children: [
      {
        path: "/",
        element: <Layout />,
        errorElement: <ErrorPage />,
        children: [
          {
            index: true,
            element: <ReleasePage />,
          },
          {
            path: "release",
            element: <ReleasePage />,
          },
          {
            path: "artist",
            element: <ArtistPage />,
          },
        ],
      },
    ],
  },
]);

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <ThemeProvider defaultTheme="dark" storageKey="vite-ui-theme">
      <AuthProvider>
        <RouterProvider router={router} />
      </AuthProvider>
    </ThemeProvider>
  </StrictMode>,
);
