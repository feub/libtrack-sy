import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { createBrowserRouter, RouterProvider } from "react-router";
import { ThemeProvider } from "@/context/ThemeProvider.tsx";
import { AuthProvider } from "@/context/AuthProvider";
import { ProtectedRoute } from "@/components/ProtectedRoute.tsx";
import Layout from "@/Layout.tsx";
import ErrorPage from "@/pages/ErrorPage.tsx";
import ReleasePage from "@/pages/ReleasePage.tsx";
import ArtistPage from "@/pages/ArtistPage.tsx";
import GenrePage from "@/pages/GenrePage.tsx";
import LoginPage from "@/pages/LoginPage.tsx";
import AddByBarcodePage from "@/pages/AddByBarcodePage.tsx";
import ReleaseForm from "@/pages/ReleaseForm.tsx";
import ArtistForm from "@/pages/ArtistForm.tsx";
import StatsPage from "@/pages/StatsPage.tsx";
import "./index.css";
import GenreForm from "./pages/GenreForm";

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
            element: <StatsPage />,
          },
          {
            path: "release",
            children: [
              {
                index: true,
                element: <ReleasePage />,
              },
              {
                path: "create",
                element: <ReleaseForm mode="create" />,
              },
              {
                path: "edit/:id",
                element: <ReleaseForm mode="update" />,
              },
              {
                path: "scan",
                element: <AddByBarcodePage />,
              },
            ],
          },
          {
            path: "artist",
            children: [
              {
                index: true,
                element: <ArtistPage />,
              },
              {
                path: "create",
                element: <ArtistForm mode="create" />,
              },
              {
                path: "edit/:id",
                element: <ArtistForm mode="update" />,
              },
            ],
          },
          {
            path: "genre",
            children: [
              {
                index: true,
                element: <GenrePage />,
              },
              {
                path: "create",
                element: <GenreForm mode="create" />,
              },
              {
                path: "edit/:id",
                element: <GenreForm mode="update" />,
              },
            ],
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
