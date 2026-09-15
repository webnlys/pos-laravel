<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentRequest;
use App\Http\Resources\QuotationResource;
use App\Http\Resources\SaleResource;
use App\Models\Quotation;
use App\Repositories\QuotationRepository;
use App\Services\PdfService;
use App\Services\QuotationService;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function __construct(
        private QuotationRepository $quotations,
        private QuotationService $service,
        private PdfService $pdf,
    ) {}

    public function index(Request $request)
    {
        $request->merge(['customer_id' => $request->user()->customer_id]);

        return QuotationResource::collection($this->quotations->paginate($request));
    }

    public function store(DocumentRequest $request)
    {
        return new QuotationResource($this->service->create($request->validated(), $request->user()->id));
    }

    public function show(Request $request, Quotation $quotation)
    {
        $this->authorizeCustomer($request, $quotation);

        return new QuotationResource($quotation->load(['customer', 'items', 'taxes']));
    }

    public function update(DocumentRequest $request, Quotation $quotation)
    {
        $this->authorizeCustomer($request, $quotation);

        return new QuotationResource($this->service->update($quotation, $request->validated()));
    }

    public function destroy(Request $request, Quotation $quotation)
    {
        $this->authorizeCustomer($request, $quotation);
        $this->service->delete($quotation);

        return response()->json(['message' => 'Deleted']);
    }

    public function convert(Request $request, Quotation $quotation)
    {
        $this->authorizeCustomer($request, $quotation);

        return new SaleResource($this->service->convertToSale($quotation, $request->user()->id));
    }

    public function pdf(Request $request, Quotation $quotation)
    {
        $this->authorizeCustomer($request, $quotation);
        $binary = $this->pdf->quotation($quotation);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$quotation->number.'.pdf"',
        ]);
    }

    private function authorizeCustomer(Request $request, Quotation $quotation): void
    {
        abort_unless($quotation->customer_id === $request->user()->customer_id, 403);
    }
}
