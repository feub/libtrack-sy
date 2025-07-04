import { useEffect, useState } from "react";
import { Link, useSearchParams, useLocation } from "react-router";
import { toast } from "react-hot-toast";
import { api } from "@/utils/apiRequest";
import { validateApiResponse, handleApiError } from "@/utils/errorHandling";
import { ListReleasesType } from "@/types/releaseTypes";
import {
  Table,
  TableBody,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import ReleaseListItem from "@/components/release/ReleaseListItem";
import ThePagination from "@/components/ThePagination";
import SearchBar from "@/components/release/SearchBar";
import TheLoader from "@/components/TheLoader";
import SortingReleasesButton from "@/components/release/SortingReleasesButton";
import { CirclePlus } from "lucide-react";
import ToggleFeaturedReleases from "@/components/release/ToggleFeaturedReleases";

const apiURL = import.meta.env.VITE_API_URL;

export default function ReleasePage() {
  const location = useLocation();
  const [searchParams, setSearchParams] = useSearchParams();

  const [releases, setReleases] = useState<ListReleasesType[]>([]);

  // Get initial page from URL params, default to 1
  const [currentPage, setCurrentPage] = useState<number>(() => {
    const pageParam = searchParams.get("page");
    return pageParam ? parseInt(pageParam, 10) : 1;
  });

  const [maxPage, setMaxPage] = useState<number>(1);
  const [limit] = useState<number>(10);
  const [sortBy, setSortBy] = useState<string>(
    searchParams.get("sort") || "createdAt",
  );
  const [sortOrderDir, setSortOrderDir] = useState<string>(
    searchParams.get("order") || "desc",
  );
  const [totalReleases, setTotalReleases] = useState<number>(0);

  const [searchTerm, setSearchTerm] = useState<string>(() => {
    return searchParams.get("search") || "";
  });

  const [isFeaturedToggled, setIsFeaturedToggled] = useState(() => {
    return searchParams.get("featured") === "1";
  });

  const [featured, setFeatured] = useState<string>(() => {
    return searchParams.get("featured") || "";
  });

  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    getReleases(currentPage, limit, sortBy, sortOrderDir, searchTerm, featured);
  }, [currentPage, limit, sortBy, sortOrderDir, searchTerm, featured]);

  // This effect runs when searchParams change (URL changes)
  useEffect(() => {
    const pageParam = searchParams.get("page");
    const searchParam = searchParams.get("search") || "";
    const featuredParam = searchParams.get("featured") || "";

    const newPage = pageParam ? parseInt(pageParam, 10) : 1;

    // Only update state if different from current to avoid loops
    if (newPage !== currentPage) {
      setCurrentPage(newPage);
    }

    if (searchParam !== searchTerm) {
      setSearchTerm(searchParam);
    }

    if (featuredParam !== featured) {
      setFeatured(featuredParam);
      setIsFeaturedToggled(featuredParam === "1");
    }
  }, [searchParams]);

  // This effect updates URL when state changes
  useEffect(() => {
    const newSearchParams = new URLSearchParams();

    if (currentPage > 1) {
      newSearchParams.set("page", currentPage.toString());
    }

    if (searchTerm) {
      newSearchParams.set("search", searchTerm);
    }

    if (featured) {
      newSearchParams.set("featured", featured);
    }

    // Avoid unnecessary URL updates by comparing with current
    const currentQueryString = searchParams.toString();
    const newQueryString = newSearchParams.toString();

    if (currentQueryString !== newQueryString) {
      setSearchParams(newSearchParams, { replace: true });
    }
  }, [currentPage, searchTerm, featured]);

  const handleSearchSubmit = async (search: string) => {
    if (search !== searchTerm) {
      // Only reset page when search changes
      if (currentPage !== 1) {
        setCurrentPage(1); // Reset to the first page when searching
      } else {
        // If we're already on page 1, we need to force a data refresh
        getReleases(1, limit, sortBy, sortOrderDir, search);
      }

      setSearchTerm(search);
    }
    return Promise.resolve();
  };

  const getReleases = async (
    page: number = 1,
    limit: number = 10,
    sort: string = "name",
    orderDir: string = "asc",
    search: string = "",
    featured: string = "",
  ) => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams({
        page: page.toString(),
        search: search.toString(),
        limit: limit.toString(),
        sort: sort.toString(),
        order: orderDir.toString(),
      });

      if (featured) {
        params.set("featured", featured);
      }

      const response = await api.get(
        `${apiURL}/api/release/?${params.toString()}`,
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
      const response = await api.delete(`${apiURL}/api/release/${id}`);

      await validateApiResponse(response, "Deleting release.");

      setReleases(releases.filter((rel) => rel.id !== id));
      toast.success("Release successfully deleted.");
    } catch (error) {
      handleApiError(error, "Deleting release.");
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

  // Handler for the toggle change
  const handleFeaturedToggle = (pressed: boolean) => {
    setIsFeaturedToggled(pressed);
    setFeatured(pressed ? "1" : "");
    setCurrentPage(1); // Reset to first page when filtering changes
  };

  return (
    <>
      <div className="flex items-center justify-between">
        <h2 className="font-bold text-3xl">Releases ({totalReleases})</h2>
        <Link
          to="/release/create"
          state={{
            returnTo: `${location.pathname}${location.search}`,
          }}
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
          <div className="flex items-center justify-between gap-4">
            <div>
              {totalReleases} releases - page {currentPage}/{maxPage}
            </div>
            <div className="flex items-center gap-2">
              <SearchBar handleSearch={handleSearchSubmit} />
              <ToggleFeaturedReleases
                pressed={isFeaturedToggled}
                onPressedChange={handleFeaturedToggle}
              />
              <SortingReleasesButton
                currentSort={sortBy}
                currentDirection={sortOrderDir}
                onSortChange={handleSortChange}
              />
            </div>
          </div>
          <div className="overflow-hidden rounded-md border">
            {releases && releases.length > 0 ? (
              <Table>
                <TableHeader className="sticky top-0 z-10 bg-muted">
                  <TableRow>
                    <TableHead className="w-[100px]"></TableHead>
                    <TableHead>Title</TableHead>
                    <TableHead className="text-center"></TableHead>
                    <TableHead className="max-w-[200px] truncate whitespace-normal break-words">
                      Artist(s)
                    </TableHead>
                    <TableHead>Genre(s)</TableHead>
                    <TableHead className="text-center">Format</TableHead>
                    <TableHead className="text-center">Year</TableHead>
                    <TableHead className="text-center">Barcode</TableHead>
                    <TableHead></TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {releases.map((release, index) => (
                    <ReleaseListItem
                      key={index}
                      release={release}
                      handleDelete={handleDelete}
                    />
                  ))}
                </TableBody>
              </Table>
            ) : (
              <div className="p-4 text-center">No releases found.</div>
            )}
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
