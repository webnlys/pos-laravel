<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private DocumentNumberService $numbers,
    ) {}

    public function create(array $data, int $userId): Payment
    {
        $this->assertSaleMatchesCustomer($data);

        return DB::transaction(function () use ($data, $userId) {
            $payment = Payment::query()->create([
                'number' => $this->numbers->next('PAY', Payment::class),
                'customer_id' => $data['customer_id'],
                'sale_id' => $data['sale_id'] ?? null,
                'user_id' => $userId,
                'amount' => round((float) $data['amount'], 2),
                'method' => $data['method'] ?? 'cash',
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncSaleStatus($payment->sale_id);

            return $payment->load(['customer', 'sale']);
        });
    }

    public function update(Payment $payment, array $data): Payment
    {
        $this->assertSaleMatchesCustomer($data);

        return DB::transaction(function () use ($payment, $data) {
            $oldSaleId = $payment->sale_id;

            $payment->update([
                'customer_id' => $data['customer_id'] ?? $payment->customer_id,
                'sale_id' => array_key_exists('sale_id', $data) ? $data['sale_id'] : $payment->sale_id,
                'amount' => round((float) ($data['amount'] ?? $payment->amount), 2),
                'method' => $data['method'] ?? $payment->method,
                'paid_at' => $data['paid_at'] ?? $payment->paid_at,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $payment->notes,
            ]);

            if ($oldSaleId && (int) $oldSaleId !== (int) $payment->sale_id) {
                $this->syncSaleStatus((int) $oldSaleId);
            }

            $this->syncSaleStatus($payment->sale_id);

            return $payment->fresh(['customer', 'sale']);
        });
    }

    public function delete(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $saleId = $payment->sale_id;
            $payment->delete();
            $this->syncSaleStatus($saleId);
        });
    }

    public function syncSaleStatus(int|string|null $saleId): void
    {
        if (! $saleId) {
            return;
        }

        $sale = Sale::query()->with('payments')->find($saleId);
        if (! $sale) {
            return;
        }

        $sale->update([
            'status' => $sale->paymentSummary()['status'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertSaleMatchesCustomer(array $data): void
    {
        $saleId = $data['sale_id'] ?? null;
        $customerId = $data['customer_id'] ?? null;

        if (! $saleId || ! $customerId) {
            return;
        }

        $sale = Sale::query()->find($saleId);
        if ($sale && (int) $sale->customer_id !== (int) $customerId) {
            throw ValidationException::withMessages([
                'sale_id' => 'This invoice does not belong to the selected customer.',
            ]);
        }
    }
}
