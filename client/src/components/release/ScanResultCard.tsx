import { ScannedReleaseType } from "@/types/releaseTypes";
import { Card, CardContent } from "@/components/ui/card";
import { CirclePlus, Link } from "lucide-react";

export default function ScanResultCard({
  scannedRelease,
  barcode,
  handleAddRelease,
}: {
  scannedRelease: ScannedReleaseType;
  barcode: string | null;
  handleAddRelease: (barcode: string | null, releaseId: number) => void;
}) {
  return (
    <Card className="mt-6 mb-4">
      <CardContent className="flex justify-start gap-4">
        <CirclePlus
          className="w-[2.5em] h-[2.5em] mr-2 text-orange-500 hover:text-orange-800"
          onClick={() => handleAddRelease(barcode, scannedRelease.id)}
        />
        <div>
          <div className="flex flex-col mb-2 text-2xl font-bold">
            {scannedRelease.title}
          </div>
          <div className="flex flex-col">
            <div>
              <span className="font-bold text-neutral-400">Artist(s):</span>{" "}
              {scannedRelease.artists && scannedRelease.artists.length > 0
                ? scannedRelease.artists.map((artist) => artist.name).join(", ")
                : "No artist"}
            </div>
            <div>
              <span className="font-bold text-neutral-400">Year:</span>{" "}
              <span>
                {scannedRelease.year && scannedRelease.year !== 0
                  ? scannedRelease.year
                  : "unknown"}
              </span>
            </div>
            <div>
              <span className="font-bold text-neutral-400">Format:</span>{" "}
              <span>
                {scannedRelease.formats && scannedRelease.formats.length > 0 ? (
                  scannedRelease.formats.map((format) => format.name)
                ) : (
                  <span>No format</span>
                )}
              </span>
            </div>
            <div>
              <span className="font-bold text-neutral-400">Genre(s):</span>{" "}
              <span>
                {scannedRelease.styles && scannedRelease.styles.length > 0 ? (
                  scannedRelease.styles.join(", ")
                ) : (
                  <span>No genre</span>
                )}
              </span>
            </div>
            {scannedRelease.uri && (
              <div>
                <a
                  href={scannedRelease.uri}
                  target="_blank"
                  className="flex flex-row justify-start items-center gap-1 text-neutral-400"
                >
                  <Link className="w-4 h-4" /> Discogs URL
                </a>
              </div>
            )}
          </div>
        </div>
        <div className="ml-auto">
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
            <div className="w-[150px] h-[150px] text-neutral-700 bg-black justify-center items-center flex rounded-md">
              No cover
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
