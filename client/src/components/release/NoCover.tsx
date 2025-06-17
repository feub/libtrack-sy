import { useState } from "react";
import { api } from "@/utils/apiRequest";
import { toast } from "react-hot-toast";
import { ScannedReleaseType } from "@/types/releaseTypes";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import TheLoader from "@/components/TheLoader";
import ThePagination from "@/components/ThePagination";
import { Button } from "@/components/ui/button";
import { Link } from "react-router";
import { CirclePlus, ExternalLink } from "lucide-react";

const apiURL = import.meta.env.VITE_API_URL;

export default function NoCover({
  id,
  title,
  artist,
}: {
  id: number;
  title: string;
  artist: string;
}) {
  const [open, setOpen] = useState<boolean>(false);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [searchBy, setSearchBy] = useState<"title" | "artist">("title");
  const [releases, setReleases] = useState<{
    releases: ScannedReleaseType[];
  } | null>(null);
  const [pagination, setPagination] = useState<{
    total: number;
    page: number;
    limit: number;
    pages: number;
    items: number;
  }>({
    total: 0,
    page: 0,
    limit: 4,
    pages: 0,
    items: 0,
  });

  const searchReleases = async (
    by: string,
    searchInput: string,
    page?: number,
  ) => {
    setIsLoading(true);
    const currentPage = page || 1;

    try {
      let response;
      if (by === "title") {
        setSearchBy("title");
        response = await api.post(`${apiURL}/api/release/search`, {
          by: "release_title",
          search: searchInput,
          limit: pagination.limit,
          page: currentPage,
        });
      } else {
        setSearchBy("artist");
        response = await api.post(`${apiURL}/api/release/search`, {
          by: "artist",
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

  const handlePageChange = (page: number) => {
    if (page === pagination.page || page < 1 || page > pagination.pages) {
      return;
    }

    searchReleases(searchBy, title, page);
  };

  const handleSetCover = async (imageUrl: string) => {
    try {
      const response = await api.put(`${apiURL}/api/release/set-cover/${id}`, {
        coverImage: imageUrl,
      });

      if (!response.ok) {
        const errorData = await response.json();
        toast.error("Setting cover image failed");
        throw new Error(
          "ERROR (response): " + errorData.message ||
            "Setting cover image failed",
        );
      }

      const data = await response.json();

      if (data.type !== "success") {
        toast.error("Setting cover image failed");
        throw "ERROR: problem setting cover image.";
      } else {
        setOpen(false);
        toast.success("Cover image set successfully");
      }
    } catch (error) {
      toast.error("Setting cover image failed");
      console.error("Setting cover image error:", error);
      throw "ERROR (T/C): " + error;
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="text-neutral-700 w-[100px] h-[100px] bg-neutral-900 justify-center items-center flex rounded-md">
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogTrigger className="cursor-pointer" onClick={() => setOpen(true)}>
          No cover
        </DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              Find a cover image for "{artist} {title}"
            </DialogTitle>
            <DialogDescription>
              <div className="flex flex-row gap-4 my-4">
                <Button
                  variant="default"
                  onClick={() => searchReleases("title", title, 1)}
                >
                  Search by title
                </Button>
                <Button
                  variant="default"
                  onClick={() => searchReleases("artist", artist, 1)}
                >
                  Search by artist
                </Button>
              </div>
              {isLoading ? (
                <TheLoader style="my-4" />
              ) : (
                <>
                  {releases?.releases &&
                    releases.releases.map((release) => (
                      <div
                        key={release.id}
                        className="mb-4 pb-4 flex justify-between gap-4 border-b border-neutral-800"
                      >
                        <div>
                          <p className="font-medium text-xl">{release.title}</p>
                          <p className="mb-2 text-sm text-neutral-500">
                            <span>Artist(s): </span>
                            {release.artists
                              .map((artist) => artist.name)
                              .join(", ")}
                          </p>
                          {release.uri && (
                            <p>
                              <Link
                                to={release.uri}
                                className="flex items-center gap-2"
                                target="_blank"
                              >
                                <ExternalLink className="w-[16px] h-[16px]" />{" "}
                                Discogs URL
                              </Link>
                            </p>
                          )}
                          {release.images && release.images.length > 0 && (
                            <CirclePlus
                              className="mt-4 w-[2.5em] h-[2.5em] mr-2 text-orange-500 hover:text-orange-800"
                              onClick={() => {
                                const primaryImage = release.images.find(
                                  (img) => img.type === "primary",
                                );
                                const imageUrl =
                                  primaryImage?.resource_url ||
                                  release.images[0].resource_url;
                                handleSetCover(imageUrl);
                              }}
                            />
                          )}
                        </div>
                        <div>
                          {release.images && release.images.length > 0 ? (
                            (() => {
                              const primaryImage = release.images.find(
                                (img) => img.type === "primary",
                              );
                              return primaryImage ? (
                                <img
                                  src={primaryImage.resource_url}
                                  alt={release.title || "Album cover"}
                                  className="w-[150px] h-[150px] rounded-md"
                                />
                              ) : (
                                <img
                                  src={release.images[0].resource_url}
                                  alt={release.title || "Album cover"}
                                  className="w-[150px] h-[150px] rounded-md"
                                />
                              );
                            })()
                          ) : (
                            <div className="w-[150px] h-[150px] text-neutral-700 bg-black justify-center items-center flex rounded-md">
                              No cover
                            </div>
                          )}
                        </div>
                      </div>
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
            </DialogDescription>
          </DialogHeader>
        </DialogContent>
      </Dialog>
    </div>
  );
}
