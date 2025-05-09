import { Link } from "react-router";
import { ListReleasesType } from "../../types/releaseTypes";
import { TableRow, TableCell } from "../ui/table";
import { Button } from "@/components/ui/button";
import { Disc, Disc3, CircleX, FilePenLine } from "lucide-react";

const apiURL = import.meta.env.VITE_API_URL;
const coverPath = import.meta.env.VITE_COVER_PATH || "/covers/";

export default function ReleaseListItem({
  release,
  handleDelete,
}: {
  release: ListReleasesType;
  handleDelete: (id: number) => void;
}) {
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
            <div className="text-neutral-700 w-[100px] h-[100px] bg-neutral-900 justify-center items-center flex rounded-md">
              No cover
            </div>
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
            release.artists.map((artist) => artist.name).join(", ")}
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
            className="inline-flex h-10 items-center justify-center rounded-md px-4 py-2 text-neutral-600 hover:text-neutral-400"
          >
            <FilePenLine className="h-4 w-4" />
          </Link>
          <Button
            variant="ghost"
            onClick={() => handleDelete(release.id)}
            className="text-neutral-600 hover:text-red-600"
          >
            <CircleX />
          </Button>
        </TableCell>
      </TableRow>
    </>
  );
}
