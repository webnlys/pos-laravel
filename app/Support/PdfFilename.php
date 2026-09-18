<?php

namespace App\Support;

class PdfFilename
{
    public static function build(?string $customerName, string $type, string $number): string
    {
        $customerName = trim((string) $customerName);
        $safeName = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $customerName), '_');

        if ($safeName === '') {
            $safeName = 'Customer';
        }

        return $safeName.'-'.$type.'-'.$number.'.pdf';
    }
}
