import { useEffect, useRef, useState } from "react";
import { useParams, useLocation, useNavigate } from "react-router";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { toast } from "react-hot-toast";
import { api } from "@/utils/apiRequest";
import {
  ListReleasesType,
  ShelfType,
  FormatType,
  ArtistType,
  GenreType,
} from "@/types/releaseTypes";
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Save, Loader, ChevronsLeft, Upload } from "lucide-react";
import { SelectPills } from "@/components/SelectPills";
import TheLoader from "@/components/TheLoader";

const apiURL = import.meta.env.VITE_API_URL;
const imagePath = import.meta.env.VITE_IMAGES_PATH + "/covers/";

const formSchema = z.object({
  title: z
    .string()
    .min(1, { message: "Title is required." })
    .max(150, { message: "Title is too long (150 caracters max)." }),
  slug: z
    .string()
    .regex(/^[a-zA-Z0-9-]+$/, {
      message: "Slug must contain only alphanumerical caracters and hyphen.",
    })
    .nullish()
    .or(z.literal("")),
  release_date: z.coerce
    .number()
    .int()
    .min(1000, { message: "Enter a valid year (4 digits)." })
    .max(new Date().getFullYear() + 10, {
      message: "Year cannot be that far in the future.",
    })
    .optional(),
  barcode: z
    .string()
    .regex(/^[0-9]+$/, {
      message: "Barcode must contain only numerical caracters.",
    })
    .nullish()
    .or(z.literal("")),
  cover: z
    .string()
    .max(255, { message: "Cover image path too long (255 caracters max)." }),
  artists: z
    .array(z.string())
    .min(1, { message: "At least one artist must be choosen." }),
  shelf: z
    .object({ id: z.number().or(z.string()) })
    .nullable()
    .optional(),
  format: z
    .object({ id: z.number().or(z.string()) })
    .nullable()
    .optional(),
  genres: z.array(z.string()).optional(),
});

