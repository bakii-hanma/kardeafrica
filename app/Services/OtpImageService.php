<?php

namespace App\Services;

/**
 * OtpImageService — génère une image PNG de code de vérification aux couleurs
 * KardAfrica (fond sombre, accent teal, logo), envoyée sur WhatsApp via WAHA.
 */
class OtpImageService
{
    private string $font;
    private string $fontReg;

    public function __construct()
    {
        $this->font    = resource_path('fonts/DejaVuSans-Bold.ttf');
        $this->fontReg = resource_path('fonts/DejaVuSans.ttf');
    }

    /** @return string PNG binaire. */
    public function render(string $code): string
    {
        $W = 1000; $H = 560;
        $img = imagecreatetruecolor($W, $H);
        imagealphablending($img, true);

        // Palette
        $navy   = imagecolorallocate($img, 15, 23, 42);     // #0F172A
        $navy2  = imagecolorallocate($img, 30, 41, 59);     // #1E293B
        $teal   = imagecolorallocate($img, 78, 205, 196);   // #4ECDC4
        $tealD  = imagecolorallocate($img, 68, 160, 141);   // #44A08D
        $white  = imagecolorallocate($img, 255, 255, 255);
        $gray   = imagecolorallocate($img, 148, 163, 184);  // slate-400
        $cardBg = imagecolorallocate($img, 23, 33, 54);

        // Fond dégradé vertical (navy -> navy2)
        for ($y = 0; $y < $H; $y++) {
            $t = $y / $H;
            $r = (int) (15 + (30 - 15) * $t);
            $g = (int) (23 + (41 - 23) * $t);
            $b = (int) (42 + (59 - 42) * $t);
            $c = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $W, $y, $c);
        }

        // Lueur teal (coin haut-gauche) — ellipse translucide
        $glow = imagecolorallocatealpha($img, 78, 205, 196, 110);
        imagefilledellipse($img, 120, 60, 520, 420, $glow);

        // Barre d'accent en haut
        imagefilledrectangle($img, 0, 0, $W, 8, $teal);

        // Logo "Kardafrica"
        imagettftext($img, 34, 0, 60, 90, $white, $this->font, 'Kard');
        $bb = imagettfbbox(34, 0, $this->font, 'Kard');
        $kardW = $bb[2] - $bb[0];
        imagettftext($img, 34, 0, 60 + $kardW, 90, $teal, $this->font, 'africa');

        // Label
        imagettftext($img, 15, 0, 62, 180, $gray, $this->font, 'CODE DE VÉRIFICATION');

        // Carte du code
        $cx1 = 60; $cy1 = 210; $cx2 = $W - 60; $cy2 = 400;
        $this->roundedRect($img, $cx1, $cy1, $cx2, $cy2, 24, $cardBg);
        imagesetthickness($img, 3);
        $this->roundedRectOutline($img, $cx1, $cy1, $cx2, $cy2, 24, $tealD);

        // Le code, espacé, centré dans la carte
        $digits = str_split($code);
        $size = 72;
        $gapExtra = 26;
        // largeur totale
        $total = 0; $widths = [];
        foreach ($digits as $d) {
            $b = imagettfbbox($size, 0, $this->font, $d);
            $w = $b[2] - $b[0];
            $widths[] = $w;
            $total += $w + $gapExtra;
        }
        $total -= $gapExtra;
        $x = (int) (($W - $total) / 2);
        $yCode = (int) (($cy1 + $cy2) / 2 + $size / 2 - 6);
        foreach ($digits as $i => $d) {
            imagettftext($img, $size, 0, $x, $yCode, $white, $this->font, $d);
            $x += $widths[$i] + $gapExtra;
        }

        // Footer
        imagettftext($img, 15, 0, 62, 470, $teal, $this->fontReg, 'Valable 10 minutes');
        imagettftext($img, 13, 0, 62, 505, $gray, $this->fontReg, 'Ne partage ce code avec personne. KardAfrica ne te le demandera jamais.');

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);
        return $png;
    }

    private function roundedRect($img, $x1, $y1, $x2, $y2, $r, $color): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    private function roundedRectOutline($img, $x1, $y1, $x2, $y2, $r, $color): void
    {
        imageline($img, $x1 + $r, $y1, $x2 - $r, $y1, $color);
        imageline($img, $x1 + $r, $y2, $x2 - $r, $y2, $color);
        imageline($img, $x1, $y1 + $r, $x1, $y2 - $r, $color);
        imageline($img, $x2, $y1 + $r, $x2, $y2 - $r, $color);
        imagearc($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 180, 270, $color);
        imagearc($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 270, 360, $color);
        imagearc($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 90, 180, $color);
        imagearc($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 90, $color);
    }
}
