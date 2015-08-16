<?php
/**
 * Misc methods
 *
 * @author Ardalan Samimi
 * @version 1.0
 */
namespace Twarpy\Components;

class Utility {

    /**
     * Split a query string into an array.
     *
     * @param   string  String to split.
     * @return  array
     */
    static public function splitQueryString($string) {
        $string = explode("&", $string);
        $array  = NULL;
        foreach($string as $key => $value) {
            $value = explode("=", $value);
            $array[$value[0]] = $value[1];
        }
        return $array;
    }

    /**
     * Convert a JSON string to an associative
     * array. The string will not be altered if
     * it is not JSON.
     *
     * @param   string  The JSON string to convert.
     * @return  array | string
     */
    static public function convertJSON($json) {
        $array = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE)
            return $array;
        return $json;
    }

}
?>
