import { Outlet } from "react-router";
import { SidebarProvider, SidebarInset } from "./components/ui/sidebar";
import { Toaster } from "react-hot-toast";
import { AppSidebar } from "./components/sidebar/AppSidebar";
import { SiteHeader } from "./components/SiteHeader";

function Layout() {
  return (
    <SidebarProvider>
      <AppSidebar />
      <SidebarInset>
        <SiteHeader />
        <div className="flex flex-1 flex-col">
          <div className="px-4 lg:gap-2 lg:p-6">
            <Outlet />
            <Toaster
              toastOptions={{
                style: {
                  width: "auto",
                  minWidth: "200px",
                  maxWidth: "80vw",
                },
                success: {
                  style: {
                    backgroundColor: "#15803d",
                    border: "1px solid #15803d",
                    padding: "16px",
                    color: "#dcfce7",
                  },
                },
                error: {
                  style: {
                    backgroundColor: "#b91c1c",
                    border: "1px solid #b91c1c",
                    padding: "16px",
                    color: "#fee2e2",
                  },
                },
              }}
            />
          </div>
        </div>
      </SidebarInset>
    </SidebarProvider>
  );
}

export default Layout;
