<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportGridExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int|string, mixed>>  $rows
     */
    public function __construct(
        private array $headings,
        private array $rows,
        private string $title = 'Report',
    ) {}

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return array_map(fn (array $row) => array_values($row), $this->rows);
    }

    public function title(): string
    {
        return substr($this->title, 0, 31);
    }
}
