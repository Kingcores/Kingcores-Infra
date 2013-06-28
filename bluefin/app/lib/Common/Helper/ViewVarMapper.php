<?php

namespace Common\Helper;

class ViewVarMapper
{
    protected static $__followerCountToFontStyle = [
        100000 => 'text-xxl-red',
        10000 => 'text-xl-orange',
        1000 => 'text-l-green',
        100 => 'text-m-blue',
        0 => 'text-s-grey'
    ];

    protected static $__followingCountToFontStyle = [
        500 => 'text-xxl-red',
        250 => 'text-xl-orange',
        100 => 'text-l-green',
        50 => 'text-m-blue',
        0 => 'text-s-grey'
    ];

    protected static $__postCountToFontStyle = [
        10000 => 'text-xxl-red',
        3000 => 'text-xl-orange',
        1000 => 'text-l-green',
        100 => 'text-m-blue',
        0 => 'text-s-grey'
    ];

    public static function followerToStyle($number)
    {
        foreach (self::$__followerCountToFontStyle as $bar => $class)
        {
            if ($number >= $bar) return $class;
        }

        return self::$__followerCountToFontStyle[0];
    }

    public static function followingToStyle($number)
    {
        foreach (self::$__followingCountToFontStyle as $bar => $class)
        {
            if ($number >= $bar) return $class;
        }

        return self::$__followingCountToFontStyle[0];
    }

    public static function postToStyle($number)
    {
        foreach (self::$__postCountToFontStyle as $bar => $class)
        {
            if ($number >= $bar) return $class;
        }

        return self::$__postCountToFontStyle[0];
    }
}