export default function ReleaseForm({ mode }: { mode: "create" | "update" }) {
  const location = useLocation();
  const navigate = useNavigate();

  // Get referrer URL from navigation state
  const referrerUrl = location.state?.returnTo;

  const handleBackToReleases = () => {
    if (referrerUrl) {
      navigate(referrerUrl);
    } else {
      // Fallback to releases page
      navigate("/release");
    }
  };

  const isUpdateMode = mode === "update";
  const [shelves, setShelves] = useState<ShelfType[] | null>(null);
  const [formats, setFormats] = useState<FormatType[] | null>(null);
  const [artists, setArtists] = useState<ArtistType[] | null>(null);
  const [genres, setGenres] = useState<GenreType[] | null>(null);
  const [release, setRelease] = useState<ListReleasesType | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isUploading, setIsUploading] = useState<boolean>(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const { id } = useParams();

  useEffect(() => {
    if (id) {
      getRelease(parseInt(id));
    }
  }, [id]);

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      title: "",
      slug: "",
      release_date: new Date().getFullYear(),
      barcode: "",
      cover: "",
      artists: [] as string[],
      shelf: null,
      format: null,
      genres: [] as string[],
    },
  });

  const getRelease = async (id: number) => {
    setIsLoading(true);
    try {
      const response = await api.get(`${apiURL}/api/release/${id}`);

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

  const getShelves = async (): Promise<boolean> => {
    try {
      const response = await api.get(`${apiURL}/api/shelf/`);

      if (!response.ok) {
        const errorData = await response.json();
        console.error(
          "ERROR (response): " + errorData.message || "Getting shelves failed",
        );
        return false;
      }

      const data = await response.json();

      if (data.type !== "success") {
        console.error("ERROR: problem getting shelves.");
        return false;
      }

      setShelves(data.data.shelves);
      return true;
    } catch (error) {
      console.error("Shelves list error:", error);
      return false;
    }
  };

  const getFormats = async (): Promise<boolean> => {
    try {
      const response = await api.get(`${apiURL}/api/format/`);

      if (!response.ok) {
        const errorData = await response.json();
        console.error(
          "ERROR (response): " + errorData.message || "Getting formats failed",
        );
        return false;
      }

      const data = await response.json();

      if (data.type !== "success") {
        console.error("ERROR: problem getting formats.");
        return false;
      }

      setFormats(data.data.formats);
      return true;
    } catch (error) {
      console.error("Formats list error:", error);
      return false;
    }
  };

  const getArtists = async (): Promise<boolean> => {
    try {
      const response = await api.get(`${apiURL}/api/artist/?limit=1000`);

      if (!response.ok) {
        const errorData = await response.json();
        console.error(
          "ERROR (response): " + errorData.message || "Getting artists failed",
        );
        return false;
      }

      const data = await response.json();

      if (data.type !== "success") {
        console.error("ERROR: problem getting artists.");
        return false;
      }

      setArtists(data.data.artists);
      return true;
    } catch (error) {
      console.error("Artists list error:", error);
      return false;
    }
  };

  const getGenres = async (): Promise<boolean> => {
    try {
      const response = await api.get(`${apiURL}/api/genre/?limit=1000`);

      if (!response.ok) {
        const errorData = await response.json();
        console.error(
          "ERROR (response): " + errorData.message || "Getting genres failed",
        );
        return false;
      }

      const data = await response.json();

      if (data.type !== "success") {
        console.error("ERROR: problem getting genres.");
        return false;
      }

      setGenres(data.data.genres);
      return true;
    } catch (error) {
      console.error("Genres list error:", error);
      return false;
    }
  };

  // Populate form with existing values
  useEffect(() => {
    // First, get the data needed for the selects
    const loadData = async () => {
      const [shelvesSuccess, formatsSuccess, artistsSuccess, genresSuccess] =
        await Promise.all([
          getShelves(),
          getFormats(),
          getArtists(),
          getGenres(),
        ]);

      if (!shelvesSuccess) {
        toast.error("Failed to load shelf locations");
      }

      if (!formatsSuccess) {
        toast.error("Failed to load formats");
      }

      if (!artistsSuccess) {
        toast.error("Failed to load artists");
      }

      if (!genresSuccess) {
        toast.error("Failed to load genres");
      }

      if (isUpdateMode && release) {
        // Set the artists as an array of names
        if (release.artists && release.artists.length > 0) {
          const artistNames = release.artists.map((artist) => artist.name);
          form.setValue("artists", artistNames);
        }

        // Set the genres as an array of names
        if (release.genres && release.genres.length > 0) {
          const genreNames = release.genres.map((genre) => genre.name);
          form.setValue("genres", genreNames);
        }

        form.setValue("title", release.title || "");
        form.setValue("slug", release.slug || "");
        form.setValue(
          "release_date",
          release.release_date ?? new Date().getFullYear(),
        );
        form.setValue(
          "barcode",
          release.barcode ? String(release.barcode) : "",
        );
        form.setValue("cover", release.cover || "");

        if (release.shelf?.id) {
          form.setValue("shelf", { id: release.shelf.id });
        } else {
          form.setValue("shelf", null);
        }

        if (release.format?.id) {
          form.setValue("format", { id: release.format.id });
        } else {
          form.setValue("format", null);
        }
      }
    };

    loadData();
  }, [release, isUpdateMode, form]);

  async function onSubmit(values: z.infer<typeof formSchema>) {
    setIsLoading(true);
    try {
      // Convert artist names to artist objects with IDs
      const artistsWithIds = values.artists
        ?.map((artistName) => {
          const artist = artists?.find((a) => a.name === artistName);
          return artist ? { id: artist.id } : null;
        })
        .filter((artist) => artist !== null);

      // Convert genre names to genre objects with IDs
      const genresWithIds = values.genres
        ?.map((genreName) => {
          const genre = genres?.find((g) => g.name === genreName);
          return genre ? { id: genre.id } : null;
        })
        .filter((genre) => genre !== null);

      // Replace the artist/genre names array with the array of artist/genre objects
      const formData = {
        ...values,
        artists: artistsWithIds,
        genres: genresWithIds,
      };

      if (isUpdateMode) {
        const response = await api.put(
          `${apiURL}/api/release/${release?.id || ""}`,
          formData,
        );

        if (!response.ok) {
          const errorData = await response.json();

          // Extract specific error messages if they exist
          let errorMessage = errorData.message || "Failed to update release";

          if (errorData.errors && Object.keys(errorData.errors).length > 0) {
            const specificErrors = Object.values(errorData.errors);
            // Get the first error message
            errorMessage = specificErrors[0]; // TODO: join all the error messages
          }

          console.error("Error updating release:", errorMessage);
          toast.error(errorMessage);
          setIsLoading(false);
          return;
        }
        toast.success("Release updated successfully!");
      } else {
        const response = await api.post(`${apiURL}/api/release/`, formData);

        if (!response.ok) {
          const errorData = await response.json();

          // Extract specific error messages if they exist
          let errorMessage = errorData.message || "Failed to update release";

          if (errorData.errors && Object.keys(errorData.errors).length > 0) {
            const specificErrors = Object.values(errorData.errors);
            // Get the first error message
            errorMessage = specificErrors[0]; // TODO: join all the error messages
          }

          console.error("Error creating release:", errorMessage);
          toast.error(errorMessage);
          setIsLoading(false);
          return;
        }
        toast.success("Release created successfully!");
      }
    } catch (error) {
      console.error("Save error:", error);
      toast.error("Failed to save release. Please try again.");
    } finally {
      setIsLoading(false);
    }
  }

  const handleFileChange = async (
    event: React.ChangeEvent<HTMLInputElement>,
  ) => {
    if (!release?.id) {
      toast.error("Release ID is required for image upload");
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

    // Check file size (max 4MB)
    if (file.size > 4 * 1024 * 1024) {
      toast.error("Image must be less than 4MB");
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

      const response = await fetch(
        `${apiURL}/api/release/${release.id}/cover`,
        {
          method: "POST",
          body: formData,
          headers,
        },
      );

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || "Image upload failed");
      }

      const data = await response.json();

      // Update the release data with the new image
      if (data.imageName) {
        setRelease({
          ...release,
          cover: data.imageName,
        });
        form.setValue("cover", data.imageName);
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
      {isLoading ? (
        <TheLoader style="my-4" />
      ) : (
        <>
          <h2 className="font-bold text-3xl">
            {isLoading ? (
              <span>Edit "{release?.title}"</span>
            ) : (
              <span>Add a release</span>
            )}
          </h2>
          <div className="overflow-hidden rounded-md border mt-4">
            <div className="flex">
              {isUpdateMode && (
                <div className="w-[300px] p-4">
                  {release?.cover ? (
                    <img
                      src={`${apiURL}/${imagePath}/${release.cover}`}
                      alt={release.title}
                      className="rounded-md w-full h-auto"
                    />
                  ) : (
                    <div className="text-neutral-700 w-full h-[200px] bg-neutral-900 justify-center items-center flex rounded-md">
                      No image
                    </div>
                  )}

                  {release?.id && (
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
                  <form onSubmit={form.handleSubmit(onSubmit)}>
                    <div className="overflow-hidden rounded-md border mt-4">
                      <div className="grid md:grid-cols-4 gap-4 p-4">
                        <FormField
                          control={form.control}
                          name="artists"
                          render={({ field }) => (
                            <FormItem className="col-span-4 md:col-span-2">
                              <FormLabel>Artist(s)</FormLabel>
                              <FormControl>
                                <SelectPills
                                  data={(artists || []).map((artist) => ({
                                    ...artist,
                                    id: artist.id.toString(),
                                  }))}
                                  value={field.value}
                                  // defaultValue={release?.artists?.map((artist) =>
                                  //   artist.name.toString(),
                                  // )}
                                  onValueChange={field.onChange}
                                  placeholder="Search for an artist..."
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name="genres"
                          render={({ field }) => (
                            <FormItem className="col-span-4 md:col-span-2">
                              <FormLabel>Genres(s)</FormLabel>
                              <FormControl>
                                <SelectPills
                                  data={(genres || []).map((genre) => ({
                                    ...genre,
                                    id: genre.id.toString(),
                                  }))}
                                  value={field.value}
                                  // defaultValue={release?.genres?.map((genre) =>
                                  //   genre.name.toString(),
                                  // )}
                                  onValueChange={field.onChange}
                                  placeholder="Search for a genre..."
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name="title"
                          render={({ field }) => (
                            <FormItem className="col-span-4">
                              <FormLabel>Title</FormLabel>
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
                            <FormItem className="col-span-4 md:col-span-2">
                              <FormLabel>Slug</FormLabel>
                              <FormControl>
                                <Input
                                  {...field}
                                  value={
                                    field.value === null ? "" : field.value
                                  }
                                />
                              </FormControl>
                              <FormDescription>
                                The slug will be added automatically if you
                                leave the field empty.
                              </FormDescription>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name="barcode"
                          render={({ field }) => (
                            <FormItem className="col-span-4 md:col-span-2">
                              <FormLabel>Barcode</FormLabel>
                              <FormControl>
                                <Input
                                  {...field}
                                  value={
                                    field.value === null ? "" : field.value
                                  }
                                />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name="release_date"
                          render={({ field }) => (
                            <FormItem className="col-span-4 md:col-span-1">
                              <FormLabel>Year</FormLabel>
                              <FormControl>
                                <Input {...field} />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name="cover"
                          render={({ field }) => (
                            <FormItem className="col-span-4 md:col-span-1">
                              <FormLabel>Cover art</FormLabel>
                              <FormControl>
                                <Input {...field} disabled />
                              </FormControl>
                              <FormMessage />
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name="shelf"
                          render={({ field }) => (
                            <FormItem className="col-span-4 md:col-span-1">
                              <FormLabel>Shelf location</FormLabel>
                              <FormControl>
                                <Select
                                  onValueChange={(value) =>
                                    field.onChange({ id: parseInt(value) })
                                  }
                                  value={field.value?.id?.toString() || ""}
                                >
                                  <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select a shelf location" />
                                  </SelectTrigger>
                                  <SelectContent>
                                    {shelves &&
                                      shelves.map((shelf, idx) => (
                                        <SelectItem
                                          key={idx}
                                          value={String(shelf.id)}
                                        >
                                          {shelf.location}
                                        </SelectItem>
                                      ))}
                                  </SelectContent>
                                </Select>
                              </FormControl>
                            </FormItem>
                          )}
                        />

                        <FormField
                          control={form.control}
                          name="format"
                          render={({ field }) => (
                            <FormItem className="col-span-4 md:col-span-1">
                              <FormLabel>Format</FormLabel>
                              <FormControl>
                                <Select
                                  onValueChange={(value) =>
                                    field.onChange({ id: parseInt(value) })
                                  }
                                  value={field.value?.id?.toString() || ""}
                                >
                                  <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select a format" />
                                  </SelectTrigger>
                                  <SelectContent>
                                    {formats &&
                                      formats.map((format, idx) => (
                                        <SelectItem
                                          key={idx}
                                          value={String(format.id)}
                                        >
                                          {format.name}
                                        </SelectItem>
                                      ))}
                                  </SelectContent>
                                </Select>
                              </FormControl>
                            </FormItem>
                          )}
                        />
                      </div>
                    </div>
                    <div className="flex justify-between mt-4">
                      <Button
                        onClick={handleBackToReleases}
                        className=""
                        variant="outline"
                      >
                        <ChevronsLeft /> back to releases
                      </Button>
                      <Button
                        type="submit"
                        className="w-[80px]"
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
                    </div>
                  </form>
                </Form>
              </div>
            </div>
          </div>
        </>
      )}
    </>
  );
}
