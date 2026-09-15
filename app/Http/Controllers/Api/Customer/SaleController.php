<?php

namespace App\Http\Controllers\Api\Customer;

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
        $request->merge(['customer_id' => $request->user()->customer_id]);

        return SaleResource::collection($this->sales->paginate($request));
    }

    public function store(DocumentRequest $request)
    {
        return new SaleResource($this->service->create($request->validated(), $request->user()->id));
    }

    public function show(Request $request, Sale $sale)
    {
        $this->authorizeCustomer($request, $sale);

        return new SaleResource($sale->load(['customer', 'items', 'taxes', 'payments']));
    }

    public function update(DocumentRequest $request, Sale $sale)
    {
        $this->authorizeCustomer($request, $sale);

        return new SaleResource($this->service->update($sale, $request->validated()));
    }

    public function destroy(Request $request, Sale $sale)
    {
        $this->authorizeCustomer($request, $sale);
        $this->service->delete($sale);

        return response()->json(['message' => 'Deleted']);
    }

    public function pdf(Request $request, Sale $sale)
    {
        $this->authorizeCustomer($request, $sale);
        $binary = $this->pdf->sale($sale);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$sale->number.'.pdf"',
        ]);
    }

    private function authorizeCustomer(Request $request, Sale $sale): void
    {
        abort_unless($sale->customer_id === $request->user()->customer_id, 403);
    }
}
