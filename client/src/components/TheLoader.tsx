import { Loader } from "lucide-react";

export default function TheLoader({ style }: { style: string }) {
  return (
    <div className={`flex gap-x-2 text-neutral-500 ${style}`}>
      <Loader className="animate-spin-slow" /> Loading
    </div>
  );
}
