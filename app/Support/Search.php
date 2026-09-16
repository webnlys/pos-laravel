<?php

namespace App\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class Search
{
    public static function likeOperator(?ConnectionInterface $connection = null): string
    {
        $driver = ($connection ?? DB::connection())->getDriverName();

        return $driver === 'pgsql' ? 'ilike' : 'like';
    }
}
