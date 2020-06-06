<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Contoller\StockSyncController;

class UpdateStockList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:stockList';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新股票列表';

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
        $ssc->syncStockList();
        
    }
}
