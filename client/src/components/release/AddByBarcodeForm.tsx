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
  barcode: z.coerce
    .string({
      invalid_type_error: "Barcode must contain only numbers",
    })
    .max(9999999999999, {
      message: "The barcode must be 13 digits or less.",
    }),
});

export default function AddByBarcodeForm({
  handleBarcodeSearch,
}: {
  handleBarcodeSearch: (barcode: string | null) => void;
}) {
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      barcode: "",
    },
  });

  function onSubmit(values: z.infer<typeof formSchema>) {
    const barcode = values.barcode.toString();
    handleBarcodeSearch(barcode);
  }

  function resetForm() {
    form.reset({ barcode: undefined });
    handleBarcodeSearch(null);
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="my-2 flex">
        <FormField
          control={form.control}
          name="barcode"
          render={({ field }) => (
            <FormItem>
              <FormControl>
                <div className="relative">
                  <Input
                    placeholder="Search for a barcode..."
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
        <Button
          type="submit"
          className="ml-2"
          disabled={!form.watch("barcode")}
        >
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
