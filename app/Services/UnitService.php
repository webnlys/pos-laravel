<?php

namespace App\Services;

use App\Models\Unit;

class UnitService
{
    public function resolve(?int $unitId, ?string $unitName): ?Unit
    {
        if ($unitId) {
            $unit = Unit::query()->find($unitId);
            if ($unit) {
                return $unit;
            }
        }

        $name = trim((string) $unitName);
        if ($name === '') {
            return null;
        }

        $existing = Unit::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            return $existing;
        }

        return Unit::query()->create(['name' => $name]);
    }
}
