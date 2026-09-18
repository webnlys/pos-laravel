<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('product_name')->constrained('units')->nullOnDelete();
            $table->string('unit_name')->nullable()->after('unit_id');
            $table->decimal('discount', 12, 2)->default(0)->after('unit_price');
            $table->foreignId('tax_id')->nullable()->after('discount')->constrained('taxes')->nullOnDelete();
            $table->string('tax_name')->nullable()->after('tax_id');
            $table->decimal('tax_rate_percent', 8, 2)->nullable()->after('tax_name');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_rate_percent');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropConstrainedForeignId('tax_id');
            $table->dropColumn(['unit_name', 'discount', 'tax_name', 'tax_rate_percent', 'tax_amount']);
        });
    }
};
