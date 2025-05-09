import FormatStatsCard from "@/components/stats/FormatStatsCard";

export default function StatsPage() {
  return (
    <>
      <div className="flex items-center justify-between mb-4">
        <h2 className="font-bold text-3xl">Statistics</h2>
      </div>
      <div className="flex gap-4">
        <FormatStatsCard />
      </div>
    </>
  );
}
