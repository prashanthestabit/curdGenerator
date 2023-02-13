<?php

/**
 * Array to string conversion with "
 *
 * @return string
 */
if (!function_exists('arrayToString')) {
    function arrayToString($array)
    {
        $string = "";
        if ($array) {
            foreach ($array as $column) {
                $string .= "\"$column\",";
            }
        }

        // remove the last comma
        return rtrim($string, ",");
    }
}

/**
 * Array to string conversion with when array like"
 * array:2 [
 * "title" => "required|string"
 * "description" => "required"
 *  ]
 *
 * @return string
 */
if (!function_exists('multiDArrayToString')) {
    function multiDArrayToString($array,$seprator)
    {
        $string = '';
        if($array){
            $string = implode($seprator, array_map(function ($v, $k) {
                return sprintf("'%s' => '%s'", $k, $v);
            }, $array, array_keys($array)));
        }

        return $string;
    }
}


