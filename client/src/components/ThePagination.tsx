import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";
import { ChevronsLeft, ChevronsRight } from "lucide-react";

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
          <>
            <PaginationItem>
              <PaginationLink
                to={`?page=1`}
                size="default"
                onClick={(e) => {
                  e.preventDefault();
                  onPageChange(1);
                }}
              >
                <ChevronsLeft />
              </PaginationLink>
            </PaginationItem>
            <PaginationItem>
              <PaginationPrevious
                to={`?page=${currentPage - 1}`}
                size="default"
                onClick={(e) => {
                  e.preventDefault();
                  onPageChange(currentPage - 1);
                }}
              />
            </PaginationItem>
          </>
        )}
        {currentPage > 1 && (
          <PaginationItem>
            <PaginationLink
              to={`?page=${currentPage - 1}`}
              size="default"
              onClick={(e) => {
                e.preventDefault();
                onPageChange(currentPage - 1);
              }}
            >
              {currentPage - 1}
            </PaginationLink>
          </PaginationItem>
        )}
        <PaginationItem>
          <PaginationLink to="#" size="default" isActive>
            {currentPage}
          </PaginationLink>
        </PaginationItem>
        {currentPage < maxPage && (
          <PaginationItem>
            <PaginationLink
              to={`?page=${currentPage + 1}`}
              size="default"
              onClick={(e) => {
                e.preventDefault();
                onPageChange(currentPage + 1);
              }}
            >
              {currentPage + 1}
            </PaginationLink>
          </PaginationItem>
        )}
        {currentPage < maxPage && (
          <>
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
            <PaginationItem>
              <PaginationLink
                to={`?page=${maxPage}`}
                size="default"
                onClick={(e) => {
                  e.preventDefault();
                  onPageChange(maxPage);
                }}
              >
                <ChevronsRight />
              </PaginationLink>
            </PaginationItem>
          </>
        )}
      </PaginationContent>
    </Pagination>
  );
}
