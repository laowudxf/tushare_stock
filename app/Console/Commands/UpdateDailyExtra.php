<?php

namespace App\Console\Commands;

use App\Http\Controllers\StockSyncController;
use Illuminate\Console\Command;

class UpdateDailyExtra extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:updateDailyExtra {--week}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新每日基本面信息';

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
        $week = $this->option("week");
        $ssc = new StockSyncController();
        $ssc->console = $this;
        $ssc->syncStockDailyExtra($week);
    }
}
