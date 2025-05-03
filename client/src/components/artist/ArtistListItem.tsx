import { useState } from "react";
import { Link } from "react-router";
import { ArtistType } from "../../types/releaseTypes";
import { TableRow, TableCell } from "../ui/table";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
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
  const [open, setOpen] = useState<boolean>(false);

  const handleDeleteAndClose = (id: number) => {
    handleDelete(id);
    setOpen(false);
  };

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
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger className="text-neutral-600 hover:text-red-600">
              <CircleX />
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Are you sure?</DialogTitle>
                <DialogDescription>
                  This will permanently delete the artist (unless at least one
                  release is attached to it).
                </DialogDescription>
              </DialogHeader>
              <DialogFooter>
                <Button
                  variant="ghost"
                  onClick={() => handleDeleteAndClose(artist.id)}
                  className="hover:bg-red-600 hover:text-red-200 text-red-600"
                >
                  <CircleX /> Delete
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </TableCell>
      </TableRow>
    </>
  );
}
