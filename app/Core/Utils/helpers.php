<?php

use App\Core\Utils\Collection;
use App\Core\Utils\Env;
if (!function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed   $target
     * @param  string|array  $key
     * @param  mixed   $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $segment) {
            if (is_array($target)) {
                if (!array_key_exists($segment, $target)) {
                    return value($default);
                }

                $target = $target[$segment];
            } elseif ($target instanceof ArrayAccess) {
                if (!isset($target[$segment])) {
                    return value($default);
                }

                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (!isset($target->{$segment})) {
                    return value($default);
                }

                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function dd()
    {
        array_map(function ($x) {
            var_dump($x);
            // (new Dumper)->dump($x);
        }, func_get_args());

        die(1);
    }
}

if (!function_exists('collect')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function collect($array = [])
    {
        return new Collection($array);
    }
}

if (!function_exists('url')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function url($url = "")
    {
        if (isset($_SERVER)) {

            $http    = $_SERVER['REQUEST_SCHEME'];
            $host    = $_SERVER["SERVER_NAME"];
            $port    = $_SERVER["SERVER_PORT"] != 80 ? ":" . $_SERVER["SERVER_PORT"] : "";
            $urlbase = $_SERVER["REQUEST_URI"];
            if (isset($_SERVER["PATH_INFO"])) {
                $urlbase = strtr($urlbase, [$_SERVER["PATH_INFO"] => ""]);
            }
            if ($url != "") {
                if (strlen($url) > 0 and substr($url, 0, 1) == "/") {
                    $url = substr($url, 1);
                }
            }
            return "{$http}://{$host}{$port}{$urlbase}{$url}";
        }
        return null;
    }
}

if (!function_exists('object_get')) {
    /**
     * Get an item from an object using "dot" notation.
     *
     * @param  object  $object
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function object_get($object, $key, $default = null)
    {
        if (is_null($key) || trim($key) == '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_object($object) || !isset($object->{$segment})) {
                return value($default);
            }

            $object = $object->{$segment};
        }

        return $object;
    }
}

if (!function_exists('time_elapsed')) {
    function time_elapsed($secs)
    {
        $bit = array(
            'y' => $secs / 31556926 % 12,
            'w' => $secs / 604800 % 52,
            'd' => $secs / 86400 % 7,
            'h' => $secs / 3600 % 24,
            'm' => $secs / 60 % 60,
            's' => $secs % 60,
        );
        $ret = [];
        foreach ($bit as $k => $v) {
            if ($v > 0) {
                $ret[] = $v . $k;
            }
        }

        return join(' ', $ret);
    }
}
if (!function_exists('env')) {
    function env()
    {
        return Env::getData();
    }
}
if (!function_exists('value')) {
    function value($arg)
    {
        return $arg;
    }
}
