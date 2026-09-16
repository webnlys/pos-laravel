<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportSupabaseDumpSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/supabase_dump.json');

        if (! File::exists($path)) {
            $this->command?->warn('No storage/app/supabase_dump.json found; skipping import.');

            return;
        }

        /** @var array<string, list<array<string, mixed>>> $dump */
        $dump = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        $tables = [
            'customers',
            'users',
            'suppliers',
            'products',
            'taxes',
            'business_settings',
            'quotations',
            'quotation_items',
            'quotation_taxes',
            'sales',
            'sale_items',
            'sale_taxes',
            'purchases',
            'purchase_items',
            'payments',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $rows = $dump[$table] ?? [];
            DB::table($table)->truncate();

            if ($rows === []) {
                continue;
            }

            $rows = array_map(fn (array $row) => $this->normalizeRow($table, $row), $rows);
            DB::table($table)->insert($rows);

            $maxId = (int) DB::table($table)->max('id');
            DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = ".($maxId + 1));
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(string $table, array $row): array
    {
        if ($table === 'products' && array_key_exists('is_active', $row)) {
            $row['is_active'] = $row['is_active'] ? 1 : 0;
        }

        return $row;
    }
}
