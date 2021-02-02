<?php

namespace App\Console\Commands;

use App\Client\TushareClient;
use App\Models\StockDaily;
use App\Models\TradeDate;
use Illuminate\Console\Command;

class UpdateTradeDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:tradeDate {--week}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $client = new TushareClient();
        $week = $this->option("week");
        $tradeDates = $client->tradeDates( $week ? now()->subDays(30)->format("Ymd") :"20000101", now()->format("Ymd"));
        if ($tradeDates["code"] != 0) {
            $this->warn("请求出错");
            return;
        }

        $insert_data = [];
        foreach ($tradeDates["data"]["items"] as $data) {
            $this->info($data[1]);
            if (TradeDate::where('trade_date', $data[1])->exists()) {
                continue;
            }
            $insert_data[] = [
                "trade_date" => $data[1]
            ];
        }
        TradeDate::insert($insert_data);

        $this->info("成功");
    }
}
