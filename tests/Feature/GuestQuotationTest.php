<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GuestQuotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_creates_customer_without_login_when_contact_is_new(): void
    {
        [$product, $tax] = $this->catalog();

        $response = $this->postJson('/api/guest/quotations', [
            'email' => 'newguest@example.test',
            'phone' => '01700000001',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 50,
                    'discount' => 0,
                    'tax_id' => $tax->id,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total', 55)
            ->assertJsonStructure(['pdf_url']);

        $this->assertDatabaseHas('customers', [
            'email' => 'newguest@example.test',
            'phone' => '01700000001',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'newguest@example.test',
        ]);
        $this->assertDatabaseHas('quotations', [
            'user_id' => null,
            'total' => 55,
        ]);
    }

    public function test_guest_binds_quotation_to_existing_customer_by_email(): void
    {
        [$product] = $this->catalog();
        $customer = Customer::query()->create([
            'name' => 'Existing',
            'email' => 'known@example.test',
            'phone' => '111',
        ]);

        $this->postJson('/api/guest/quotations', [
            'email' => 'known@example.test',
            'phone' => '999',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id);

        $this->assertEquals(1, Customer::query()->count());
    }

    public function test_guest_binds_quotation_to_existing_customer_by_phone(): void
    {
        [$product] = $this->catalog();
        $customer = Customer::query()->create([
            'name' => 'By Phone',
            'email' => 'phone-owner@example.test',
            'phone' => '01811112222',
        ]);

        $this->postJson('/api/guest/quotations', [
            'email' => 'other@example.test',
            'phone' => '01811112222',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 25],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id);

        $this->assertEquals(1, Customer::query()->count());
    }

    public function test_guest_can_download_signed_pdf(): void
    {
        [$product] = $this->catalog();

        $response = $this->postJson('/api/guest/quotations', [
            'email' => 'print@example.test',
            'phone' => '01500000000',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10],
            ],
        ])->assertCreated();

        $pdfUrl = $response->json('pdf_url');
        $this->assertNotEmpty($pdfUrl);

        $this->get($pdfUrl)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(URL::route('guest.quotations.pdf', ['quotation' => $response->json('data.id')]))
            ->assertForbidden();
    }

    public function test_admin_can_enable_login_for_guest_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::query()->create([
            'name' => 'Guest User',
            'email' => 'guestlogin@example.test',
            'phone' => '01600000000',
        ]);

        $this->actingAs($admin)->putJson("/api/admin/customers/{$customer->id}", [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'password' => 'secret12',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'email' => 'guestlogin@example.test',
            'role' => 'customer',
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * @return array{0: Product, 1: Tax}
     */
    private function catalog(): array
    {
        $product = Product::query()->create([
            'name' => 'Panel',
            'sku' => 'PN-001',
            'sale_price' => 50,
            'cost_price' => 20,
            'stock_qty' => 0,
            'is_active' => true,
        ]);
        $tax = Tax::query()->create([
            'name' => 'VAT',
            'rate_percent' => 10,
            'effective_from' => now()->subDay(),
        ]);

        return [$product, $tax];
    }
}
