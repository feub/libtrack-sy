import { useEffect, useState } from "react";
import { validateApiResponse, handleApiError } from "@/utils/errorHandling";
import { api } from "@/utils/apiRequest";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Bar,
  BarChart,
  CartesianGrid,
  LabelList,
  XAxis,
  YAxis,
} from "recharts";
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
} from "@/components/ui/chart";
import TheLoader from "@/components/TheLoader";

const apiURL = import.meta.env.VITE_API_URL;

type ChartConfigType = {
  [key: string]: {
    label?: string;
    color: string;
  };
};

type GenresStatsType = {
  genre_id: number;
  genre_name: string;
  count: number;
};

export default function GenreBarChartCard() {
  const [chrtData, setChrtData] = useState<Array<GenresStatsType>>([]);
  const [chrtConfig, setChrtConfig] = useState<ChartConfigType>({});
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    getStats();
  }, []);

  const getStats = async () => {
    setIsLoading(true);
    try {
      const response = await api.get(`${apiURL}/api/stats/genres`);

      const data = await validateApiResponse(response, "Fetching genres stats");

      const sortedData = data.data.sort(
        (a: GenresStatsType, b: GenresStatsType) => b.count - a.count,
      );
      data.data = sortedData.slice(0, 11);

      const chrtConfig: ChartConfigType = {};

      data.data.forEach((d: GenresStatsType, idx: number) => {
        if (d.genre_name) {
          const genreName = d.genre_name === null ? "undefined" : d.genre_name;
          const sanitizedGenreName = genreName
            .toLowerCase()
            .replace(/\s+/g, "-");

          chrtConfig[sanitizedGenreName] = {
            label: d.genre_name || "Not set",
            color: `hsl(var(--chart-${idx + 1}))`,
          };
        }
      });

      chrtConfig.label = {
        color: "hsl(var(--background))",
      };

      const chrtData = data.data
        .filter((d: GenresStatsType) => d.genre_name !== null)
        .map((d: GenresStatsType) => {
          const genreName = d.genre_name === null ? "undefined" : d.genre_name;

          return {
            genre: genreName,
            count: d.count,
          };
        });

      setChrtData(chrtData);
      setChrtConfig(chrtConfig);
    } catch (error) {
      handleApiError(error, "Fetching genres stats");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      {isLoading ? (
        <TheLoader style="my-4" />
      ) : (
        <Card className="flex flex-col w-full">
          <CardHeader className="items-center pb-0">
            <CardTitle>Top 10 genres</CardTitle>
          </CardHeader>
          <CardContent className="flex-1 pb-0">
            <ChartContainer
              config={chrtConfig}
              className="mx-auto aspect-square genre-bar-chart w-full"
            >
              <BarChart
                accessibilityLayer
                data={chrtData}
                layout="vertical"
                margin={{
                  right: 16,
                }}
              >
                <CartesianGrid horizontal={false} />
                <YAxis
                  dataKey="genre"
                  type="category"
                  tickLine={false}
                  tickMargin={10}
                  axisLine={false}
                  tickFormatter={(value) => value.slice(0, 3)}
                  hide
                />
                <XAxis dataKey="count" type="number" hide />
                <ChartTooltip
                  cursor={false}
                  content={<ChartTooltipContent indicator="line" />}
                />
                <Bar
                  dataKey="count"
                  layout="vertical"
                  fill="hsl(var(--chart-2))"
                  radius={4}
                >
                  <LabelList
                    dataKey="genre"
                    position="insideLeft"
                    offset={8}
                    className="fill-background"
                    fontSize={10}
                  />
                  <LabelList
                    dataKey="count"
                    position="right"
                    offset={8}
                    className="fill-foreground"
                    fontSize={14}
                  />
                </Bar>
              </BarChart>
            </ChartContainer>
          </CardContent>
        </Card>
      )}
    </>
  );
}
