<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_quotation_with_line_discount_and_tax(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create([
            'name' => 'Acme',
            'email' => 'acme@example.test',
        ]);
        $product = Product::query()->create([
            'name' => 'Widget',
            'sku' => 'WD-001',
            'sale_price' => 100,
            'cost_price' => 60,
            'stock_qty' => 10,
            'is_active' => true,
        ]);
        $tax = Tax::query()->create([
            'name' => 'VAT',
            'rate_percent' => 10,
            'effective_from' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                    'discount' => 20,
                    'tax_id' => $tax->id,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.subtotal', 200)
            ->assertJsonPath('data.discount', 20)
            ->assertJsonPath('data.tax_total', 18)
            ->assertJsonPath('data.total', 198);

        $this->assertDatabaseHas('quotations', [
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'status' => 'draft',
            'total' => 198,
        ]);
        $this->assertDatabaseHas('quotation_items', [
            'product_id' => $product->id,
            'discount' => 20,
            'tax_id' => $tax->id,
            'tax_amount' => 18,
            'line_total' => 198,
        ]);
    }

    public function test_customer_cannot_create_admin_quotations(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Walk-in',
            'email' => 'walkin@example.test',
        ]);
        $user = User::factory()->create([
            'role' => 'customer',
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($user)
            ->postJson('/api/admin/quotations', [
                'customer_id' => $customer->id,
                'items' => [['product_id' => 1, 'quantity' => 1, 'unit_price' => 10]],
            ])
            ->assertForbidden();
    }

    public function test_customer_can_create_own_quotation(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Portal User',
            'email' => 'portal@example.test',
        ]);
        $user = User::factory()->create([
            'role' => 'customer',
            'customer_id' => $customer->id,
        ]);
        $product = Product::query()->create([
            'name' => 'Cable',
            'sku' => 'CB-001',
            'sale_price' => 40,
            'cost_price' => 10,
            'stock_qty' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)->postJson('/api/customer/quotations', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 40, 'discount' => 5],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.total', 35);
    }
}
