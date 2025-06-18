import FormatPieChartCard from "@/components/stats/FormatPieChartCard";
import GenreBarChartCard from "@/components/stats/GenreBarChartCard";
import TopSectionCards from "@/components/stats/TopSectionCards";

export default function StatsPage() {
  return (
    <>
      <div className="flex items-center justify-between mb-4">
        <h2 className="font-bold text-3xl">Statistics 🤘</h2>
      </div>

      <TopSectionCards />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <FormatPieChartCard />
        <GenreBarChartCard />
      </div>
    </>
  );
}
