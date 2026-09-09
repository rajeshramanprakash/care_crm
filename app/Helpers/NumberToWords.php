<?php

namespace App\Helpers;

class NumberToWords
{
    protected static $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
    protected static $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
    protected static $teens = ['ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];

    /**
     * Convert number to words (Indian style, for rupees).
     * Supports 0 to 99,99,99,999 (crores).
     */
    public static function toWords($num)
    {
        $num = (int) round($num);
        if ($num === 0) {
            return 'zero';
        }
        if ($num < 0) {
            return 'minus ' . self::toWords(-$num);
        }
        $crores = (int) floor($num / 10000000);
        $num %= 10000000;
        $lakhs = (int) floor($num / 100000);
        $num %= 100000;
        $thousands = (int) floor($num / 1000);
        $num %= 1000;
        $hundreds = (int) floor($num / 100);
        $num %= 100;

        $parts = [];
        if ($crores > 0) {
            $parts[] = self::toWordsLessThan1000($crores) . ' crore';
        }
        if ($lakhs > 0) {
            $parts[] = self::toWordsLessThan1000($lakhs) . ' lakh';
        }
        if ($thousands > 0) {
            $parts[] = self::toWordsLessThan1000($thousands) . ' thousand';
        }
        if ($hundreds > 0) {
            $parts[] = self::toWordsLessThan1000($hundreds) . ' hundred';
        }
        if ($num > 0) {
            $parts[] = self::toWordsLessThan100($num);
        }

        return implode(' ', $parts);
    }

    protected static function toWordsLessThan1000($n)
    {
        $n = (int) $n;
        if ($n >= 100) {
            $h = (int) floor($n / 100);
            $r = $n % 100;
            return self::$ones[$h] . ' hundred' . ($r > 0 ? ' ' . self::toWordsLessThan100($r) : '');
        }
        return self::toWordsLessThan100($n);
    }

    protected static function toWordsLessThan100($n)
    {
        $n = (int) $n;
        if ($n < 10) {
            return self::$ones[$n];
        }
        if ($n < 20) {
            return self::$teens[$n - 10];
        }
        $t = (int) floor($n / 10);
        $o = $n % 10;
        return self::$tens[$t] . ($o > 0 ? ' ' . self::$ones[$o] : '');
    }
}
