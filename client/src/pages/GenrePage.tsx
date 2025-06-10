import { useEffect, useState } from "react";
import { Link } from "react-router";
import { toast } from "react-hot-toast";
import { api } from "@/utils/apiRequest";
import { validateApiResponse, handleApiError } from "@/utils/errorHandling";
import { GenreType } from "@/types/releaseTypes";
import {
  Table,
  TableBody,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import ThePagination from "@/components/ThePagination";
import TheLoader from "@/components/TheLoader";
import GenreListItem from "@/components/genre/GenreListItem";
import { CirclePlus } from "lucide-react";

const apiURL = import.meta.env.VITE_API_URL;

export default function GenrePage() {
  const [genres, setGenres] = useState<GenreType[]>([]);
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [maxPage, setMaxPage] = useState<number>(1);
  const [limit] = useState<number>(10);
  const [totalGenres, setTotalGenres] = useState<number>(0);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    getGenres(currentPage, limit);
  }, [currentPage, limit]);

  const getGenres = async (page: number = 1, limit: number = 10) => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams({
        page: page.toString(),
        limit: limit.toString(),
      });

      const response = await api.get(
        `${apiURL}/api/genre/?${params.toString()}`,
      );

      const data = await validateApiResponse(response, "Fetching genres");

      setGenres(data.data.genres);
      setMaxPage(data.data.maxPage);
      setTotalGenres(data.data.total);
    } catch (error) {
      handleApiError(error, "Fetching genres");
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
      const response = await api.delete(`${apiURL}/api/genre/${id}`);

      await validateApiResponse(response, "Deleting genre.");

      setGenres(genres.filter((genre) => genre.id !== id));
      toast.success("Genre successfully deleted.");
    } catch (error) {
      handleApiError(error, "Deleting genre.");
    }
  };

  return (
    <>
      <div className="flex items-center justify-between mb-4">
        <h2 className="font-bold text-3xl">Genres ({totalGenres})</h2>
        <Link
          to="/genre/create"
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
            <p className="mb-4">
              {totalGenres} genres - page {currentPage}/{maxPage}
            </p>
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
                {genres &&
                  genres.map((genre, index) => (
                    <GenreListItem
                      key={index}
                      genre={genre}
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
