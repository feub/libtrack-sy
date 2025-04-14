import { useState } from "react";
import { api } from "@/utils/apiRequest";
import { ScannedReleaseType } from "@/types/releaseTypes";
import AddByBarcodeForm from "@/components/release/AddByBarcodeForm";
import TheLoader from "@/components/TheLoader";
import ScanResultCard from "@/components/release/ScanResultCard";

const apiURL = import.meta.env.VITE_API_URL;

export default function AddByBarcodePage() {
  const [barcode, setBarcode] = useState<number | null>(null);
  const [releases, setReleases] = useState<{
    releases: ScannedReleaseType[];
  } | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  const handleSearchSubmit = async (barcode: number | null) => {
    if (barcode === null) {
      console.warn("Barcode is null");
      return;
    }

    setBarcode(barcode);
    searchReleases(barcode);
  };

  const searchReleases = async (barcode: number) => {
    setIsLoading(true);
    try {
      const response = await api.post(`${apiURL}/api/release/scan`, {
        barcode,
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(
          "ERROR (response): " + errorData.message ||
            "Getting releases list failed",
        );
      }

      const data = await response.json();

      console.log(barcode, data);

      setReleases(data);

      if (data.type !== "success") {
        throw "ERROR: problem getting releases.";
      }
    } catch (error) {
      console.error("Releases list error:", error);
      throw "ERROR (T/C): " + error;
    } finally {
      setIsLoading(false);
    }
  };

  const handleAddRelease = (barcode: number, release_id: number) => {
    console.log("added: ", barcode, release_id);
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
              barcode={barcode ?? 0}
              scannedRelease={release}
              handleAddRelease={handleAddRelease}
            />
          ))}
        </>
      )}
    </>
  );
}
