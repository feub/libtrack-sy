import { Toggle } from "@/components/ui/toggle";
import { ToggleRight } from "lucide-react";

type ToggleFeaturedReleasesProps = {
  pressed: boolean;
  onPressedChange: (pressed: boolean) => void;
};
export default function ToggleFeaturedReleases({
  pressed,
  onPressedChange,
}: ToggleFeaturedReleasesProps) {
  return (
    <Toggle
      variant="outline"
      aria-label="Toggle featured releases"
      pressed={pressed}
      onPressedChange={onPressedChange}
    >
      <ToggleRight className="h-4 w-4" />
    </Toggle>
  );
}
