<?php

namespace App\Support;

class MoneyWords
{
    /** @var array<int, string> */
    private const ONES = [
        0 => 'Zero',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
    ];

    /** @var array<int, string> */
    private const TENS = [
        2 => 'Twenty',
        3 => 'Thirty',
        4 => 'Forty',
        5 => 'Fifty',
        6 => 'Sixty',
        7 => 'Seventy',
        8 => 'Eighty',
        9 => 'Ninety',
    ];

    public static function convert(mixed $amount, string $currency = 'AED'): string
    {
        $negative = (float) $amount < 0;
        $formatted = number_format(abs((float) $amount), 2, '.', '');
        [$majorRaw, $minorRaw] = array_pad(explode('.', $formatted, 2), 2, '00');
        $major = (int) $majorRaw;
        $minor = (int) $minorRaw;

        $names = config('currencies.words.'.$currency, [
            'major' => $currency,
            'major_plural' => $currency,
            'minor' => 'Fils',
            'minor_plural' => 'Fils',
        ]);

        $majorName = $major === 1 ? $names['major'] : $names['major_plural'];
        $parts = [self::integer($major).' '.$majorName];

        if ($minor > 0) {
            $minorName = $minor === 1 ? $names['minor'] : $names['minor_plural'];
            $parts[] = 'and '.self::integer($minor).' '.$minorName;
        }

        $words = implode(' ', $parts).' Only';

        return $negative ? 'Minus '.$words : $words;
    }

    public static function integer(int $number): string
    {
        if ($number === 0) {
            return self::ONES[0];
        }

        if ($number < 0) {
            return 'Minus '.self::integer(abs($number));
        }

        if ($number < 20) {
            return self::ONES[$number];
        }

        if ($number < 100) {
            $ten = intdiv($number, 10);
            $rest = $number % 10;

            return self::TENS[$ten].($rest !== 0 ? '-'.self::ONES[$rest] : '');
        }

        if ($number < 1000) {
            $hundreds = intdiv($number, 100);
            $rest = $number % 100;
            $words = self::ONES[$hundreds].' Hundred';

            return $rest !== 0 ? $words.' '.self::integer($rest) : $words;
        }

        foreach ([1_000_000_000 => 'Billion', 1_000_000 => 'Million', 1_000 => 'Thousand'] as $unit => $label) {
            if ($number >= $unit) {
                $count = intdiv($number, $unit);
                $rest = $number % $unit;
                $words = self::integer($count).' '.$label;

                return $rest !== 0 ? $words.' '.self::integer($rest) : $words;
            }
        }

        return (string) $number;
    }
}
