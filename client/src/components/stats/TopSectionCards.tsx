import { useState, useEffect } from "react";
import { validateApiResponse, handleApiError } from "@/utils/errorHandling";
import { api } from "@/utils/apiRequest";
import {
  Card,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import TheLoader from "@/components/TheLoader";

const apiURL = import.meta.env.VITE_API_URL;

export default function TopSectionCards() {
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [releasesCount, setReleasesCount] = useState<number>(0);
  const [artistsCount, setArtistsCount] = useState<number>(0);
  const [genresCount, setGenresCount] = useState<number>(0);

  const getReleasesCount = async () => {
    setIsLoading(true);
    try {
      const response = await api.get(`${apiURL}/api/stats/releases/count`);

      const data = await validateApiResponse(
        response,
        "Fetching releases count",
      );

      setReleasesCount(data.data.count);
    } catch (error) {
      handleApiError(error, "Fetching releases count");
    } finally {
      setIsLoading(false);
    }
  };

  const getArtistsCount = async () => {
    setIsLoading(true);
    try {
      const response = await api.get(`${apiURL}/api/stats/artists/count`);

      const data = await validateApiResponse(
        response,
        "Fetching artists count",
      );

      setArtistsCount(data.data.count);
    } catch (error) {
      handleApiError(error, "Fetching artists count");
    } finally {
      setIsLoading(false);
    }
  };

  const getGenresCount = async () => {
    setIsLoading(true);
    try {
      const response = await api.get(`${apiURL}/api/stats/genres/count`);

      const data = await validateApiResponse(response, "Fetching genres count");

      setGenresCount(data.data.count);
    } catch (error) {
      handleApiError(error, "Fetching genres count");
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    getReleasesCount();
    getArtistsCount();
    getGenresCount();
  }, []);

  return (
    <>
      {isLoading ? (
        <TheLoader style="my-4" />
      ) : (
        <div className="*:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card dark:*:data-[slot=card]:bg-card grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:shadow-xs lg:px-4 @xl/main:grid-cols-2 @5xl/main:grid-cols-4">
          <Card className="@container/card">
            <CardHeader>
              <CardDescription>Total Releases</CardDescription>
              <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                {releasesCount}
              </CardTitle>
            </CardHeader>
          </Card>
          <Card className="@container/card">
            <CardHeader>
              <CardDescription>Total Artists</CardDescription>
              <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                {artistsCount}
              </CardTitle>
            </CardHeader>
          </Card>
          <Card className="@container/card">
            <CardHeader>
              <CardDescription>Total Genres</CardDescription>
              <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                {genresCount}
              </CardTitle>
            </CardHeader>
          </Card>
        </div>
      )}
    </>
  );
}
