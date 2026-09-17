<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'email',
        'phone',
        'address',
        'currency',
    ];

    public function currencyCode(): string
    {
        return $this->currency ?: (string) config('currencies.default', 'AED');
    }

    public function formatMoney(mixed $amount): string
    {
        return $this->currencyCode().' '.number_format((float) $amount, 2);
    }

    public function logoPath(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        $path = public_path('storage/'.$this->logo);

        return file_exists($path) ? $path : null;
    }
}
