<?php

namespace HS\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CacheFiltersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filters:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate filter cache times based on filter performance data';

    /**
     * @var Closure
     */
    protected $justFilterIds;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->justFilterIds = function ($item) {
            return $item->xFilter;
        };
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Quote the table name for postgres.
        $usage = DB::table('HS_Filter_Performance')
            ->select(['HS_Filter_Performance.xFilter', DB::raw('count(HS_Filter_Performance.xFilter) as view_count')])
            ->join('HS_Filters', 'HS_Filters.xFilter', '=', 'HS_Filter_Performance.xFilter')
            ->where('HS_Filter_Performance.sType', 'view')
            ->where('HS_Filters.fShowCount', 1)
            ->groupBy('HS_Filter_Performance.xFilter')
            ->orderBy('view_count', 'asc')
            ->get();

        $usage = new Collection($usage);

        if ($usage->count() < 10) {
            return $this->setCacheTime($usage->map($this->justFilterIds)->toArray() /* , $minutes=5 */);
        }

        $segmented = $this->segmentFilters($usage);

        $this->setCacheTime($segmented['95'], 5);
        $this->setCacheTime($segmented['75'], 10);
        $this->setCacheTime($segmented['50'], 30);
        $this->setCacheTime($segmented['0'], 60);
    }

    /**
     * Divide filters into segments by view count, via percentiles
     * An array [percentile1 => [xFilter1, ...], percentile2 => [xFilter4, ...]]
     *   e.g. [0 => [1,2,3], 50 => [4,5,6], 75 => [7,8,9], 95 => [10,11,12] ].
     * @link https://www.emathhelp.net/calculators/probability-statistics/percentile-calculator/
     * @param Collection $usage
     * @return array
     */
    protected function segmentFilters($usage)
    {
        $n = $usage->count();

        $p95 = $this->percentile($n, 95);
        $p75 = $this->percentile($n, 75);
        $p50 = $this->percentile($n, 50);

        return [
            '95' => $usage->slice($p95)->map($this->justFilterIds)->toArray(), // 95 - 100
            '75' => $usage->slice($p75, $p95 - $p75)->map($this->justFilterIds)->toArray(), // 75-94
            '50' => $usage->slice($p50, $p75 - $p50)->map($this->justFilterIds)->toArray(), // 50-74
            '0'  => $usage->slice(0, $p50)->map($this->justFilterIds)->toArray(), // 0-49
        ];
    }

    /**
     * Calculate the percentile.
     * Returns the array index (position) of the item in that percentile.
     * @param int $count
     * @param int $percentile
     * @return float|int
     */
    protected function percentile($count, $percentile)
    {
        return ceil(($percentile / 100) * $count) - 1;
    }

    /**
     * Update filters to include filter cache time in minutes.
     * @param $xFilters
     * @param int $minutes
     * @return bool
     */
    protected function setCacheTime($xFilters, $minutes = 5)
    {
        if (count($xFilters)) {
            return DB::table('HS_Filters')
                ->whereIn('xFilter', $xFilters)
                ->update(['iCachedMinutes' => $minutes]);
        }

        return true;
    }
}
