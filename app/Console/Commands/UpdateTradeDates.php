<?php

namespace App\Console\Commands;

use App\Client\TushareClient;
use App\Http\Controllers\UpdateController;
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
    public function handle(UpdateController $updateController)
    {
        $week = $this->option("week");
        $updateController->updateTradeDate($week);
        $this->info("成功");
    }

}
