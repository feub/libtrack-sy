import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { toast } from "react-hot-toast";
import { api } from "@/utils/apiRequest";
import { GenreType } from "@/types/releaseTypes";
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
    .min(1, { message: "Genre name is required." })
    .max(100, { message: "Genre name is too long (100 caracters max)." }),
  slug: z
    .string()
    .regex(/^[a-zA-Z0-9-]+$/, {
      message: "Slug must contain only alphanumerical caracters and hyphen.",
    })
    .nullish()
    .or(z.literal("")),
});

export default function GenreForm({ mode }: { mode: "create" | "update" }) {
  const isUpdateMode = mode === "update";
  const [genre, setGenre] = useState<GenreType | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);

  const { id } = useParams();

  useEffect(() => {
    if (isUpdateMode) {
      if (id) {
        const numericId = parseInt(id, 10);
        if (!isNaN(numericId)) {
          getGenre(numericId);
        } else {
          console.error("Invalid ID format");
        }
      } else {
        console.error("ID is undefined");
      }
    }
  }, [id, isUpdateMode]);

  const getGenre = async (id: number) => {
    try {
      const response = await api.get(`${apiURL}/api/genre/${id}`);

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(
          "ERROR (response): " + errorData.message || "Getting genre failed",
        );
      }

      const data = await response.json();

      if (data.type !== "success") {
        throw "ERROR: problem getting genre.";
      }

      setGenre(data.data.genre);
    } catch (error) {
      console.error("Genre error:", error);
      throw "ERROR (T/C): " + error;
    }
  };

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      slug: "",
    },
  });

  // Populate form with existing values
  useEffect(() => {
    const loadData = async () => {
      if (isUpdateMode && genre) {
        form.setValue("name", genre.name || "");
        form.setValue("slug", genre.slug || "");
      }
    };

    loadData();
  }, [genre, isUpdateMode, form]);

  async function onSubmit(values: z.infer<typeof formSchema>) {
    setIsLoading(true);
    try {
      if (isUpdateMode) {
        const response = await api.put(
          `${apiURL}/api/genre/${genre?.id || ""}`,
          values,
        );

        if (!response.ok) {
          const errorData = await response.json();
          console.error(
            "Error updating genre:",
            errorData.message || "Unknown error",
          );
          toast.error(errorData.message || "Failed to update genre");
          setIsLoading(false);
          return;
        }
        toast.success("Genre updated successfully!");
      } else {
        const response = await api.post(`${apiURL}/api/genre/`, values);

        if (!response.ok) {
          const errorData = await response.json();
          console.error(
            "Error creating genre:",
            errorData.message || "Unknown error",
          );
          toast.error(errorData.message || "Failed to create genre");
          setIsLoading(false);
          return;
        }
        toast.success("Genre created successfully!");
      }
    } catch (error) {
      console.error("Save error:", error);
      toast.error("Failed to save genre. Please try again.");
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <>
      <h2 className="font-bold text-3xl">
        {isUpdateMode ? <span>Update</span> : <span>Add genre</span>}
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
