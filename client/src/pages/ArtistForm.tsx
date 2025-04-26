import { useEffect, useState } from "react";
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
import { Save, Loader } from "lucide-react";
import { useParams } from "react-router";

const apiURL = import.meta.env.VITE_API_URL;

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

  return (
    <>
      <h2 className="font-bold text-3xl">
        {isUpdateMode ? <span>Update</span> : <span>Add an artist</span>}
      </h2>
      <div className="overflow-hidden rounded-md border mt-4">
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
                    The slug will be added automatically if you leave the field
                    empty.
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="thumbnail"
              render={({ field }) => (
                <FormItem className="col-span-2">
                  <FormLabel>Thumbnail</FormLabel>
                  <FormControl>
                    <Input {...field} disabled />
                  </FormControl>
                  <FormDescription>Disabled for now.</FormDescription>
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
    </>
  );
}
