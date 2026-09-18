<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_sale_with_line_discount_and_tax_without_changing_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Acme']);
        $product = Product::query()->create([
            'name' => 'Widget',
            'sku' => 'WD-SALE-001',
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

        $this->actingAs($admin)->postJson('/api/admin/sales', [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                    'discount' => 20,
                    'tax_id' => $tax->id,
                    'unit_name' => 'Piece',
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.status', 'unpaid')
            ->assertJsonPath('data.paid', 0)
            ->assertJsonPath('data.due', 198)
            ->assertJsonPath('data.advance', 0)
            ->assertJsonPath('data.subtotal', 200)
            ->assertJsonPath('data.discount', 20)
            ->assertJsonPath('data.tax_total', 18)
            ->assertJsonPath('data.total', 198);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'discount' => 20,
            'tax_id' => $tax->id,
            'tax_amount' => 18,
            'unit_name' => 'Piece',
            'line_total' => 198,
        ]);
        $this->assertEquals(10, $product->fresh()->stock_qty);
    }

    public function test_admin_can_convert_quotation_to_sale_and_keep_line_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Acme']);
        $product = Product::query()->create([
            'name' => 'Widget',
            'sku' => 'WD-CONV-001',
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

        $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'notes' => 'From quotation',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                    'discount' => 20,
                    'tax_id' => $tax->id,
                    'unit_name' => 'Piece',
                ],
            ],
        ])->assertCreated();

        $quotation = Quotation::query()->first();

        $response = $this->actingAs($admin)->postJson("/api/admin/quotations/{$quotation->id}/convert");

        $response->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.quotation_id', $quotation->id)
            ->assertJsonPath('data.notes', 'From quotation')
            ->assertJsonPath('data.subtotal', 200)
            ->assertJsonPath('data.discount', 20)
            ->assertJsonPath('data.tax_total', 18)
            ->assertJsonPath('data.total', 198)
            ->assertJsonPath('data.status', 'unpaid')
            ->assertJsonPath('data.paid', 0)
            ->assertJsonPath('data.due', 198);

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'converted',
            'converted_sale_id' => $response->json('data.id'),
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $response->json('data.id'),
            'product_id' => $product->id,
            'discount' => 20,
            'tax_id' => $tax->id,
            'tax_amount' => 18,
            'unit_name' => 'Piece',
            'line_total' => 198,
        ]);
        $this->assertEquals(10, $product->fresh()->stock_qty);

        $this->actingAs($admin)->postJson("/api/admin/quotations/{$quotation->id}/convert")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quotation');
    }

    public function test_sale_pdf_html_uses_sales_invoice_title_and_quotation_layout(): void
    {
        BusinessSetting::query()->create([
            'name' => 'Desert Shop',
            'currency' => 'AED',
            'invoice_terms' => 'Payment is due within 14 days.',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Walk-in']);
        $product = Product::query()->create([
            'name' => 'Panel',
            'sku' => 'PN-SALE',
            'sale_price' => 10,
            'cost_price' => 5,
            'stock_qty' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->postJson('/api/admin/sales', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10],
            ],
        ])->assertCreated();

        $sale = Sale::query()->first();
        $html = view('pdf.sale', [
            'document' => $sale->load(['customer', 'items', 'taxes', 'payments']),
            'title' => 'Sales Invoice',
            'settings' => BusinessSetting::query()->first(),
        ])->render();

        $this->assertStringContainsString('Sales Invoice', $html);
        $this->assertStringNotContainsString('Quotation', $html);
        $this->assertStringContainsString('Invoice No:', $html);
        $this->assertStringContainsString('Paid', $html);
        $this->assertStringContainsString('Due', $html);
        $this->assertStringContainsString('Terms and Conditions', $html);
        $this->assertStringContainsString('<li>Payment is due within 14 days.</li>', $html);
    }

    public function test_sale_payments_support_paid_partial_and_advance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Acme']);
        $product = Product::query()->create([
            'name' => 'Widget',
            'sku' => 'WD-PAY-001',
            'sale_price' => 100,
            'cost_price' => 60,
            'stock_qty' => 10,
            'is_active' => true,
        ]);

        $paid = $this->actingAs($admin)->postJson('/api/admin/sales', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
            'payment' => [
                'amount' => 100,
                'method' => 'cash',
            ],
        ]);

        $paid->assertCreated()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.paid', 100)
            ->assertJsonPath('data.due', 0)
            ->assertJsonPath('data.advance', 0)
            ->assertJsonCount(1, 'data.payments');

        $partial = $this->actingAs($admin)->postJson('/api/admin/sales', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
            'payment' => [
                'amount' => 40,
                'method' => 'cash',
            ],
        ]);

        $partial->assertCreated()
            ->assertJsonPath('data.status', 'partial')
            ->assertJsonPath('data.paid', 40)
            ->assertJsonPath('data.due', 60)
            ->assertJsonPath('data.advance', 0);

        $this->actingAs($admin)->postJson('/api/admin/payments', [
            'customer_id' => $customer->id,
            'sale_id' => $partial->json('data.id'),
            'amount' => 20,
            'method' => 'bank',
        ])->assertCreated();

        $this->actingAs($admin)->getJson('/api/admin/sales/'.$partial->json('data.id'))
            ->assertOk()
            ->assertJsonPath('data.status', 'partial')
            ->assertJsonPath('data.paid', 60)
            ->assertJsonPath('data.due', 40)
            ->assertJsonCount(2, 'data.payments');

        $advance = $this->actingAs($admin)->postJson('/api/admin/sales', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
            'payment' => [
                'amount' => 150,
                'method' => 'cash',
            ],
        ]);

        $advance->assertCreated()
            ->assertJsonPath('data.status', 'advance')
            ->assertJsonPath('data.paid', 150)
            ->assertJsonPath('data.due', 0)
            ->assertJsonPath('data.advance', 50);

        $this->actingAs($admin)->postJson('/api/admin/payments', [
            'customer_id' => $customer->id,
            'sale_id' => null,
            'amount' => 25,
            'method' => 'cash',
            'notes' => 'Customer advance',
        ])->assertCreated()
            ->assertJsonPath('data.kind', 'advance')
            ->assertJsonPath('data.sale_id', null);
    }

    public function test_customer_list_and_details_show_paid_due_and_advance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Balance Buyer']);
        $product = Product::query()->create([
            'name' => 'Panel',
            'sku' => 'PN-BAL',
            'sale_price' => 100,
            'cost_price' => 40,
            'stock_qty' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->postJson('/api/admin/sales', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
            'payment' => [
                'amount' => 40,
                'method' => 'cash',
            ],
        ])->assertCreated();

        $this->actingAs($admin)->getJson('/api/admin/customers?q=Balance')
            ->assertOk()
            ->assertJsonPath('data.0.paid', 40)
            ->assertJsonPath('data.0.due', 60)
            ->assertJsonPath('data.0.advance', 0);

        $this->actingAs($admin)->postJson('/api/admin/payments', [
            'customer_id' => $customer->id,
            'amount' => 80,
            'method' => 'cash',
        ])->assertCreated();

        $this->actingAs($admin)->getJson('/api/admin/customers/'.$customer->id)
            ->assertOk()
            ->assertJsonPath('data.paid', 120)
            ->assertJsonPath('data.due', 0)
            ->assertJsonPath('data.advance', 20)
            ->assertJsonPath('data.charged', 100);
    }
}
