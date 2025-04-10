import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "../ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormMessage,
} from "../ui/form";
import { Input } from "../ui/input";
import { X, Search } from "lucide-react";

const formSchema = z.object({
  search: z.string().max(100, {
    message: "The search must be 100 characters max.",
  }),
});

export default function SearchBar({
  handleSearch,
}: {
  handleSearch: (search: string) => void;
}) {
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      search: "",
    },
  });

  function onSubmit(values: z.infer<typeof formSchema>) {
    handleSearch(values.search);
  }

  function resetForm() {
    form.reset({ search: "" });
    handleSearch(""); // Also reset the search results
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="my-6 flex">
        <FormField
          control={form.control}
          name="search"
          render={({ field }) => (
            <FormItem>
              <FormControl>
                <div className="relative">
                  <Input
                    placeholder="Search for a release or artist..."
                    {...field}
                    className="w-md"
                  />
                  {field.value && ( // Only show X when there's text
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="absolute right-1 top-1/2 -translate-y-1/2 h-6 w-6"
                      onClick={resetForm}
                      aria-label="Clear search"
                    >
                      <X className="h-4 w-4" />
                    </Button>
                  )}
                </div>
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <Button type="submit" className="ml-2">
          <Search />
        </Button>
      </form>
    </Form>
  );
}
