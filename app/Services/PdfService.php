<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Quotation;
use App\Models\Sale;
use App\Support\PdfFilename;
use Mpdf\Mpdf;

class PdfService
{
    public function sale(Sale $sale): string
    {
        $sale->load(['customer', 'items', 'taxes', 'payments']);

        return $this->render('pdf.sale', [
            'document' => $sale,
            'title' => 'Sales Invoice',
            'settings' => $this->settings(),
        ], PdfFilename::build($sale->customer?->name, 'invoice', $sale->number));
    }

    public function quotation(Quotation $quotation): string
    {
        $quotation->load(['customer', 'items', 'taxes']);

        return $this->render('pdf.quotation', [
            'document' => $quotation,
            'title' => 'Quotation',
            'settings' => $this->settings(),
        ], PdfFilename::build($quotation->customer?->name, 'quotation', $quotation->number));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function render(string $view, array $data, string $filename): string
    {
        $html = view($view, $data)->render();
        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 12,
            'margin_bottom' => 40,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_footer' => 8,
            'tempDir' => $tempDir,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output($filename, 'S');
    }

    private function settings(): BusinessSetting
    {
        return BusinessSetting::query()->first() ?? new BusinessSetting([
            'name' => config('app.name'),
            'currency' => config('currencies.default', 'AED'),
            'tagline' => 'Thank you for your business!',
        ]);
    }
}
