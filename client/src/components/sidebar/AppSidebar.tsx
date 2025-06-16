import { Link } from "react-router";
import { useAuth } from "@/hooks/useAuth";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar";
import {
  Music,
  ScanBarcode,
  PersonStanding,
  Settings,
  ChevronUp,
  User2,
  LogOut,
  ChartPie,
  ListMusic,
} from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { ModeToggle } from "@/components/ModeToggle";

const items = [
  {
    title: "Statistics",
    url: "/",
    icon: ChartPie,
  },
  {
    title: "Releases",
    url: "/release",
    icon: Music,
  },
  {
    title: "Music Service Search",
    url: "/release/music-service-search",
    icon: ScanBarcode,
  },
  {
    title: "Artists",
    url: "/artist",
    icon: PersonStanding,
  },
  {
    title: "Genres",
    url: "/genre",
    icon: ListMusic,
  },
];

export function AppSidebar() {
  const { user, logoutUser } = useAuth();

  const handleLogout = () => {
    logoutUser();
  };

  return (
    <Sidebar variant="inset">
      <SidebarHeader />
      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupLabel>LibTrack</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              {items.map((item) => (
                <SidebarMenuItem key={item.title}>
                  <SidebarMenuButton asChild>
                    <Link to={item.url}>
                      <item.icon />
                      <span>{item.title}</span>
                    </Link>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
        <SidebarGroup />
      </SidebarContent>
      <SidebarFooter>
        <SidebarMenu>
          <SidebarMenuItem>
            <DropdownMenu>
              <DropdownMenuTrigger>
                <div className="flex items-center gap-2 px-3 py-2 rounded-md hover:bg-accent">
                  <User2 />
                  {user?.email}
                  <ChevronUp className="ml-auto" />
                </div>
              </DropdownMenuTrigger>
              <DropdownMenuContent
                side="top"
                className="w-[--radix-popper-anchor-width]"
              >
                <ModeToggle />
                <DropdownMenuItem>
                  <Settings />
                  <span>Settings</span>
                </DropdownMenuItem>
                <DropdownMenuItem onClick={handleLogout}>
                  <LogOut />
                  <span>Sign out</span>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarFooter>
    </Sidebar>
  );
}
