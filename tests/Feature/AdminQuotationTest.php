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

    public function test_admin_can_create_quotation_with_typed_new_product_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Walk-in']);

        $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_name' => 'Custom curtain',
                    'quantity' => 1,
                    'unit_price' => 75,
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.total', 75);

        $this->assertDatabaseHas('products', [
            'name' => 'Custom curtain',
            'sale_price' => 75,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('quotation_items', [
            'product_name' => 'Custom curtain',
            'unit_price' => 75,
        ]);
    }

    public function test_typed_product_name_reuses_existing_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Acme']);
        $product = Product::query()->create([
            'name' => 'SALA DOWN',
            'sku' => 'SD-001',
            'sale_price' => 4500,
            'cost_price' => 3200,
            'stock_qty' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_name' => 'sala down',
                    'quantity' => 1,
                    'unit_price' => 4300,
                ],
            ],
        ])->assertCreated();

        $this->assertEquals(1, Product::query()->count());
        $this->assertDatabaseHas('quotation_items', [
            'product_id' => $product->id,
            'product_name' => 'SALA DOWN',
            'unit_price' => 4300,
        ]);
    }

    public function test_non_admin_cannot_create_admin_quotations(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)
            ->postJson('/api/admin/quotations', [
                'customer_id' => 1,
                'items' => [['product_name' => 'X', 'quantity' => 1, 'unit_price' => 10]],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_create_customer_with_name_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::query()->create([
            'name' => 'Widget',
            'sku' => 'WD-002',
            'sale_price' => 80,
            'cost_price' => 40,
            'stock_qty' => 5,
            'is_active' => true,
        ]);

        $customerResponse = $this->actingAs($admin)->postJson('/api/admin/customers', [
            'name' => 'Walk-in Buyer',
            'phone' => '01900000000',
            'email' => 'walkin.buyer@example.test',
            'address' => 'Dhaka',
        ]);

        $customerResponse->assertCreated()
            ->assertJsonPath('data.name', 'Walk-in Buyer');

        $this->assertDatabaseMissing('users', [
            'email' => 'walkin.buyer@example.test',
        ]);

        $nameOnly = $this->actingAs($admin)->postJson('/api/admin/customers', [
            'name' => 'Cash Customer',
        ]);

        $nameOnly->assertCreated()
            ->assertJsonPath('data.name', 'Cash Customer');

        $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customerResponse->json('data.id'),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 80],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.customer_id', $customerResponse->json('data.id'));
    }

    public function test_customer_login_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'old.customer@example.test',
            'password' => 'password',
            'role' => 'customer',
        ]);

        $this->postJson('/api/login', [
            'email' => 'old.customer@example.test',
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }
}
