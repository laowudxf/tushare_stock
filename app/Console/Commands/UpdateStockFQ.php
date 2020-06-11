<?php

namespace App\Console\Commands;

use App\Http\Controllers\StockSyncController;
use Illuminate\Console\Command;

class UpdateStockFQ extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:fq {--week}';

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
        $ssc = new StockSyncController();
        $ssc->syncStockFQ();
    }
}
