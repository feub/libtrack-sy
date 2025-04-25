import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { toast } from "react-hot-toast";
import { apiRequest, api } from "@/utils/apiRequest";
import { ListReleasesType } from "@/types/releaseTypes";
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
import { Save, Loader } from "lucide-react";
import { SelectPills } from "@/components/SelectPills";

const apiURL = import.meta.env.VITE_API_URL;

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
    .optional(),
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
});

type ShelfType = {
  id: number;
  location: string;
  description: string;
  slug: string;
};

type FormatType = {
  id: number;
  name: string;
  slug: string;
};

type ArtistType = {
  id: number;
  name: string;
  slug: string;
  thumbnail: string;
};

export default function ReleaseForm({
  release,
  mode,
}: {
  release: ListReleasesType | null;
  mode: "create" | "update";
}) {
  const isUpdateMode = mode === "update";
  const [shelves, setShelves] = useState<ShelfType[] | null>(null);
  const [formats, setFormats] = useState<FormatType[] | null>(null);
  const [artists, setArtists] = useState<ArtistType[] | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);

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
    },
  });

  const getShelves = async () => {
    try {
      const response = await apiRequest(`${apiURL}/api/shelf`, {
        method: "GET",
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(
          "ERROR (response): " + errorData.message || "Getting shelves failed",
        );
      }

      const data = await response.json();

      if (data.type !== "success") {
        throw "ERROR: problem getting shelves.";
      }

      setShelves(data.data.shelves);
    } catch (error) {
      console.error("Shelves list error:", error);
      throw "ERROR (T/C): " + error;
    }
  };

  const getFormats = async () => {
    try {
      const response = await apiRequest(`${apiURL}/api/format`, {
        method: "GET",
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(
          "ERROR (response): " + errorData.message || "Getting formats failed",
        );
      }

      const data = await response.json();

      if (data.type !== "success") {
        throw "ERROR: problem getting formats.";
      }

      setFormats(data.data.formats);
    } catch (error) {
      console.error("Formats list error:", error);
      throw "ERROR (T/C): " + error;
    }
  };

  const getArtists = async () => {
    try {
      const response = await apiRequest(`${apiURL}/api/artist`, {
        method: "GET",
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(
          "ERROR (response): " + errorData.message || "Getting artists failed",
        );
      }

      const data = await response.json();

      if (data.type !== "success") {
        throw "ERROR: problem getting artists.";
      }

      setArtists(data.data.artists);
    } catch (error) {
      console.error("Artists list error:", error);
      throw "ERROR (T/C): " + error;
    }
  };

  // Populate form with existing values
  useEffect(() => {
    // First, get the data needed for the selects
    const loadData = async () => {
      await Promise.all([getShelves(), getFormats()]);
      if (isUpdateMode && release) {
        // Set the artists as an array of names
        if (release.artists && release.artists.length > 0) {
          const artistNames = release.artists.map((artist) => artist.name);
          form.setValue("artists", artistNames);
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

    getArtists();
    getShelves();
    getFormats();
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

      // Replace the artist names array with the array of artist objects
      const formData = {
        ...values,
        artists: artistsWithIds,
      };

      if (isUpdateMode) {
        const response = await api.put(
          `${apiURL}/api/release/${release?.id || ""}`,
          formData,
        );

        if (!response.ok) {
          const errorData = await response.json();
          console.error(
            "Error updating release:",
            errorData.message || "Unknown error",
          );
          toast.error(errorData.message || "Failed to update release");
          setIsLoading(false);
          return;
        }
        toast.success("Release updated successfully!");
      } else {
        const response = await api.post(`${apiURL}/api/release/`, formData);

        if (!response.ok) {
          const errorData = await response.json();
          console.error(
            "Error creating release:",
            errorData.message || "Unknown error",
          );
          toast.error(errorData.message || "Failed to create release");
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

  return (
    <>
      <Form {...form}>
        <form
          onSubmit={form.handleSubmit(onSubmit)}
          className=" grid grid-cols-2 gap-4 p-4"
        >
          <FormField
            control={form.control}
            name="artists"
            render={({ field }) => (
              <FormItem className="col-span-2">
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
            name="title"
            render={({ field }) => (
              <FormItem className="col-span-2">
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
              <FormItem className="col-span-2">
                <FormLabel>Slug</FormLabel>
                <FormControl>
                  <Input {...field} />
                </FormControl>
                <FormDescription>
                  The slug will be added automatically if you leave the field
                  empty.
                </FormDescription>
                <FormMessage />
              </FormItem>
            )}
          />

          <FormField
            control={form.control}
            name="barcode"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Barcode</FormLabel>
                <FormControl>
                  <Input
                    {...field}
                    value={field.value === null ? "" : field.value}
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
              <FormItem>
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
              <FormItem className="col-span-2">
                <FormLabel>Cover art</FormLabel>
                <FormControl>
                  <Input {...field} disabled />
                </FormControl>
                <FormDescription>Disabled for now.</FormDescription>
                <FormMessage />
              </FormItem>
            )}
          />

          <FormField
            control={form.control}
            name="shelf"
            render={({ field }) => (
              <FormItem className="">
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
                          <SelectItem key={idx} value={String(shelf.id)}>
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
              <FormItem className="">
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
                          <SelectItem key={idx} value={String(format.id)}>
                            {format.name}
                          </SelectItem>
                        ))}
                    </SelectContent>
                  </Select>
                </FormControl>
              </FormItem>
            )}
          />

          <Button type="submit" className="ml-2 w-[80px]" disabled={isLoading}>
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
    </>
  );
}
