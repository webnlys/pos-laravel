<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuotationResource;
use App\Models\Quotation;
use App\Repositories\QuotationRepository;
use App\Services\PdfService;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function __construct(
        private QuotationRepository $quotations,
        private PdfService $pdf,
    ) {}

    public function index(Request $request)
    {
        return QuotationResource::collection($this->quotations->paginate($request));
    }

    public function show(Quotation $quotation)
    {
        return new QuotationResource($quotation->load(['customer', 'items', 'taxes']));
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
