<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentRepository $payments,
        private DocumentNumberService $numbers,
    ) {}

    public function index(Request $request)
    {
        return PaymentResource::collection($this->payments->paginate($request));
    }

    public function store(PaymentRequest $request)
    {
        $data = $request->validated();
        $data['number'] = $this->numbers->next('PAY', Payment::class);
        $data['user_id'] = $request->user()->id;
        $data['paid_at'] = $data['paid_at'] ?? now();

        return new PaymentResource(Payment::query()->create($data)->load(['customer', 'sale']));
    }

    public function show(Payment $payment)
    {
        return new PaymentResource($payment->load(['customer', 'sale']));
    }

    public function update(PaymentRequest $request, Payment $payment)
    {
        $data = $request->validated();
        $data['paid_at'] = $data['paid_at'] ?? $payment->paid_at;
        $payment->update($data);

        return new PaymentResource($payment->fresh(['customer', 'sale']));
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
