import { ListReleasesType } from "../../types/releaseTypes";
import { TableRow, TableCell } from "../ui/table";
import { Button } from "@/components/ui/button";
import { Disc, Disc3, CircleX } from "lucide-react";

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
            />
          ) : (
            <div className="text-neutral-700 w-[100px] h-[100px] bg-neutral-900 justify-center items-center flex">
              No cover
            </div>
          )}
        </TableCell>
        <TableCell className="font-medium">
          {release.title}
          {release.shelf && (
            <>
              <br />
              <span className="text-neutral-500">Shelf: {release.shelf}</span>
            </>
          )}
        </TableCell>
        <TableCell className="max-w-[200px] truncate whitespace-normal break-words">
          {release.artists &&
            release.artists.map((artist) => artist.name).join(", ")}
        </TableCell>
        <TableCell className="text-center">
          <div className="flex justify-center items-center">
            {release.format?.includes("CD") ? (
              <Disc aria-label={release.format} />
            ) : release.format?.includes("Vinyl") ? (
              <Disc3 aria-label={release.format} />
            ) : (
              ""
            )}
          </div>
        </TableCell>
        <TableCell className="text-center">{release.release_date}</TableCell>
        <TableCell className="text-center">{release.barcode}</TableCell>
        <TableCell>
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
