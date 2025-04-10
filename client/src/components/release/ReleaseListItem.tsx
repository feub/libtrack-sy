import { ListReleasesType } from "../../types/releaseTypes";
import { TableRow, TableCell } from "../ui/table";

const apiURL = import.meta.env.VITE_API_URL;
const coverPath = import.meta.env.VITE_COVER_PATH || "/covers/";

export default function ReleaseListItem({
  release,
}: {
  release: ListReleasesType;
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
        <TableCell className="font-medium">{release.title}</TableCell>
        <TableCell className="max-w-[200px] truncate whitespace-normal break-words">
          {release.artists &&
            release.artists.map((artist) => artist.name).join(", ")}
        </TableCell>
        <TableCell className="text-center">{release.release_date}</TableCell>
        <TableCell className="text-center">{release.barcode}</TableCell>
        <TableCell></TableCell>
      </TableRow>
    </>
  );
}
