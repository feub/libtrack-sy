import { useEffect, useState } from "react";
import { apiRequest } from "../utils/apiRequest";
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

const apiURL = import.meta.env.VITE_API_URL;

export default function ReleasePage() {
  const [releases, setReleases] = useState<{ title: string }[]>([]);
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [maxPage, setMaxPage] = useState<number>(1);
  const [limit] = useState<number>(10);
  const [totalReleases, setTotalReleases] = useState<number>(0);
  const [searchTerm, setSearchTerm] = useState<string>("");
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    setTimeout(() => getReleases(currentPage, limit, searchTerm), 250);
  }, [currentPage, limit, searchTerm]);

  const handleSearchSubmit = async (search: string) => {
    setSearchTerm(search);
    setCurrentPage(1); // Reset to the first page when searching
    return Promise.resolve();
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
        `${apiURL}/api/release/list?${params.toString()}`,
        {
          method: "GET",
        },
      );

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(
          "ERROR (response): " + errorData.message ||
            "Getting releases list failed",
        );
      }

      const data = await response.json();

      if (data.type !== "success") {
        throw "ERROR: problem getting releases.";
      }

      setReleases(data.releases);
      setCurrentPage(data.page);
      setMaxPage(data.maxPage);
      setTotalReleases(data.totalReleases);
    } catch (error) {
      console.error("Releases list error:", error);
      throw "ERROR (T/C): " + error;
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      <h2 className="font-bold text-3xl">Releases ({totalReleases})</h2>
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
                    <ReleaseListItem key={index} release={release} />
                  ))}
              </TableBody>
            </Table>
          </div>
          <div className="m-4">
            <ThePagination
              currentPage={currentPage}
              maxPage={maxPage}
              onPageChange={setCurrentPage}
            />
          </div>
        </>
      )}
    </>
  );
}
