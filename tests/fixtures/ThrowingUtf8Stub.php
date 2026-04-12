<?php

namespace voku\helper;

final class UTF8
{
    public static function file_get_contents(string $filename)
    {
        return \file_get_contents($filename);
    }

    public static function rawurldecode(string $content, bool $_multiDecodeNewHtmlEntity): string
    {
        // Match the ValueError raised by portable-utf8 from inside rawurldecode().
        throw new \ValueError('mb_decode_numericentity(): Argument #2 ($map) must have a multiple of 4 elements');
    }
}
