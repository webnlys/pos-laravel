<?php

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/{any?}', function () {
    $name = config('app.name', '');

    try {
        if (Schema::hasTable('business_settings')) {
            $name = BusinessSetting::query()->value('name') ?: $name;
        }
    } catch (\Throwable) {
        // Keep the configured app name until settings are available.
    }

    return view('app', [
        'metaTitle' => BusinessSetting::documentTitle($name),
    ]);
})->where('any', '^(?!api|sanctum|storage|up).*$');
