<?php

namespace App\Console\Commands;

use App\Models\StockDaily;
use App\Models\StockTec;
use App\StockStrategies\DailyDealStrategy\DailyDealStrategy;
use App\StockStrategies\DefaultStockStrategy;
use App\StockStrategies\StockTecIndex;
use App\StockStrategies\StrategyRunContainer;
use Illuminate\Console\Command;

class DealTecIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:tecIndex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天重新生成技术指标';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $strategy = new DailyDealStrategy();
        $runner = new StrategyRunContainer(now()->subYears(2), now(), $strategy);
        $strategy->setRunContainer($runner);
        $runner->stockCodePool = $strategy->ensureStockPool();
//        $t_start = msectime();
        $runner->initData();
        $tecs = $runner->stockTecData;
        $insertDatas = [];
        StockTec::where('id', '>', 0)->delete();
        $index = 0;
        foreach ($tecs as $key => $value) {
            if (empty($value[0])) {
                continue;
            }
            $dates = array_keys($value[0]);
            foreach ($dates as $date) {
                $insertDatas[] = [
                    "ts_code" => $key,
                    "trade_date" => $date,
                    "macd" => $value[0][$date],
                    "boll_0" => $value[1][0][$date],
                    "boll_1" => $value[1][1][$date],
                    "boll_2" => $value[1][2][$date],
                    "boll_3" => $value[1][3][$date],
                    "boll_4" => $value[1][4][$date],
                    "boll_5" => $value[1][5][$date],
                ];
            }
            $this->info("正在处理第{$index}条 {$key}");
            $index += 1;
            StockTec::insert($insertDatas);
            $insertDatas = [];
        }
    }
}
