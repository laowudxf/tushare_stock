<?php

namespace App\Console\Commands;

use App\Http\Controllers\UpdateController;
use App\Models\Stock;
use App\Models\StockDaily;
use App\Models\StockWeek;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateStockWeek extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:stockWeek {--week} {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成周线';

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
        //
        $date = $this->option("date");
        $isWeek = $this->option("week");
        $updateController->generatorWeekStock($date, $isWeek, $this);
    }

}
