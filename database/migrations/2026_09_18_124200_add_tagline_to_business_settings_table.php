<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->text('tagline')->nullable()->after('currency');
        });

        DB::table('business_settings')->whereNull('tagline')->update([
            'tagline' => 'Thank you for your business!',
        ]);
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('tagline');
        });
    }
};
