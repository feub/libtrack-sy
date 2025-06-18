import { Outlet } from "react-router";
import { Toaster } from "react-hot-toast";
import { SidebarProvider, SidebarInset } from "@/components/ui/sidebar";
import { AppSidebar } from "@/components/sidebar/AppSidebar";
import { SiteHeader } from "@/components/SiteHeader";
import FooterApiVersion from "@/components/FooterApiVersion";

const version = import.meta.env.PACKAGE_VERSION;

function Layout() {
  return (
    <SidebarProvider>
      <AppSidebar />
      <SidebarInset>
        <div className="flex flex-col h-full">
          <SiteHeader />
          <div className="flex overflow-auto">
            <div className="px-4 lg:gap-2 lg:p-6 w-full">
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
          <footer className="border-t border-border rounded-b-xl bg-background py-2 px-4 mt-auto">
            <div className="container flex items-center justify-between">
              <p className="text-sm text-muted-foreground">
                © {new Date().getFullYear()} LibTrack
              </p>
              <p className="text-sm text-muted-foreground">
                Version {version} - <FooterApiVersion />
              </p>
            </div>
          </footer>
        </div>
      </SidebarInset>
    </SidebarProvider>
  );
}

export default Layout;
