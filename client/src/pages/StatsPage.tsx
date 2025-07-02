import FeaturedReleasesCard from "@/components/stats/FeaturedReleasesCard";
import FormatPieChartCard from "@/components/stats/FormatPieChartCard";
import GenreBarChartCard from "@/components/stats/GenreBarChartCard";
import TopSectionCards from "@/components/stats/TopSectionCards";

export default function StatsPage() {
  return (
    <>
      <div className="flex items-center justify-between mb-4">
        <h2 className="font-bold text-3xl">Statistics 🤘</h2>
      </div>

      <div className="flex flex-1 flex-col">
        <div className="@container/main flex flex-1 flex-col gap-2">
          <div className="flex flex-col gap-4 py-4 md:gap-4 md:py-4">
            <TopSectionCards />

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 px-4 lg:px-4">
              <FormatPieChartCard />
              <GenreBarChartCard />
            </div>

            <FeaturedReleasesCard />
          </div>
        </div>
      </div>
    </>
  );
}
