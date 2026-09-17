<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestQuotationRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\QuotationResource;
use App\Http\Resources\TaxResource;
use App\Models\BusinessSetting;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Tax;
use App\Services\GuestCustomerService;
use App\Services\PdfService;
use App\Services\QuotationService;
use Illuminate\Support\Facades\URL;

class GuestController extends Controller
{
    public function business()
    {
        $settings = BusinessSetting::query()->firstOrCreate([], [
            'name' => config('app.name'),
            'currency' => config('currencies.default', 'AED'),
        ]);

        return response()->json([
            'data' => [
                'name' => $settings->name,
                'currency' => $settings->currencyCode(),
                'currencies' => config('currencies.codes'),
            ],
        ]);
    }

    public function products()
    {
        return ProductResource::collection(
            Product::query()->where('is_active', true)->orderBy('name')->get()
        );
    }

    public function taxes()
    {
        return TaxResource::collection(Tax::query()->orderBy('name')->get());
    }

    public function store(GuestQuotationRequest $request, GuestCustomerService $guests, QuotationService $quotations)
    {
        $data = $request->validated();
        $customer = $guests->resolve($data['email'], $data['phone']);

        $quotation = $quotations->create([
            'customer_id' => $customer->id,
            'document_datetime' => $data['document_datetime'] ?? null,
            'notes' => $data['notes'] ?? null,
            'items' => $data['items'],
        ], null);

        return (new QuotationResource($quotation))
            ->additional([
                'pdf_url' => URL::temporarySignedRoute(
                    'guest.quotations.pdf',
                    now()->addHour(),
                    ['quotation' => $quotation->id]
                ),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function pdf(Quotation $quotation, PdfService $pdf)
    {
        $binary = $pdf->quotation($quotation);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$quotation->number.'.pdf"',
        ]);
    }
}
