<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
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

    public function test_quotation_pdf_download_filename_includes_customer_name_and_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Demo Customer']);
        $product = Product::query()->create([
            'name' => 'Widget',
            'sku' => 'WD-PDF',
            'sale_price' => 100,
            'cost_price' => 60,
            'stock_qty' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ])->assertCreated();

        $quotation = Quotation::query()->first();

        $response = $this->actingAs($admin)->get("/api/admin/quotations/{$quotation->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString(
            'filename="Demo_Customer-quotation-'.$quotation->number.'.pdf"',
            $response->headers->get('Content-Disposition'),
        );
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
            'product_name' => 'SALA DOWN',
            'unit_price' => 4300,
        ]);
    }

    public function test_admin_can_create_unit_and_attach_it_to_product_and_quotation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/admin/units', [
            'name' => 'Piece',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Piece');

        $this->actingAs($admin)->postJson('/api/admin/units', [
            'name' => 'piece',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Piece');

        $this->assertEquals(1, \App\Models\Unit::query()->count());

        $productResponse = $this->actingAs($admin)->postJson('/api/admin/products', [
            'name' => 'Oil',
            'sku' => 'OIL-1',
            'unit_name' => 'Liter',
            'sale_price' => 20,
        ]);

        $productResponse->assertCreated()
            ->assertJsonPath('data.unit_name', 'Liter');

        $this->assertDatabaseHas('units', ['name' => 'Liter']);

        $customer = Customer::query()->create(['name' => 'Buyer']);

        $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $productResponse->json('data.id'),
                    'unit_name' => 'Liter',
                    'quantity' => 2,
                    'unit_price' => 20,
                ],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('quotation_items', [
            'product_name' => 'Oil',
            'unit_name' => 'Liter',
            'quantity' => 2,
        ]);
    }

    public function test_typed_unit_on_quotation_line_is_created(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Buyer']);

        $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_name' => 'Milk',
                    'unit_name' => 'Liter',
                    'quantity' => 1,
                    'unit_price' => 12,
                ],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('units', ['name' => 'Liter']);
        $this->assertDatabaseHas('products', ['name' => 'Milk']);
        $this->assertDatabaseHas('quotation_items', [
            'product_name' => 'Milk',
            'unit_name' => 'Liter',
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

    public function test_admin_list_endpoints_paginate_and_cap_per_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 1; $i <= 16; $i++) {
            Product::query()->create([
                'name' => "Item {$i}",
                'sku' => 'SKU-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'sale_price' => 10,
                'cost_price' => 5,
                'stock_qty' => 0,
                'is_active' => true,
            ]);
        }

        $this->actingAs($admin)->getJson('/api/admin/products?per_page=10&page=2')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonCount(6, 'data');

        $this->actingAs($admin)->getJson('/api/admin/products?per_page=500')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonCount(16, 'data');

        $this->actingAs($admin)->getJson('/api/admin/customers?page=1&per_page=15')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to']]);
    }
}
