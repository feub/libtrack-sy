import { ScannedReleaseType } from "@/types/releaseTypes";
import { Card, CardContent } from "@/components/ui/card";
import { CirclePlus } from "lucide-react";

export default function ScanResultCard({
  scannedRelease,
  barcode,
  handleAddRelease,
}: {
  scannedRelease: ScannedReleaseType;
  barcode: string;
  handleAddRelease: (barcode: string, releaseId: number) => void;
}) {
  return (
    <Card className="mt-6 mb-4">
      <CardContent className="flex justify-between">
        <div>
          <div className="flex flex-col">
            <div className="flex items-center mb-2">
              <CirclePlus
                className="w-[2.5em] h-[2.5em] mr-2 text-orange-500 hover:text-orange-800"
                onClick={() => handleAddRelease(barcode, scannedRelease.id)}
              />
              <div className="text-2xl font-bold">{scannedRelease.title}</div>
            </div>
          </div>
          <div className="flex flex-col pl-10">
            <div>
              <span className="font-bold">Artist(s):</span>{" "}
              {scannedRelease.artists && scannedRelease.artists.length > 0
                ? scannedRelease.artists.map((artist) => artist.name).join(", ")
                : "No artist"}
            </div>
            <div>
              <span className="font-bold">Year:</span>{" "}
              <span>
                {scannedRelease.year && scannedRelease.year !== 0
                  ? scannedRelease.year
                  : "unknown"}
              </span>
            </div>
            <div>
              <span className="font-bold">Format:</span>{" "}
              <span>
                {scannedRelease.formats && scannedRelease.formats.length > 0 ? (
                  scannedRelease.formats.map((format) => format.name)
                ) : (
                  <span>No format</span>
                )}
              </span>
            </div>
          </div>
        </div>
        <div>
          {scannedRelease.images && scannedRelease.images.length > 0 ? (
            (() => {
              const primaryImage = scannedRelease.images.find(
                (img) => img.type === "primary",
              );
              return primaryImage ? (
                <img
                  src={primaryImage.resource_url}
                  alt={scannedRelease.title || "Album cover"}
                  className="w-[150px]"
                />
              ) : (
                <img
                  src={scannedRelease.images[0].resource_url}
                  alt={scannedRelease.title || "Album cover"}
                  className="w-[150px]"
                />
              );
            })()
          ) : (
            <p>No cover</p>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
