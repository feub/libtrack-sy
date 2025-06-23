import { useState } from "react";
import { Link, useLocation } from "react-router";
import { ListReleasesType } from "@/types/releaseTypes";
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
import { Disc, Disc3, CircleX, FilePenLine } from "lucide-react";
import NoCover from "@/components/release/NoCover";

const apiURL = import.meta.env.VITE_API_URL;
const coverPath = import.meta.env.VITE_IMAGES_PATH + "/covers/";

export default function ReleaseListItem({
  release,
  handleDelete,
}: {
  release: ListReleasesType;
  handleDelete: (id: number) => void;
}) {
  const location = useLocation();
  const [open, setOpen] = useState<boolean>(false);

  const handleDeleteAndClose = (id: number) => {
    handleDelete(id);
    setOpen(false);
  };

  return (
    <>
      <TableRow key={release.id}>
        <TableCell>
          {release.cover ? (
            <img
              src={`${apiURL}${coverPath}${release.cover}`}
              alt={release.title}
              className="rounded-md"
            />
          ) : (
            <NoCover
              id={release.id}
              title={release.title as string}
              artist={release.artists?.[0]?.name as string}
            />
          )}
        </TableCell>
        <TableCell className="font-medium">
          {release.title}
          {release.shelf && (
            <>
              <br />
              <span className="text-neutral-500">
                Shelf: {release.shelf.location}
              </span>
            </>
          )}
        </TableCell>
        <TableCell className="max-w-[200px] truncate whitespace-normal break-words">
          {release.artists &&
            release.artists.map((artist) => {
              return (
                <Link
                  key={artist.id}
                  to={`/release?search=${artist.name}`}
                  className="font-bold"
                >
                  {artist.name}
                </Link>
              );
            })}
        </TableCell>
        <TableCell className="max-w-[200px] truncate whitespace-normal break-words">
          {release.genres &&
            release.genres.map((genre) => genre.name).join(", ")}
        </TableCell>
        <TableCell className="text-center">
          <div className="flex justify-center items-center">
            {release.format?.name?.includes("CD") ? (
              <Disc aria-label={release.format.name} />
            ) : release.format?.name?.includes("Vinyl") ? (
              <Disc3 aria-label={release.format.name} />
            ) : (
              ""
            )}
          </div>
        </TableCell>
        <TableCell className="text-center">{release.release_date}</TableCell>
        <TableCell className="text-center">{release.barcode}</TableCell>
        <TableCell>
          <Link
            to={`/release/edit/${release.id}`}
            state={{
              returnTo: `${location.pathname}${location.search}`,
            }}
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
                  This will permanently delete the release.
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
                  onClick={() => handleDeleteAndClose(release.id)}
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
