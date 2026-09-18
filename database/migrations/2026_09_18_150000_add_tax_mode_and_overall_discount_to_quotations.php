<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('tax_mode')->default('per_item')->after('discount');
            $table->decimal('overall_discount', 12, 2)->default(0)->after('tax_mode');
            $table->foreignId('overall_tax_id')->nullable()->after('overall_discount')->constrained('taxes')->nullOnDelete();
            $table->string('overall_tax_name')->nullable()->after('overall_tax_id');
            $table->decimal('overall_tax_rate_percent', 8, 2)->nullable()->after('overall_tax_name');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('overall_tax_id');
            $table->dropColumn(['tax_mode', 'overall_discount', 'overall_tax_name', 'overall_tax_rate_percent']);
        });
    }
};
