<?php


namespace App\Repository\Facade\RDS;


class ResponseDataStruct
{
    function success($data = [], $message = "ok"):array {
//        if (empty($data) && $islist == false) {
//            $data = (object)[];
//        }
        if ($data == null) {
            $data = (object)[];
        }

        return [
            "code" => 0,
            "message" => $message,
            "data" => $data,
        ];
    }

    function fail($code = -1, $message = "fail"):array {
        return [
            "code" => $code,
            "message" => $message,
            "data" => (object)[],
        ];
    }
}
