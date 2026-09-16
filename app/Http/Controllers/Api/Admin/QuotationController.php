<?php

namespace App\Http\Controllers\Api\Admin;

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
        return QuotationResource::collection($this->quotations->paginate($request));
    }

    public function store(DocumentRequest $request)
    {
        return new QuotationResource($this->service->create($request->validated(), $request->user()->id));
    }

    public function show(Quotation $quotation)
    {
        return new QuotationResource($quotation->load(['customer', 'items', 'taxes']));
    }

    public function update(DocumentRequest $request, Quotation $quotation)
    {
        return new QuotationResource($this->service->update($quotation, $request->validated()));
    }

    public function destroy(Quotation $quotation)
    {
        $this->service->delete($quotation);

        return response()->json(['message' => 'Deleted']);
    }

    public function convert(Request $request, Quotation $quotation)
    {
        return new SaleResource($this->service->convertToSale($quotation, $request->user()->id));
    }

    public function pdf(Quotation $quotation)
    {
        $binary = $this->pdf->quotation($quotation);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$quotation->number.'.pdf"',
        ]);
    }
}
