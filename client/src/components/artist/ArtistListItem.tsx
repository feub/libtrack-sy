import { Link } from "react-router";
import { ArtistType } from "../../types/releaseTypes";
import { TableRow, TableCell } from "../ui/table";
import { Button } from "@/components/ui/button";
import { CircleX, FilePenLine } from "lucide-react";

const apiURL = import.meta.env.VITE_API_URL;
const coverPath = import.meta.env.VITE_COVER_PATH || "/covers/";

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
        <TableCell className="text-neutral-600 italic">{artist.slug}</TableCell>
        <TableCell className="w-[100px] h-[100px] text-center">
          {artist.thumbnail ? (
            <img
              src={`${apiURL}${coverPath}${artist.thumbnail}`}
              alt={artist.name}
              className="rounded-md"
            />
          ) : (
            <div className="text-neutral-700 w-[100px] h-[100px] bg-neutral-900 justify-center items-center flex rounded-md">
              No image
            </div>
          )}
        </TableCell>
        <TableCell className="text-right">
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
