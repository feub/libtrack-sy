import { useState, useEffect } from "react";
import { validateApiResponse, handleApiError } from "@/utils/errorHandling";
import { api } from "@/utils/apiRequest";
import { ListReleasesType } from "@/types/releaseTypes";
import {
  Card,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import TheLoader from "@/components/TheLoader";
import { Link } from "react-router";
import { ChevronsRight } from "lucide-react";

const apiURL = import.meta.env.VITE_API_URL;
const coverPath = import.meta.env.VITE_IMAGES_PATH + "/covers/";

export default function FeaturedReleasesCard() {
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [releases, setReleases] = useState<ListReleasesType[]>([]);

  const getTopReleases = async () => {
    setIsLoading(true);
    try {
      const response = await api.get(
        `${apiURL}/api/release/?featured=1&limit=5`,
      );

      const data = await validateApiResponse(response, "Fetching top releases");

      setReleases(data.data.releases);
    } catch (error) {
      handleApiError(error, "Fetching releases count");
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    getTopReleases();
  }, []);

  return (
    <>
      {isLoading ? (
        <TheLoader style="my-4" />
      ) : (
        <div className="*:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card dark:*:data-[slot=card]:bg-card grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:shadow-xs">
          <Card className="@container/card">
            <CardHeader>
              <CardDescription className="font-bold text-xl mb-4">
                Top 5 releases
              </CardDescription>
              <CardTitle className="font-semibold">
                <div className="flex flex-row gap-4">
                  {releases && releases.length > 0
                    ? releases.map((release) => (
                        <div key={release.id} className="">
                          <Link
                            to={`/release/?search=${release.title}`}
                            className="text-blue-600 hover:underline"
                            title={`${release.title} by ${
                              release.artists &&
                              release.artists
                                .map((artist) => artist.name)
                                .join(", ")
                            }`}
                          >
                            <img
                              src={`${apiURL}${coverPath}${release.cover}`}
                              alt={release.title}
                              className="w-[200px] h-[200px] object-cover rounded-md mb-1"
                            />
                          </Link>
                        </div>
                      ))
                    : "<div>No featured releases available</div>"}
                </div>
              </CardTitle>
            </CardHeader>
            <CardFooter className="flex justify-between">
              <div className="font-medium">
                Those are the top 5 featured releases in the collection.{" "}
              </div>
              <Link
                to={`/release/?featured=1`}
                className="flex items-center gap-1 hover:underline text-neutral-500"
              >
                <ChevronsRight /> See all featured releases
              </Link>
            </CardFooter>
          </Card>
        </div>
      )}
    </>
  );
}
