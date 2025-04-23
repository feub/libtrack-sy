import { useEffect, useState } from "react";
import { useParams } from "react-router";
import { apiRequest } from "../utils/apiRequest";
import { ListReleasesType } from "@/types/releaseTypes";
import TheLoader from "@/components/TheLoader";
import ReleaseForm from "@/components/release/ReleaseForm";

const apiURL = import.meta.env.VITE_API_URL;

export default function ReleaseEdit() {
  const [release, setRelease] = useState<ListReleasesType | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  const { id } = useParams();

  useEffect(() => {
    if (id) {
      getRelease(parseInt(id));
    }
  }, [id]);

  const getRelease = async (id: number) => {
    setIsLoading(true);
    try {
      const response = await apiRequest(`${apiURL}/api/release/${id}`, {
        method: "GET",
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(
          "ERROR (response): " + errorData.message || "Getting release failed",
        );
      }

      const data = await response.json();

      if (data.type !== "success") {
        throw "ERROR: problem getting the release.";
      }

      setRelease(data.data.release);
    } catch (error) {
      console.error("Releases list error:", error);
      throw "ERROR (T/C): " + error;
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      {isLoading ? (
        <TheLoader style="my-4" />
      ) : (
        <>
          <h2 className="font-bold text-3xl">Edit "{release?.title}"</h2>
          <div className="overflow-hidden rounded-md border mt-4">
            <ReleaseForm release={release} mode="update" />
          </div>
        </>
      )}
    </>
  );
}
