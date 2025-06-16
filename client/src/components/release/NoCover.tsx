import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";

export default function NoCover() {
  return (
    <div className="text-neutral-700 w-[100px] h-[100px] bg-neutral-900 justify-center items-center flex rounded-md">
      <Dialog>
        <DialogTrigger className="cursor-pointer">No cover</DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Do you want to try finding a cover?</DialogTitle>
            <DialogDescription>
              See if anything comes up in the cover search.
            </DialogDescription>
          </DialogHeader>
        </DialogContent>
      </Dialog>
    </div>
  );
}
