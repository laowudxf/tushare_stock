<?php


namespace App\Repository\Facade\RDS;


use Illuminate\Support\Facades\Facade;

class RDS extends Facade
{


    protected static function getFacadeAccessor()
    {
        return "App\Repository\Facade\RDS\ResponseDataStruct";
    }
}
