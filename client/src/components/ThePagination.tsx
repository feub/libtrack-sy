import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "./ui/pagination";

export default function ThePagination({
  currentPage,
  maxPage,
  onPageChange,
}: {
  currentPage: number;
  maxPage: number;
  onPageChange: (page: number) => void;
}) {
  return (
    <Pagination>
      <PaginationContent>
        {currentPage > 1 && (
          <PaginationItem>
            <PaginationPrevious
              to={`?page=1`}
              size="default"
              onClick={(e) => {
                e.preventDefault();
                onPageChange(currentPage - 1);
              }}
            />
          </PaginationItem>
        )}
        <PaginationItem>
          <PaginationLink to="#" size="default">
            {currentPage}
          </PaginationLink>
        </PaginationItem>
        {currentPage < maxPage && (
          <PaginationItem>
            <PaginationNext
              to={`?page=${currentPage + 1}`}
              size="default"
              onClick={(e) => {
                e.preventDefault();
                onPageChange(currentPage + 1);
              }}
            />
          </PaginationItem>
        )}
      </PaginationContent>
    </Pagination>
  );
}
