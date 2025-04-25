import ReleaseForm from "@/components/release/ReleaseForm";

export default function ReleaseCreate() {
  return (
    <>
      <h2 className="font-bold text-3xl">Add a new release</h2>
      <div className="overflow-hidden rounded-md border mt-4">
        <ReleaseForm release={null} mode="create" />
      </div>
    </>
  );
}
