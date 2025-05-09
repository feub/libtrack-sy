import { useEffect, useMemo, useState } from "react";
import { validateApiResponse, handleApiError } from "@/utils/errorHandling";
import { api } from "@/utils/apiRequest";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
  ChartLegend,
  ChartLegendContent,
} from "@/components/ui/chart";
import { Label, Pie, PieChart } from "recharts";
import TheLoader from "@/components/TheLoader";

const apiURL = import.meta.env.VITE_API_URL;

type ChartConfigType = {
  [key: string]: {
    label: string;
    color: string;
  };
};

type FormatsStatsType = {
  format_id: number;
  format_name: string;
  count: number;
};

export default function FormatStatsCard() {
  const [chrtData, setChrtData] = useState<Array<FormatsStatsType>>([]);
  const [chrtConfig, setChrtConfig] = useState<ChartConfigType>({});
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    getStats();
  }, []);

  const getStats = async () => {
    setIsLoading(true);
    try {
      const response = await api.get(`${apiURL}/api/stats/formats`);

      const data = await validateApiResponse(
        response,
        "Fetching formats stats",
      );

      const chrtConfig: ChartConfigType = {};
      data.data.forEach((d: FormatsStatsType, idx: number) => {
        const formatName = d.format_name === null ? "undefined" : d.format_name;

        chrtConfig[formatName.toLowerCase()] = {
          label: d.format_name || "Not set",
          color: `hsl(var(--chart-${idx + 1}))`,
        };
      });

      const chrtData = data.data.map((d: FormatsStatsType) => {
        const formatName = d.format_name === null ? "undefined" : d.format_name;

        return {
          format: formatName.toLowerCase(),
          count: d.count,
          fill: `var(--color-${formatName.toLowerCase()})`,
        };
      });

      setChrtData(chrtData);
      setChrtConfig(chrtConfig);
    } catch (error) {
      handleApiError(error, "Fetching formats stats");
    } finally {
      setIsLoading(false);
    }
  };

  const totalCount = useMemo(() => {
    return chrtData.reduce((acc, curr) => acc + curr.count, 0);
  }, [chrtData]);

  return (
    <>
      {isLoading ? (
        <TheLoader style="my-4" />
      ) : (
        <Card className="flex flex-col">
          <CardHeader className="items-center pb-0">
            <CardTitle>Number of releases by formats</CardTitle>
          </CardHeader>
          <CardContent className="flex-1 pb-0">
            <ChartContainer
              config={chrtConfig}
              className="mx-auto aspect-square max-h-[250px]"
            >
              <PieChart>
                <ChartTooltip
                  cursor={false}
                  content={<ChartTooltipContent hideLabel />}
                />
                <Pie
                  data={chrtData}
                  dataKey="count"
                  nameKey="format"
                  innerRadius={60}
                  strokeWidth={5}
                >
                  <Label
                    content={({ viewBox }) => {
                      if (viewBox && "cx" in viewBox && "cy" in viewBox) {
                        return (
                          <text
                            x={viewBox.cx}
                            y={viewBox.cy}
                            textAnchor="middle"
                            dominantBaseline="middle"
                          >
                            <tspan
                              x={viewBox.cx}
                              y={viewBox.cy}
                              className="fill-foreground text-3xl font-bold"
                            >
                              {totalCount.toLocaleString()}
                            </tspan>
                            <tspan
                              x={viewBox.cx}
                              y={(viewBox.cy || 0) + 24}
                              className="fill-muted-foreground"
                            >
                              releases
                            </tspan>
                          </text>
                        );
                      }
                    }}
                  />
                </Pie>
                <ChartLegend
                  content={<ChartLegendContent nameKey="format" />}
                  className="-translate-y-2 flex-wrap gap-2 [&>*]:basis-1/4 [&>*]:justify-center"
                />
              </PieChart>
            </ChartContainer>
          </CardContent>
        </Card>
      )}
    </>
  );
}
