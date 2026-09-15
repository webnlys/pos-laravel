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
        User::query()->firstOrCreate(
            ['email' => 'admin@simplepos.test'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        $customer = Customer::query()->firstOrCreate(
            ['email' => 'customer@simplepos.test'],
            [
                'name' => 'Walk-in Customer',
                'phone' => '01700000000',
                'address' => 'Dhaka, Bangladesh',
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'customer@simplepos.test'],
            [
                'name' => $customer->name,
                'password' => 'password',
                'role' => 'customer',
                'customer_id' => $customer->id,
            ]
        );

        Supplier::query()->firstOrCreate(
            ['name' => 'Default Supplier'],
            ['phone' => '01800000000', 'email' => 'supplier@simplepos.test']
        );

        Product::query()->firstOrCreate(
            ['sku' => 'KB-001'],
            ['name' => 'Mechanical Keyboard', 'sale_price' => 4500, 'cost_price' => 3200, 'stock_qty' => 10, 'is_active' => true]
        );
        Product::query()->firstOrCreate(
            ['sku' => 'MS-001'],
            ['name' => 'Wireless Mouse', 'sale_price' => 1200, 'cost_price' => 800, 'stock_qty' => 25, 'is_active' => true]
        );
        Product::query()->firstOrCreate(
            ['sku' => 'HD-001'],
            ['name' => '27" Monitor', 'sale_price' => 18500, 'cost_price' => 14200, 'stock_qty' => 5, 'is_active' => true]
        );

        Tax::query()->firstOrCreate(
            ['name' => 'VAT'],
            [
                'rate_percent' => 15,
                'effective_from' => '2020-01-01 00:00:00',
                'effective_to' => null,
            ]
        );

        BusinessSetting::query()->firstOrCreate([], [
            'name' => 'Simple POS',
            'email' => 'hello@simplepos.test',
            'phone' => '01300000000',
            'address' => 'Dhaka, Bangladesh',
        ]);
    }
}
