import { useState } from "react";
import { Link } from "react-router";
import { GenreType } from "@/types/releaseTypes";
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

export default function GenreListItem({
  genre,
  handleDelete,
}: {
  genre: GenreType;
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
        <TableCell className="font-medium">{genre.name}</TableCell>
        <TableCell className="text-neutral-600 italic">{genre.slug}</TableCell>
        <TableCell className="text-right">
          <Link
            to={`/artist/edit/${genre.id}`}
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
                  This will permanently delete the genre (unless at least one
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
                  onClick={() => handleDeleteAndClose(genre.id)}
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
