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
        <div className="flex flex-row gap-4 mb-4">
          <Card className="w-[250px] @container/card">
            <CardHeader>
              <CardDescription>Total Releases</CardDescription>
              <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                {releasesCount}
              </CardTitle>
            </CardHeader>
          </Card>
          <Card className="w-[250px] @container/card">
            <CardHeader>
              <CardDescription>Total Artists</CardDescription>
              <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                {artistsCount}
              </CardTitle>
            </CardHeader>
          </Card>
          <Card className="w-[250px] @container/card">
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
