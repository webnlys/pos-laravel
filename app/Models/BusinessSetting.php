<?php

namespace App\Models;

use App\Support\MoneyWords;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'signature',
        'email',
        'phone',
        'website',
        'address',
        'currency',
        'tagline',
        'invoice_terms',
        'quotation_terms',
    ];

    public function currencyCode(): string
    {
        return $this->currency ?: (string) config('currencies.default', 'AED');
    }

    public function taglineText(): ?string
    {
        $tagline = trim((string) $this->tagline);

        return $tagline !== '' ? $tagline : null;
    }

    public function invoiceTermsText(): ?string
    {
        $terms = trim((string) $this->invoice_terms);

        return $terms !== '' ? $terms : null;
    }

    /**
     * @return list<string>
     */
    public function invoiceTermsLines(): array
    {
        if (! $this->invoiceTermsText()) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $this->invoice_terms) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));
    }

    public function quotationTermsText(): ?string
    {
        $terms = trim((string) $this->quotation_terms);

        return $terms !== '' ? $terms : null;
    }

    /**
     * @return list<string>
     */
    public function quotationTermsLines(): array
    {
        if (! $this->quotationTermsText()) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $this->quotation_terms) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));
    }

    public function formatMoney(mixed $amount): string
    {
        return $this->currencyCode().' '.number_format((float) $amount, 2);
    }

    public function amountInWords(mixed $amount): string
    {
        return MoneyWords::convert($amount, $this->currencyCode(), false);
    }

    public static function documentTitle(?string $name = null): string
    {
        $brand = trim((string) $name);

        return $brand !== '' ? $brand.' - dashboard' : 'dashboard';
    }

    public function logoPath(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        $path = public_path('storage/'.$this->logo);

        return file_exists($path) ? $path : null;
    }

    public function signaturePath(): ?string
    {
        if (! $this->signature) {
            return null;
        }

        $path = public_path('storage/'.$this->signature);

        return file_exists($path) ? $path : null;
    }
}
