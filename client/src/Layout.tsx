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
                className: "",
                style: {
                  backgroundColor: "#151515",
                  border: "1px solid #CFCFCF",
                  padding: "16px",
                  color: "#CFCFCF",
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
