<?php

namespace App;

use Illuminate\Support\Facades\Log;

class Debug extends Log {
    public static function lstr($v) {
        if (config('app.debug')) {
            ob_start();
            var_dump($v);
            $str = ob_get_clean();
            return $str;
        }
        return null;
        /*
        $type = gettype($v);
        $str = $type;
        if ('integer' == $type || 'double' == $type)
            $str .= '('.strval($v).')';
        else if ('boolean' == $type)
            $str .= '('.boolval($v).')';
        else if ('array' == $type)
            $str .= ', length='.count($v);
        return $str;
        */
    }

    public static function getPrefix($sfile, $iline, $sfunc)
    {
        if (!config('app.debug')) return '';
        $prefix = '<'.getmypid().'>'.'['.$sfile.':'.$iline.':'.$sfunc.'] ';
        return $prefix;
    }

    public static function out($sfile, $iline, $sfunc, $out) {
        if (!config('app.debug')) return;
        $prefix = self::getPrefix($sfile, $iline, $sfunc);
        if (is_string($out))
            self::debug($prefix.$out);
        else
            self::debug($prefix.self::lstr($out));
    }
}
