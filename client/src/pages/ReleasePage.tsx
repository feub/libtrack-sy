import { useEffect, useState } from "react";
import { toast } from "react-hot-toast";
import { apiRequest } from "../utils/apiRequest";
import { validateApiResponse, handleApiError } from "../utils/errorHandling";
import { ListReleasesType } from "@/types/releaseTypes";
import {
  Table,
  TableBody,
  TableHead,
  TableHeader,
  TableRow,
} from "../components/ui/table";
import ReleaseListItem from "../components/release/ReleaseListItem";
import ThePagination from "../components/ThePagination";
import SearchBar from "../components/release/SearchBar";
import TheLoader from "@/components/TheLoader";
import { Link } from "react-router";
import { CirclePlus } from "lucide-react";

const apiURL = import.meta.env.VITE_API_URL;

export default function ReleasePage() {
  const [releases, setReleases] = useState<ListReleasesType[]>([]);
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [maxPage, setMaxPage] = useState<number>(1);
  const [limit] = useState<number>(10);
  const [totalReleases, setTotalReleases] = useState<number>(0);
  const [searchTerm, setSearchTerm] = useState<string>("");
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    getReleases(currentPage, limit, searchTerm);
  }, [currentPage, limit, searchTerm]);

  const handleSearchSubmit = async (search: string) => {
    if (search !== searchTerm) {
      setSearchTerm(search);
      setCurrentPage(1); // Reset to the first page when searching
      return Promise.resolve();
    }
  };

  const getReleases = async (
    page: number = 1,
    limit: number = 10,
    search: string = "",
  ) => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams({
        page: page.toString(),
        search: search.toString(),
        limit: limit.toString(),
      });

      const response = await apiRequest(
        `${apiURL}/api/release?${params.toString()}`,
        {
          method: "GET",
        },
      );

      const data = await validateApiResponse(response, "Fetching releases");

      setReleases(data.data.releases);
      setMaxPage(data.data.maxPage);
      setTotalReleases(data.data.totalReleases);
    } catch (error) {
      handleApiError(error, "Fetching releases");
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
      const response = await apiRequest(`${apiURL}/api/release/${id}`, {
        method: "DELETE",
      });

      await validateApiResponse(response, "Deleting release");

      setReleases(releases.filter((rel) => rel.id !== id));
      toast.success("Releases successfully deleted.");
    } catch (error) {
      handleApiError(error, "Deleting release");
    }
  };

  return (
    <>
      <div className="flex items-center justify-between">
        <h2 className="font-bold text-3xl">Releases ({totalReleases})</h2>
        <Link
          to="/release/create"
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
          <div className="flex items-center justify-between">
            <p>
              {totalReleases} releases - page {currentPage}/{maxPage}
            </p>
            <SearchBar handleSearch={handleSearchSubmit} />
          </div>
          <div className="overflow-hidden rounded-md border">
            <Table>
              <TableHeader className="sticky top-0 z-10 bg-muted">
                <TableRow>
                  <TableHead className="w-[100px]"></TableHead>
                  <TableHead>Title</TableHead>
                  <TableHead className="max-w-[200px] truncate whitespace-normal break-words">
                    Artist(s)
                  </TableHead>
                  <TableHead className="text-center">Format</TableHead>
                  <TableHead className="text-center">Year</TableHead>
                  <TableHead className="text-center">Barcode</TableHead>
                  <TableHead></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {releases &&
                  releases.map((release, index) => (
                    <ReleaseListItem
                      key={index}
                      release={release}
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
