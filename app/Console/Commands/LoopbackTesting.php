<?php

namespace App\Console\Commands;

use App\Http\Controllers\StockController;
use Illuminate\Console\Command;

class LoopbackTesting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:lookBack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '回测';

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
        $stockController = new StockController();
        $stockController->test();
    }
}
