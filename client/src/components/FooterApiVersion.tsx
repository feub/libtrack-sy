import { useState } from "react";
import { api } from "@/utils/apiRequest";

const apiURL = import.meta.env.VITE_API_URL;

export default function FooterApiVersion() {
  const [version, setVersion] = useState<string>("Check API version");

  const showApiVersion = async () => {
    try {
      const response = await api.get(`${apiURL}/api/version`);

      if (!response.ok) {
        const errorData = await response.json();
        console.error(
          "ERROR (response): " + errorData.message || "Getting API version",
        );
      }

      const data = await response.json();

      setVersion(`API version ${data.data.version}`);
    } catch (error) {
      console.error("Releases list error:", error);
    }
  };

  return (
    <>
      <span onClick={showApiVersion}>{version}</span>
    </>
  );
}
