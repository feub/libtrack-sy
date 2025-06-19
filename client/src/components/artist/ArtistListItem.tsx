import { useState } from "react";
import { Link } from "react-router";
import { ArtistType } from "@/types/releaseTypes";
import { TableRow, TableCell } from "@/components/ui/table";
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
const imagePath = import.meta.env.VITE_IMAGES_PATH + "/artists/";

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
              src={`${apiURL}${imagePath}${artist.thumbnail}`}
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
            <FilePenLine className="h-[16px] w-[16px]" />
          </Link>
          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger className="text-neutral-600 hover:text-red-600">
              <CircleX className="h-[16px] w-[16px]" />
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
                  onClick={() => setOpen(false)}
                  className="text-neutral-600 hover:text-neutral-400"
                >
                  Cancel
                </Button>
                <Button
                  variant="destructive"
                  onClick={() => handleDeleteAndClose(artist.id)}
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
