import { toast } from "react-hot-toast";
import { api } from "@/utils/apiRequest";
import { LoaderCircle, Sparkles } from "lucide-react";
import { useState } from "react";

const apiURL = import.meta.env.VITE_API_URL;

type AlbumInfoForAi = {
  id: number;
  title: string;
  artist: string;
  year: number;
  description?: string;
  genre?: string;
};

export default function AiCompletion({
  albumInfo,
  onInfoChange,
}: {
  albumInfo: AlbumInfoForAi;
  onInfoChange?: (info: string) => void;
}) {
  const [, setInfo] = useState<string>("");
  const [isLoading, setIsLoading] = useState<boolean>(false);

  const completeAlbumInfo = async (album: AlbumInfoForAi) => {
    setIsLoading(true);
    try {
      try {
        const response = await api.post(
          `${apiURL}/api/ai/release/${album.id}/description`,
          {},
        );

        if (!response.ok) {
          const errorData = await response.json();
          toast.error(errorData.message || "AI completion failed");
          return;
        }

        const data = await response.json();

        if (data.type === "success") {
          const aiResponse = data.data.description;
          setInfo(aiResponse);
          onInfoChange?.(aiResponse);
          setIsLoading(false);
        }
      } catch (error) {
        console.error("AI completion error:", error);
        toast.error("AI completion failed");
      }
    } catch (error) {
      console.error("Error calling Mistral API:", error);
      throw error;
    }
  };

  return (
    <>
      {isLoading ? (
        <LoaderCircle className="animate-spin w-4 h-4 text-orange-500" />
      ) : (
        <Sparkles
          onClick={() => completeAlbumInfo(albumInfo)}
          className="text-orange-500 hover:text-orange-800 cursor-pointer w-4 h-4 transition-colors duration-200"
        />
      )}
    </>
  );
}
