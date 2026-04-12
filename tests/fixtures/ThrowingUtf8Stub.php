<?php

namespace voku\helper;

final class UTF8
{
    public static function file_get_contents(string $filename)
    {
        return \file_get_contents($filename);
    }

    public static function rawurldecode(string $content, bool $multiDecodeNewHtmlEntity): string
    {
        throw new \ValueError('mb_decode_numericentity(): Argument #2 ($map) must have a multiple of 4 elements');
    }
}
