import { useEffect, useState } from "react";
import { useSearchParams } from "react-router";
import { toast } from "react-hot-toast";
import { api } from "@/utils/apiRequest";
import { ScannedReleaseType } from "@/types/releaseTypes";
import AddByBarcodeForm from "@/components/release/AddByBarcodeForm";
import TheLoader from "@/components/TheLoader";
import ScanResultCard from "@/components/release/ScanResultCard";

const apiURL = import.meta.env.VITE_API_URL;

export default function MusicServiceSearch() {
  const [barcode, setBarcode] = useState<string>("");
  const [releases, setReleases] = useState<{
    releases: ScannedReleaseType[];
  } | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [pagination, setPagination] = useState<{
    total: number;
    page: number;
    limit: number;
    pages: number;
  }>({
    total: 0,
    page: 1,
    limit: 3,
    pages: 0,
  });
  const [searchParams, setSearchParams] = useSearchParams();

  const page = searchParams.get("page") || 1;
  const limit = searchParams.get("limit") || 3;

  useEffect(() => {
    setSearchParams({
      page: page.toString(),
      limit: limit.toString(),
    });
  }, [page, limit, setSearchParams]);

  const handleSearchSubmit = async (barcode: string | null) => {
    if (barcode === null) {
      console.warn("Barcode is null");
      return;
    }

    setBarcode(barcode);
    searchReleases(barcode);
  };

  const searchReleases = async (barcode: string) => {
    setIsLoading(true);
    console.log(
      "Searching releases for barcode:",
      barcode,
      pagination.limit,
      pagination.page,
    );
    try {
      const response = await api.post(`${apiURL}/api/release/scan`, {
        barcode,
        limit: pagination.limit,
        page: pagination.page,
      });

      if (!response.ok) {
        const errorData = await response.json();
        toast.error("Getting releases list failed");
        throw new Error(
          "ERROR (response): " + errorData.message ||
            "Getting releases list failed",
        );
      }

      const data = await response.json();

      console.log("Releases list data:", data);

      setReleases(data.data);
      setPagination({
        total: data.data.items,
        page: data.data.page,
        limit: data.data.per_page,
        pages: data.data.pages,
      });
      setSearchParams({
        page: data.data.page.toString() || 1,
        limit: data.data.per_page.toString() || 3,
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

  const handleAddRelease = async (barcode: string, release_id: number) => {
    // Create a promise to track the API request
    const addReleasePromise = new Promise<void>((resolve, reject) => {
      api
        .post(`${apiURL}/api/release/scan/add`, {
          barcode,
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
      <h2 className="font-bold text-3xl">Add by barcode</h2>
      <AddByBarcodeForm handleBarcodeSearch={handleSearchSubmit} />
      {isLoading ? (
        <TheLoader style="my-4" />
      ) : (
        <>
          {releases && (
            <h3 className="text-xl font-bold">
              Found {releases.releases.length} results for barcode "{barcode}":
            </h3>
          )}
          {releases?.releases.map((release, index) => (
            <ScanResultCard
              key={index}
              barcode={barcode}
              scannedRelease={release}
              handleAddRelease={handleAddRelease}
            />
          ))}
          <div>
            {/* {pagination.page > 1 && (
              <button
                className="btn btn-secondary mr-2"
                onClick={() => {
                  const newPage = Math.max(1, pagination.page - 1);
                  setSearchParams({ page: newPage.toString(), limit });
                  searchReleases(barcode);
                }}
              >
                Previous
              </button>
            )} */}
            {pagination.page < pagination.pages && (
              <button
                className="btn btn-secondary"
                onClick={() => {
                  setSearchParams({
                    page: (pagination.page + 1).toString(),
                    limit: pagination.limit.toString(),
                  });
                  searchReleases(barcode);
                }}
              >
                Next
              </button>
            )}
            <p className="mt-2">
              Page {pagination.page} of {pagination.pages} ({pagination.total}{" "}
              total results)
            </p>
          </div>
        </>
      )}
    </>
  );
}
