import { useEffect, useState } from "react";
import { Link, useSearchParams } from "react-router";
import { toast } from "react-hot-toast";
import { api } from "@/utils/apiRequest";
import { validateApiResponse, handleApiError } from "@/utils/errorHandling";
import { ArtistType } from "@/types/releaseTypes";
import {
  Table,
  TableBody,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import ThePagination from "@/components/ThePagination";
import TheLoader from "@/components/TheLoader";
import ArtistListItem from "@/components/artist/ArtistListItem";
import SortingArtistsButton from "@/components/artist/SortingArtistsButton";
import { CirclePlus } from "lucide-react";

const apiURL = import.meta.env.VITE_API_URL;

export default function ArtistPage() {
  const [searchParams, setSearchParams] = useSearchParams();

  const [artists, setArtists] = useState<ArtistType[]>([]);
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [maxPage, setMaxPage] = useState<number>(1);
  const [limit] = useState<number>(10);
  const [sortBy, setSortBy] = useState<string>(
    searchParams.get("sort") || "name",
  );
  const [sortOrderDir, setSortOrderDir] = useState<string>(
    searchParams.get("order") || "asc",
  );
  const [totalArtists, setTotalArtists] = useState<number>(0);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    getArtists(currentPage, limit, sortBy, sortOrderDir);
  }, [currentPage, limit, sortBy, sortOrderDir]);

  const getArtists = async (
    page: number = 1,
    limit: number = 10,
    sort: string = "name",
    orderDir: string = "asc",
  ) => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams({
        page: page.toString(),
        limit: limit.toString(),
        sort: sort.toString(),
        order: orderDir.toString(),
      });

      const response = await api.get(
        `${apiURL}/api/artist/?${params.toString()}`,
      );

      const data = await validateApiResponse(response, "Fetching artists");

      setArtists(data.data.artists);
      setMaxPage(data.data.maxPage);
      setTotalArtists(data.data.totalArtists);
    } catch (error) {
      handleApiError(error, "Fetching artists");
    } finally {
      setIsLoading(false);
    }
  };

  // Handle page change from pagination component
  const handlePageChange = (page: number) => {
    if (page === currentPage || page < 1 || page > maxPage) {
      return;
    }
    setCurrentPage(page);
  };

  const handleDelete = async (id: number) => {
    try {
      const response = await api.delete(`${apiURL}/api/artist/${id}`);

      await validateApiResponse(response, "Deleting artist.");

      setArtists(artists.filter((artist) => artist.id !== id));
      toast.success("Artist successfully deleted.");
    } catch (error) {
      handleApiError(error, "Deleting artist.");
    }
  };

  const handleSortChange = (sortBy: string, sortDir: string) => {
    setSortBy(sortBy);
    setSortOrderDir(sortDir);
    setCurrentPage(1);

    // Update URL params
    const newParams = new URLSearchParams(searchParams);
    newParams.set("sort", sortBy);
    newParams.set("order", sortDir);
    newParams.set("page", "1");
    setSearchParams(newParams);
  };

  return (
    <>
      <div className="flex items-center justify-between mb-4">
        <h2 className="font-bold text-3xl">Artists ({totalArtists})</h2>
        <Link
          to="/artist/create"
          className="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-primary text-primary-foreground shadow-xs hover:bg-primary/90 h-9 px-4 py-2 has-[>svg]:px-3"
        >
          <CirclePlus />
          Add
        </Link>
      </div>
      {isLoading ? (
        <TheLoader style="my-4" />
      ) : (
        <>
          <div className="flex items-center justify-between mb-4">
            <div>
              {totalArtists} artists - page {currentPage}/{maxPage}
            </div>
            <SortingArtistsButton
              currentSort={sortBy}
              currentDirection={sortOrderDir}
              onSortChange={handleSortChange}
            />
          </div>
          <div className="overflow-hidden rounded-md border">
            <Table>
              <TableHeader className="sticky top-0 z-10 bg-muted">
                <TableRow>
                  <TableHead className="max-w-[200px] truncate whitespace-normal break-words">
                    Name
                  </TableHead>
                  <TableHead>Slug</TableHead>
                  <TableHead className="w-[100px] text-center"></TableHead>
                  <TableHead className="text-right"></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {artists &&
                  artists.map((artist, index) => (
                    <ArtistListItem
                      key={index}
                      artist={artist}
                      handleDelete={handleDelete}
                    />
                  ))}
              </TableBody>
            </Table>
          </div>
          <div className="m-4">
            <ThePagination
              currentPage={currentPage}
              maxPage={maxPage}
              onPageChange={handlePageChange}
            />
          </div>
        </>
      )}
    </>
  );
}
