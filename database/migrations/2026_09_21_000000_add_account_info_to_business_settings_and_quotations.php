<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->text('account_info')->nullable()->after('quotation_terms');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->boolean('include_account_info')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('account_info');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('include_account_info');
        });
    }
};
