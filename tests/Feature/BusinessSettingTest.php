<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_business_endpoint_returns_default_currency(): void
    {
        $this->getJson('/api/business')
            ->assertOk()
            ->assertJsonPath('data.currency', 'AED')
            ->assertJsonPath('data.currencies.AED', 'UAE Dirham');
    }

    public function test_admin_can_update_currency(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/admin/settings', [
            'name' => 'Desert Shop',
            'currency' => 'usd',
        ])->assertSuccessful()
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.name', 'Desert Shop');

        $this->assertDatabaseHas('business_settings', [
            'name' => 'Desert Shop',
            'currency' => 'USD',
        ]);
    }

    public function test_admin_can_update_invoice_tagline(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/admin/settings', [
            'name' => 'Desert Shop',
            'currency' => 'AED',
            'tagline' => 'Prices are valid for 14 days.',
        ])->assertSuccessful()
            ->assertJsonPath('data.tagline', 'Prices are valid for 14 days.');

        $this->assertDatabaseHas('business_settings', [
            'name' => 'Desert Shop',
            'tagline' => 'Prices are valid for 14 days.',
        ]);
    }

    public function test_admin_can_update_invoice_terms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/admin/settings', [
            'name' => 'Desert Shop',
            'currency' => 'AED',
            'invoice_terms' => 'Payment is due within 14 days.',
        ])->assertSuccessful()
            ->assertJsonPath('data.invoice_terms', 'Payment is due within 14 days.');

        $this->assertDatabaseHas('business_settings', [
            'name' => 'Desert Shop',
            'invoice_terms' => 'Payment is due within 14 days.',
        ]);
    }

    public function test_admin_cannot_set_unknown_currency(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/admin/settings', [
            'name' => 'Desert Shop',
            'currency' => 'XXX',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('currency');
    }

    public function test_quotation_pdf_html_uses_selected_currency(): void
    {
        BusinessSetting::query()->create([
            'name' => 'Desert Shop',
            'currency' => 'SAR',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Walk-in']);
        $product = Product::query()->create([
            'name' => 'Panel',
            'sku' => 'PN-CUR',
            'sale_price' => 10,
            'cost_price' => 5,
            'stock_qty' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10],
            ],
        ])->assertCreated();

        $quotation = Quotation::query()->first();
        $html = view('pdf.quotation', [
            'document' => $quotation->load(['customer', 'items', 'taxes']),
            'title' => 'Quotation',
            'settings' => BusinessSetting::query()->first(),
        ])->render();

        $this->assertStringContainsString('SAR', $html);
        $this->assertStringContainsString('10.00', $html);
        $this->assertStringContainsString('Amount in words', $html);
        $this->assertStringContainsString('Ten Only', $html);
        $this->assertStringNotContainsString('Saudi Riyals', $html);
        $this->assertStringNotContainsString('UAE Dirhams', $html);
        $this->assertStringNotContainsString('Thank you for your business!', $html);
    }

    public function test_quotation_pdf_html_uses_settings_tagline(): void
    {
        BusinessSetting::query()->create([
            'name' => 'Desert Shop',
            'currency' => 'AED',
            'tagline' => 'Prices are valid for 14 days.',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create(['name' => 'Walk-in']);
        $product = Product::query()->create([
            'name' => 'Panel',
            'sku' => 'PN-TAG',
            'sale_price' => 10,
            'cost_price' => 5,
            'stock_qty' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10],
            ],
        ])->assertCreated();

        $quotation = Quotation::query()->first();
        $html = view('pdf.quotation', [
            'document' => $quotation->load(['customer', 'items', 'taxes']),
            'title' => 'Quotation',
            'settings' => BusinessSetting::query()->first(),
        ])->render();

        $this->assertStringContainsString('Prices are valid for 14 days.', $html);
        $this->assertStringNotContainsString('Thank you for your business!', $html);
    }

    public function test_quotation_pdf_html_uses_invoice_terms_at_bottom(): void
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
            'sku' => 'PN-TERMS',
            'sale_price' => 10,
            'cost_price' => 5,
            'stock_qty' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->postJson('/api/admin/quotations', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10],
            ],
        ])->assertCreated();

        $quotation = Quotation::query()->first();
        $html = view('pdf.quotation', [
            'document' => $quotation->load(['customer', 'items', 'taxes']),
            'title' => 'Quotation',
            'settings' => BusinessSetting::query()->first(),
        ])->render();

        $this->assertStringContainsString('Terms and Conditions', $html);
        $this->assertStringContainsString('<li>Payment is due within 14 days.</li>', $html);
        $this->assertStringContainsString('htmlpagefooter', $html);
    }

    public function test_spa_meta_title_uses_business_name(): void
    {
        BusinessSetting::query()->create([
            'name' => 'Desert Shop',
            'currency' => 'AED',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('<title>Desert Shop - dashboard</title>', false)
            ->assertSee('<meta name="title" content="Desert Shop - dashboard">', false);
    }
}
