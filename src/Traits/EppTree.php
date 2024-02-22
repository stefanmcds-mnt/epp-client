<?php

namespace EppClient\Traits;

trait EppTree
{

    /**
     * Tree array or object static function
     *
     * @param mixed $var
     * @return array
     */
    static public function _Tree(mixed $var)
    {
        if (is_object($var)) {
            $var = json_decode(json_encode($var), TRUE);
        }
        $array = [];
        foreach ($var as $key => $value) {
            if (is_array($value) && is_object($value)) {
                self::_Tree($value);
            }
            if (stristr($key, ':')) {
                $key = explode(':', $key);
                $key = end($key);
            }
            $array[$key] = $value;
        }
        return $array;
    }

    /**
     * Tree array or object static function
     *
     * @param mixed $var
     * @return array
     */
    public function Tree(mixed $var)
    {
        if (is_object($var)) {
            $var = json_decode(json_encode($var), TRUE);
        }
        $array = [];
        foreach ($var as $key => $value) {
            if (is_array($value) && is_object($value)) {
                $this->Tree($value);
            }
            if (stristr($key, ':')) {
                $key = explode(':', $key);
                $key = end($key);
            }
            $array[$key] = $value;
        }
        return $array;
    }
}
