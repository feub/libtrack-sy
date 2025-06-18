import { useTheme } from "@/hooks/useTheme";
import { DropdownMenuItem } from "@/components/ui/dropdown-menu";
import { SunMoon } from "lucide-react";

export function ModeToggle() {
  const { theme, setTheme } = useTheme();

  const switchTheme = () => {
    if (theme === "dark") {
      setTheme("light");
    } else {
      setTheme("dark");
    }
  };

  return (
    <>
      <DropdownMenuItem onClick={switchTheme}>
        <SunMoon /> Switch theme
      </DropdownMenuItem>
    </>
  );
}
