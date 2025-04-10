import AddByBarcodeForm from "@/components/release/AddByBarcodeForm";

export default function AddByBarcodePage() {
  const handleSearchSubmit = async (barcode: number | null) => {
    if (barcode === null) {
      console.warn("Barcode is null");
      return;
    }
    console.log("barcode: ", barcode);

    // setSearchTerm(search);
    // setCurrentPage(1); // Reset to the first page when searching
    // return Promise.resolve();
  };

  return (
    <>
      <h2 className="font-bold text-3xl">Add by barcode</h2>
      <AddByBarcodeForm handleBarcodeSearch={handleSearchSubmit} />
    </>
  );
}
