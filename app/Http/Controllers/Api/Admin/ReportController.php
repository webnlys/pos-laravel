<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\ReportGridExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function purchase(Request $request)
    {
        return $this->respond($request, $this->reports->purchase($request), 'Purchase-Report');
    }

    public function sales(Request $request)
    {
        return $this->respond($request, $this->reports->sales($request), 'Sales-Report');
    }

    public function invoiceProfitLoss(Request $request)
    {
        return $this->respond($request, $this->reports->invoiceProfitLoss($request), 'Invoice-Profit-Loss');
    }

    public function itemProfitLoss(Request $request)
    {
        return $this->respond($request, $this->reports->itemProfitLoss($request), 'Item-Profit-Loss');
    }

    public function totalProfitLoss(Request $request)
    {
        return $this->respond($request, $this->reports->totalProfitLoss($request), 'Total-Profit-Loss');
    }

    public function productList(Request $request)
    {
        return $this->respond($request, $this->reports->productList($request), 'Product-List');
    }

    public function stockSummary(Request $request)
    {
        return $this->respond($request, $this->reports->stockSummary($request), 'Stock-Summary');
    }

    public function valuation(Request $request)
    {
        return $this->respond($request, $this->reports->valuation($request), 'Inventory-Valuation');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function respond(Request $request, array $payload, string $filename)
    {
        $type = $request->string('exportType')->toString();

        if (in_array($type, ['csv', 'xlsx'], true)) {
            return $this->download($payload, $filename, $type);
        }

        return response()->json($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function download(array $payload, string $filename, string $type): BinaryFileResponse
    {
        $name = $filename.'-'.now()->format('d-m-Y').'.'.$type;

        return Excel::download(
            new ReportGridExport($payload['columns'], $payload['rows'], $payload['title']),
            $name,
            $type === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX,
        );
    }
}
