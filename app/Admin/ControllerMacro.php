<?php


namespace App\Admin;


class ControllerMacro
{
    const STATUS = [
        'on' => ['value' => 1, 'text' => '上架', 'color' => 'success'],
        'off' => ['value' => 0, 'text' => '下架', 'color' => 'danger'],
    ];

    const SITESTATUS = [
        'on' => ['value' => 1, 'text' => '通过', 'color' => 'success'],
        'off' => ['value' => 0, 'text' => '未通过', 'color' => 'danger'],
    ];

    const WITHDRAW_STATUS = [
        "审核中",
        "通过",
        "驳回",
    ];

}
