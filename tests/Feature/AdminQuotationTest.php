<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_quotation(): void
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

        $response = $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'discount' => 0,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('quotations', [
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'status' => 'draft',
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
}
