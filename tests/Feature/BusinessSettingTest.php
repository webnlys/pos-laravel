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
    }
}
