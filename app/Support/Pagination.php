<?php

namespace App\Support;

use Illuminate\Http\Request;

class Pagination
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    /**
     * @var array<int, int>
     */
    public const ALLOWED_PER_PAGE = [10, 15, 25, 50, 100];

    public static function perPage(Request $request, int $default = self::DEFAULT_PER_PAGE): int
    {
        $value = $request->integer('per_page', $default);

        if (in_array($value, self::ALLOWED_PER_PAGE, true)) {
            return $value;
        }

        if ($value < 1) {
            return $default;
        }

        return min($value, self::MAX_PER_PAGE);
    }

    public static function page(Request $request): int
    {
        return max(1, $request->integer('page', 1));
    }
}
