<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@simplepos.test'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => 'admin',
                'customer_id' => null,
            ]
        );

        Customer::query()->updateOrCreate(
            ['email' => 'demo.customer@simplepos.test'],
            [
                'name' => 'Demo Customer',
                'phone' => '01700000000',
                'address' => 'Dhaka, Bangladesh',
            ]
        );

        Supplier::query()->updateOrCreate(
            ['name' => 'Default Supplier'],
            [
                'phone' => '01800000000',
                'email' => 'supplier@simplepos.test',
                'address' => null,
            ]
        );

        Product::query()->updateOrCreate(
            ['sku' => 'SD-001'],
            [
                'name' => 'SALA DOWN (curtain+shipon 2 window) (roller -3 pes)',
                'sale_price' => 4500,
                'cost_price' => 3200,
                'stock_qty' => 10,
                'is_active' => true,
            ]
        );

        Tax::query()->updateOrCreate(
            ['name' => 'VAT'],
            [
                'rate_percent' => 15,
                'effective_from' => '2020-01-01 00:00:00',
                'effective_to' => null,
            ]
        );

        $settings = BusinessSetting::query()->first() ?? new BusinessSetting;
        $settings->fill([
            'name' => $settings->name ?: 'Simple POS',
            'email' => $settings->email ?: 'hello@simplepos.test',
            'phone' => $settings->phone ?: '01300000000',
            'address' => $settings->address ?: 'Dhaka, Bangladesh',
            'currency' => $settings->currency ?: config('currencies.default', 'AED'),
        ])->save();
    }
}
