import { useEffect, useRef, useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { toast } from "react-hot-toast";
import { api } from "@/utils/apiRequest";
import { ArtistType } from "@/types/releaseTypes";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Save, Loader, Upload } from "lucide-react";
import { useParams } from "react-router";

const apiURL = import.meta.env.VITE_API_URL;
const imagePath = import.meta.env.VITE_IMAGES_PATH + "/artists/";

const formSchema = z.object({
  name: z
    .string()
    .min(1, { message: "Artist name is required." })
    .max(100, { message: "Artist name is too long (100 caracters max)." }),
  slug: z
    .string()
    .regex(/^[a-zA-Z0-9-]+$/, {
      message: "Slug must contain only alphanumerical caracters and hyphen.",
    })
    .nullish()
    .or(z.literal("")),
  thumbnail: z
    .string()
    .max(255, { message: "Thumbnail too long (255 caracters max)." }),
});

export default function ArtistForm({ mode }: { mode: "create" | "update" }) {
  const isUpdateMode = mode === "update";
  const [artist, setArtist] = useState<ArtistType | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isUploading, setIsUploading] = useState<boolean>(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const { id } = useParams();

  useEffect(() => {
    if (isUpdateMode) {
      if (id) {
        const numericId = parseInt(id, 10);
        if (!isNaN(numericId)) {
          getArtist(numericId);
        } else {
          console.error("Invalid ID format");
        }
      } else {
        console.error("ID is undefined");
      }
    }
  }, [id, isUpdateMode]);

  const getArtist = async (id: number) => {
    try {
      const response = await api.get(`${apiURL}/api/artist/${id}`);

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(
          "ERROR (response): " + errorData.message || "Getting artist failed",
        );
      }

      const data = await response.json();

      if (data.type !== "success") {
        throw "ERROR: problem getting artist.";
      }

      setArtist(data.data.artist);
    } catch (error) {
      console.error("Artist error:", error);
      throw "ERROR (T/C): " + error;
    }
  };

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      slug: "",
      thumbnail: "",
    },
  });

  // Populate form with existing values
  useEffect(() => {
    const loadData = async () => {
      if (isUpdateMode && artist) {
        form.setValue("name", artist.name || "");
        form.setValue("slug", artist.slug || "");
        form.setValue("thumbnail", artist.thumbnail || "");
      }
    };

    loadData();
  }, [artist, isUpdateMode, form]);

  async function onSubmit(values: z.infer<typeof formSchema>) {
    setIsLoading(true);
    try {
      if (isUpdateMode) {
        const response = await api.put(
          `${apiURL}/api/artist/${artist?.id || ""}`,
          values,
        );

        if (!response.ok) {
          const errorData = await response.json();
          console.error(
            "Error updating artist:",
            errorData.message || "Unknown error",
          );
          toast.error(errorData.message || "Failed to update artist");
          setIsLoading(false);
          return;
        }
        toast.success("Artist updated successfully!");
      } else {
        const response = await api.post(`${apiURL}/api/artist/`, values);

        if (!response.ok) {
          const errorData = await response.json();
          console.error(
            "Error creating artist:",
            errorData.message || "Unknown error",
          );
          toast.error(errorData.message || "Failed to create artist");
          setIsLoading(false);
          return;
        }
        toast.success("Artist created successfully!");
      }
    } catch (error) {
      console.error("Save error:", error);
      toast.error("Failed to save artist. Please try again.");
    } finally {
      setIsLoading(false);
    }
  }

  const handleFileChange = async (
    event: React.ChangeEvent<HTMLInputElement>,
  ) => {
    if (!artist?.id) {
      toast.error("Artist ID is required for image upload");
      return;
    }

    const file = event.target.files?.[0];
    if (!file) {
      return;
    }

    // Check file type
    if (!file.type.startsWith("image/")) {
      toast.error("Please select an image file");
      return;
    }

    // Check file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
      toast.error("Image must be less than 2MB");
      return;
    }

    setIsUploading(true);
    try {
      const formData = new FormData();
      formData.append("image", file);

      const token = localStorage.getItem("access_token");
      const headers: HeadersInit = {};

      if (token) {
        headers["Authorization"] = `Bearer ${token}`;
      }

      const response = await fetch(`${apiURL}/api/artist/${artist.id}/image`, {
        method: "POST",
        body: formData,
        headers,
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || "Image upload failed");
      }

      const data = await response.json();

      // Update the artist data with the new image
      if (data.imageName) {
        setArtist({
          ...artist,
          thumbnail: data.imageName,
        });
        form.setValue("thumbnail", data.imageName);
        toast.success("Image uploaded successfully");
      }
    } catch (error) {
      console.error("Image upload error:", error);
      toast.error("Failed to upload image. Please try again.");
    } finally {
      setIsUploading(false);
      // Clear the file input
      if (fileInputRef.current) {
        fileInputRef.current.value = "";
      }
    }
  };

  return (
    <>
      <h2 className="font-bold text-3xl">
        {isUpdateMode ? <span>Update</span> : <span>Add an artist</span>}
      </h2>
      <div className="overflow-hidden rounded-md border mt-4">
        <div className="flex">
          {isUpdateMode && (
            <div className="w-[300px] p-4">
              {artist?.thumbnail ? (
                <img
                  src={`${apiURL}/${imagePath}/${artist.thumbnail}`}
                  alt={artist.name}
                  className="rounded-md w-full h-auto"
                />
              ) : (
                <div className="text-neutral-700 w-full h-[200px] bg-neutral-900 justify-center items-center flex rounded-md">
                  No image
                </div>
              )}

              {artist?.id && (
                <div className="mt-4">
                  <Input
                    type="file"
                    ref={fileInputRef}
                    onChange={handleFileChange}
                    className="hidden"
                    accept="image/*"
                  />
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => fileInputRef.current?.click()}
                    className="w-full"
                    disabled={isUploading}
                  >
                    {isUploading ? (
                      <>
                        <Loader className="mr-2 h-4 w-4 animate-spin" />{" "}
                        Uploading...
                      </>
                    ) : (
                      <>
                        <Upload className="mr-2 h-4 w-4" /> Upload Image
                      </>
                    )}
                  </Button>
                </div>
              )}
            </div>
          )}
          <div className="flex-1">
            <Form {...form}>
              <form
                onSubmit={form.handleSubmit(onSubmit)}
                className=" grid grid-cols-2 gap-4 p-4"
              >
                <FormField
                  control={form.control}
                  name="name"
                  render={({ field }) => (
                    <FormItem className="col-span-2">
                      <FormLabel>Name</FormLabel>
                      <FormControl>
                        <Input {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="slug"
                  render={({ field }) => (
                    <FormItem className="col-span-2">
                      <FormLabel>Slug</FormLabel>
                      <FormControl>
                        <Input
                          {...field}
                          value={field.value === null ? "" : field.value}
                        />
                      </FormControl>
                      <FormDescription>
                        The slug will be added automatically if you leave the
                        field empty.
                      </FormDescription>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <Button
                  type="submit"
                  className="ml-2 w-[80px]"
                  disabled={isLoading}
                >
                  {isLoading ? (
                    <>
                      <Loader /> Saving...
                    </>
                  ) : (
                    <>
                      <Save /> Save
                    </>
                  )}
                </Button>
              </form>
            </Form>
          </div>
        </div>
      </div>
    </>
  );
}
