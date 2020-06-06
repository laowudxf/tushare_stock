<?php


namespace App\Traits;


use Illuminate\Support\Facades\Storage;

trait TraitHelper
{

    function getImagePath($img) {
       return Storage::url($img);
    }

    function getImagesPath($imgArr) {
        $result = [];
        foreach ($imgArr as $k => $img) {
            $result[] = Storage::url($img);
        }
        return $result;
    }
}
