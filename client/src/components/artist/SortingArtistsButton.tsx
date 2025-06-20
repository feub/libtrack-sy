// import { useState } from "react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { ArrowUpDown } from "lucide-react";
import { useEffect, useState } from "react";

type SortingButtonProps = {
  currentSort: string;
  currentDirection: string;
  onSortChange: (sortBy: string, sortDirection: string) => void;
};
export default function SortingArtistsButton({
  currentSort,
  currentDirection,
  onSortChange,
}: SortingButtonProps) {
  const [position, setPosition] = useState<string>("nameasc");

  useEffect(() => {
    const initialValue = `${currentSort}${currentDirection}`;
    setPosition(initialValue);
  }, [currentSort, currentDirection]);

  const handleSortChange = (value: string) => {
    setPosition(value);

    switch (value) {
      case "nameasc":
        onSortChange("name", "asc");
        break;
      case "namedesc":
        onSortChange("name", "desc");
        break;
      case "createdAtasc":
        onSortChange("createdAt", "asc");
        break;
      case "createdAtdesc":
        onSortChange("createdAt", "desc");
        break;
      default:
        onSortChange("name", "asc");
    }
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger>
        <Button variant="outline" size="sm">
          <ArrowUpDown /> Sorting
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-56">
        <DropdownMenuRadioGroup
          value={position}
          onValueChange={handleSortChange}
        >
          <DropdownMenuRadioItem value="nameasc">
            By name ascending
          </DropdownMenuRadioItem>
          <DropdownMenuRadioItem value="namedesc">
            By name descending
          </DropdownMenuRadioItem>
          <DropdownMenuRadioItem value="createdAtasc">
            By date ascending
          </DropdownMenuRadioItem>
          <DropdownMenuRadioItem value="createdAtdesc">
            By date descending
          </DropdownMenuRadioItem>
        </DropdownMenuRadioGroup>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
