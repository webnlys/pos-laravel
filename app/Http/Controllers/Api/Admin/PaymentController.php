<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\PaymentService;
use App\Services\PdfService;
use App\Support\PdfFilename;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentRepository $payments,
        private PaymentService $service,
        private PdfService $pdf,
    ) {}

    public function index(Request $request)
    {
        return PaymentResource::collection($this->payments->paginate($request));
    }

    public function store(PaymentRequest $request)
    {
        return new PaymentResource($this->service->create($request->validated(), $request->user()->id));
    }

    public function show(Payment $payment)
    {
        return new PaymentResource($payment->load(['customer', 'sale']));
    }

    public function update(PaymentRequest $request, Payment $payment)
    {
        return new PaymentResource($this->service->update($payment, $request->validated()));
    }

    public function destroy(Payment $payment)
    {
        $this->service->delete($payment);

        return response()->json(['message' => 'Deleted']);
    }

    public function pdf(Payment $payment)
    {
        $binary = $this->pdf->paymentReceipt($payment);
        $filename = PdfFilename::build($payment->customer?->name, 'receipt', $payment->number);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
