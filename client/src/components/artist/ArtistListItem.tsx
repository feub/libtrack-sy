import { Link } from "react-router";
import { ArtistType } from "../../types/releaseTypes";
import { TableRow, TableCell } from "../ui/table";
import { Button } from "@/components/ui/button";
import { CircleX, FilePenLine } from "lucide-react";

export default function ArtistListItem({
  artist,
  handleDelete,
}: {
  artist: ArtistType;
  handleDelete: (id: number) => void;
}) {
  return (
    <>
      <TableRow>
        <TableCell className="font-medium">{artist.name}</TableCell>
        <TableCell>
          <Link
            to={`/artist/edit/${artist.id}`}
            className="inline-flex h-10 items-center justify-center rounded-md px-4 py-2 text-neutral-600 hover:text-neutral-400"
          >
            <FilePenLine className="h-4 w-4" />
          </Link>
          <Button
            variant="ghost"
            onClick={() => handleDelete(artist.id)}
            className="text-neutral-600 hover:text-red-600"
          >
            <CircleX />
          </Button>
        </TableCell>
      </TableRow>
    </>
  );
}
