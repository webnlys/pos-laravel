<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Repositories\SaleRepository;
use App\Services\PdfService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        private SaleRepository $sales,
        private SaleService $service,
        private PdfService $pdf,
    ) {}

    public function index(Request $request)
    {
        return SaleResource::collection($this->sales->paginate($request));
    }

    public function store(DocumentRequest $request)
    {
        $sale = $this->service->create($request->validated(), $request->user()->id);

        return new SaleResource($sale);
    }

    public function show(Sale $sale)
    {
        return new SaleResource($sale->load(['customer', 'items', 'taxes', 'payments']));
    }

    public function update(DocumentRequest $request, Sale $sale)
    {
        return new SaleResource($this->service->update($sale, $request->validated(), $request->user()->id));
    }

    public function destroy(Sale $sale)
    {
        $this->service->delete($sale);

        return response()->json(['message' => 'Deleted']);
    }

    public function pdf(Sale $sale)
    {
        $binary = $this->pdf->sale($sale);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$sale->number.'.pdf"',
        ]);
    }
}
