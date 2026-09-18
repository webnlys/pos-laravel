<?php

namespace App\Support;

use Mpdf\Barcode;

class BarcodeImage
{
    public static function dataUri(string $code, string $type = 'C128B', float $moduleWidthPx = 2.2, int $heightPx = 60): string
    {
        // Mpdf's default 10-module quiet zone on each side leaves a large blank
        // margin inside the image, which makes the bars look off-center/left
        // even when the <img> itself is right-aligned. Trim it down so the
        // visible bars sit flush against the image edges.
        $arr = (new Barcode())->getBarcodeArray($code, $type, '', 2, 2);

        if (! $arr) {
            return '';
        }

        $leftMargin = ($arr['lightmL'] ?? 0) * $moduleWidthPx;
        $rightMargin = ($arr['lightmR'] ?? 0) * $moduleWidthPx;
        $width = (int) ceil(($arr['maxw'] * $moduleWidthPx) + $leftMargin + $rightMargin);

        $image = imagecreatetruecolor(max(1, $width), $heightPx);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $width, $heightPx, $white);

        $maxHeight = $arr['maxh'] ?? 1;
        $x = $leftMargin;

        foreach ($arr['bcode'] as $bar) {
            $barWidth = $bar['w'] * $moduleWidthPx;

            if ($bar['t']) {
                $barHeight = ($bar['h'] / $maxHeight) * $heightPx;
                $y = ($bar['p'] / $maxHeight) * $heightPx;

                imagefilledrectangle(
                    $image,
                    (int) round($x),
                    (int) round($y),
                    (int) round($x + $barWidth) - 1,
                    (int) round($y + $barHeight) - 1,
                    $black,
                );
            }

            $x += $barWidth;
        }

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}
