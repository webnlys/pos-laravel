<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

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
            if (! Schema::hasTable($table)) {
                $this->command?->warn("Skipping missing table [{$table}].");
                continue;
            }

            $rows = $dump[$table] ?? [];
            DB::table($table)->truncate();

            if ($rows === []) {
                continue;
            }

            $rows = array_map(fn (array $row) => $this->normalizeRow($table, $row), $rows);
            $rows = array_values(array_filter($rows, fn (array $row) => $row !== []));

            if ($rows === []) {
                continue;
            }

            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table($table)->insert($chunk);
            }

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

        if ($table === 'business_settings') {
            $row['currency'] = $this->currencyCode($row['currency'] ?? null);
        }

        if ($table === 'quotation_items') {
            $row['discount'] = $row['discount'] ?? 0;
            $row['tax_id'] = $row['tax_id'] ?? null;
            $row['tax_name'] = $row['tax_name'] ?? null;
            $row['tax_rate_percent'] = $row['tax_rate_percent'] ?? null;
            $row['tax_amount'] = $row['tax_amount'] ?? 0;
        }

        $columns = Schema::getColumnListing($table);

        return array_intersect_key($row, array_flip($columns));
    }

    private function currencyCode(mixed $value): string
    {
        $code = strtoupper(trim((string) $value));
        $allowed = array_keys(config('currencies.codes', []));

        if ($code !== '' && in_array($code, $allowed, true)) {
            return $code;
        }

        return (string) config('currencies.default', 'AED');
    }
}
