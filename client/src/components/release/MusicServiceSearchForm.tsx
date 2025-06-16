import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { X, Search } from "lucide-react";
import { Label } from "@/components/ui/label";

const formSchema = z.object({
  search: z.string({
    invalid_type_error: "Search cannot be empty",
  }),
});

export default function MusicServiceSearchForm({
  handleSearch,
}: {
  handleSearch: (search: string | null) => void;
}) {
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      search: "",
    },
  });

  function onSubmit(values: z.infer<typeof formSchema>) {
    const search = values.search.toString();
    handleSearch(search);
  }

  function resetForm() {
    form.reset({ search: undefined });
    handleSearch(null);
  }

  return (
    <Form {...form}>
      <div className="my-4">
        <Label htmlFor="search">Search for a barcode or a title</Label>
      </div>
      <form onSubmit={form.handleSubmit(onSubmit)} className="my-2 flex">
        <FormField
          control={form.control}
          name="search"
          render={({ field }) => (
            <FormItem className="flex-row items-end">
              <FormControl>
                <div className="relative">
                  <Input
                    placeholder="Search for a barcode or a title..."
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
        <Button type="submit" className="ml-2" disabled={!form.watch("search")}>
          <Search />
        </Button>
      </form>
      <p className="mb-6 text-neutral-500 text-sm">
        Ex.:
        <br />
        5020157105125 (Anathema "Pentecost III" CD)
        <br /> 016861923822 (Death "Human" CD)
        <br />
        075678235825 (Tori Amos "Little Earthquake" CD)
        <br />
      </p>
    </Form>
  );
}
