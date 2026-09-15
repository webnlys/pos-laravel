<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class DocumentNumberService
{
    public function next(string $prefix, string $modelClass): string
    {
        /** @var class-string<Model> $modelClass */
        $last = $modelClass::query()->orderByDesc('id')->value('number');
        $seq = 1;

        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
