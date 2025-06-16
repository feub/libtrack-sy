import { useState } from "react";
import { toast } from "react-hot-toast";
import { api } from "@/utils/apiRequest";
import { ScannedReleaseType } from "@/types/releaseTypes";
import MusicServiceSearchForm from "@/components/release/MusicServiceSearchForm";
import TheLoader from "@/components/TheLoader";
import ScanResultCard from "@/components/release/ScanResultCard";
import ThePagination from "@/components/ThePagination";

const apiURL = import.meta.env.VITE_API_URL;

export default function MusicServiceSearch() {
  const [search, setSearch] = useState<string>("");
  const [releases, setReleases] = useState<{
    releases: ScannedReleaseType[];
  } | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [pagination, setPagination] = useState<{
    total: number;
    page: number;
    limit: number;
    pages: number;
    items: number;
  }>({
    total: 0,
    page: 0,
    limit: 6,
    pages: 0,
    items: 0,
  });

  const isBarcode = (input: string): boolean => {
    return /^\d+$/.test(input.trim());
  };

  const handlePageChange = (page: number) => {
    if (page === pagination.page || page < 1 || page > pagination.pages) {
      return;
    }

    searchReleases(search, page);
  };

  const handleSearchSubmit = async (searchInput: string | null) => {
    if (searchInput === null || searchInput.trim() === "") {
      return;
    }

    const trimmedInput = searchInput.trim();

    setSearch(trimmedInput);
    searchReleases(trimmedInput, 1);
  };

  const searchReleases = async (searchInput: string, page?: number) => {
    setIsLoading(true);
    const currentPage = page || 1;

    try {
      let response;
      if (isBarcode(searchInput)) {
        response = await api.post(`${apiURL}/api/release/scan`, {
          barcode: searchInput,
          limit: pagination.limit,
          page: currentPage,
        });
      } else {
        response = await api.post(`${apiURL}/api/release/search`, {
          by: "release_title",
          search: searchInput,
          limit: pagination.limit,
          page: currentPage,
        });
      }

      if (!response.ok) {
        const errorData = await response.json();
        toast.error("Getting releases list failed");
        throw new Error(
          "ERROR (response): " + errorData.message ||
            "Getting releases list failed",
        );
      }

      const data = await response.json();

      setReleases(data.data);
      setPagination({
        total: data.data.items,
        page: data.data.page,
        limit: data.data.per_page,
        pages: data.data.pages,
        items: data.data.items,
      });

      if (data.type !== "success") {
        toast.error("Getting releases list failed");
        throw "ERROR: problem getting releases.";
      }
    } catch (error) {
      toast.error("Getting releases list failed");
      console.error("Releases list error:", error);
      throw "ERROR (T/C): " + error;
    } finally {
      setIsLoading(false);
    }
  };

  const handleAddRelease = async (
    barcode: string | null,
    release_id: number,
  ) => {
    // Create a promise to track the API request
    const addReleasePromise = new Promise<void>((resolve, reject) => {
      api
        .post(`${apiURL}/api/release/scan/add`, {
          ...(barcode !== null && { barcode: barcode.trim() }),
          release_id,
        })
        .then(async (response) => {
          if (!response.ok) {
            const errorData = await response.json();
            console.error(
              "Error adding release:",
              errorData.message || "Unknown error",
            );
            reject(
              new Error(errorData.message || "Getting releases list failed"),
            );
          } else {
            resolve();
          }
        })
        .catch((error) => {
          console.error("Scan add error:", error);
          reject(error);
        });
    });

    // Use toast.promise to show loading/success/error states
    toast.promise(addReleasePromise, {
      loading: "Adding release...",
      success: "Release added successfully!",
      error: (err) => `${err.message || "Adding release failed"}`,
    });
  };

  return (
    <>
      <h2 className="font-bold text-3xl">Music Service Search</h2>
      <MusicServiceSearchForm handleSearch={handleSearchSubmit} />
      {isLoading ? (
        <TheLoader style="my-4" />
      ) : (
        <>
          {releases && (
            <h3 className="text-xl font-bold">
              Found {pagination.items} results for{" "}
              {isBarcode(search) ? "barcode" : "title"} "{search}":
            </h3>
          )}
          {releases?.releases.map((release, index) => (
            <ScanResultCard
              key={index}
              barcode={isBarcode(search) ? search : null}
              scannedRelease={release}
              handleAddRelease={handleAddRelease}
            />
          ))}
          {pagination.pages > 0 && (
            <ThePagination
              currentPage={pagination.page}
              maxPage={pagination.pages}
              onPageChange={handlePageChange}
            />
          )}
        </>
      )}
    </>
  );
}
