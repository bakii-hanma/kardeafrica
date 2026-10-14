<?php

namespace App\Services;

/**
 * CardImageService — rend la carte livrée en PNG, IDENTIQUE à la carte « Mes
 * cartes » du site (resources/views/cards/index.blade.php) :
 *   - en-tête couleur de marque pleine (Apple noir, etc.) + texture de cercles
 *     + halo blanc + « GIFT CARD » + marque + « Prix payé » FCFA + crédit + puce
 *   - corps BLANC : nom du produit, boîte CODE, boîtes PIN / EXPIRE
 *
 * La couleur de marque suit exactement la même logique que la vue (palette +
 * hash), pour que l'image colle au visuel que voit le client.
 */
class CardImageService
{
    private string $font;
    private string $fontReg;

    public function __construct()
    {
        $this->font    = resource_path('fonts/DejaVuSans-Bold.ttf');
        $this->fontReg = resource_path('fonts/DejaVuSans.ttf');
    }

    /**
     * @param array{brand?:string,name?:string,face?:string,currency?:string,code?:string,pin?:string,expires?:string,logo_url?:string,price_xaf?:float|int|string,status?:string} $card
     */
    public function render(array $card): string
    {
        $name    = (string) ($card['name'] ?? 'Carte cadeau');
        $brand   = trim((string) ($card['brand'] ?? '')) ?: (explode(' ', $name)[0] ?? 'Carte');
        $face    = trim((string) ($card['face'] ?? ''));
        $cur     = (string) ($card['currency'] ?? '');
        $code    = (string) ($card['code'] ?? '');
        $pin     = (string) ($card['pin'] ?? '');
        $expires = (string) ($card['expires'] ?? '');
        $priceX  = (float) ($card['price_xaf'] ?? 0);
        $status  = strtolower((string) ($card['status'] ?? 'active'));
        $isUrl   = str_starts_with($code, 'http');

        $bc = $this->hex2rgb($this->brandColor($brand !== '' ? $brand : $name));

        // --- Dimensions ---------------------------------------------------
        $W = 1000;
        $headerH = 300;
        $H = 690;
        $P = 44;
        $r = 28;

        $base = imagecreatetruecolor($W, $H);
        imagealphablending($base, true);

        // Corps blanc (tout le fond)
        $white = imagecolorallocate($base, 255, 255, 255);
        imagefilledrectangle($base, 0, 0, $W, $H, $white);

        // ---- EN-TÊTE couleur de marque (plein) ----
        $brandCol = imagecolorallocate($base, $bc[0], $bc[1], $bc[2]);
        imagefilledrectangle($base, 0, 0, $W, $headerH, $brandCol);

        // Halo blanc flou, coin haut-droit
        $halo = $this->glowSprite(255, 255, 255, 104);
        $hs = 440;
        imagecopyresampled($base, $halo, $W - 300, -170, 0, 0, $hs, $hs, imagesx($halo), imagesy($halo));
        imagedestroy($halo);

        // Texture cercles (blanc ~8%)
        $tex = imagecolorallocatealpha($base, 255, 255, 255, 117);
        imagesetthickness($base, 1);
        for ($cy = 18; $cy < $headerH; $cy += 36) {
            for ($cx = 18; $cx < $W; $cx += 36) {
                imageellipse($base, $cx, $cy, 28, 28, $tex);
            }
        }

        // Logo marque en filigrane (bitmap), à droite dans l'en-tête
        $logo = ! empty($card['logo_url']) ? $this->fetchImage($card['logo_url']) : null;
        if ($logo) {
            $lw = imagesx($logo); $lh = imagesy($logo);
            $target = 190; $scale = $target / max($lw, $lh);
            $dw = (int) round($lw * $scale); $dh = (int) round($lh * $scale);
            $wm = imagecreatetruecolor($dw, $dh);
            imagealphablending($wm, false); imagesavealpha($wm, true);
            imagefill($wm, 0, 0, imagecolorallocatealpha($wm, 0, 0, 0, 127));
            imagecopyresampled($wm, $logo, 0, 0, 0, 0, $dw, $dh, $lw, $lh);
            imagecopymerge($base, $wm, $W - $dw - 60, (int)(($headerH - $dh) / 2), 0, 0, $dw, $dh, 14);
            imagedestroy($wm); imagedestroy($logo);
        }

        $wTxt  = imagecolorallocate($base, 255, 255, 255);
        $w80   = imagecolorallocatealpha($base, 255, 255, 255, 38);
        $w60   = imagecolorallocatealpha($base, 255, 255, 255, 60);

        // « GIFT CARD »
        imagettftext($base, 12, 0, $P, 52, $w80, $this->font, 'G I F T   C A R D');

        // Badge statut (pastille) en haut à droite
        $this->statusPill($base, $W - $P, 36, $status);

        // Nom de marque (hero)
        imagettftext($base, 40, 0, $P, 128, $wTxt, $this->font, $this->fit($brand, 18));

        // Bas d'en-tête : Prix payé + crédit (gauche), puce (droite)
        imagettftext($base, 11, 0, $P, 212, $w60, $this->font, 'PRIX PAYÉ');
        $priceStr = number_format($priceX, 0, ',', ' ');
        imagettftext($base, 28, 0, $P, 252, $wTxt, $this->font, $priceStr);
        $pw = $this->textW(28, $this->font, $priceStr);
        imagettftext($base, 14, 0, $P + $pw + 10, 252, $w60, $this->fontReg, 'FCFA');
        if ($face !== '' && strtoupper($cur) !== 'XAF') {
            imagettftext($base, 13, 0, $P, 284, $w60, $this->fontReg, $face . ' ' . $cur . ' de crédit');
        }
        // Puce dorée
        $this->chip($base, $W - $P - 76, 214, 76, 50);

        // ---- CORPS BLANC ----
        $ink   = imagecolorallocate($base, 15, 23, 42);      // slate-900
        $mute  = imagecolorallocate($base, 148, 163, 184);   // slate-400
        $boxBg = imagecolorallocate($base, 248, 250, 252);   // slate-50
        $boxBd = imagecolorallocate($base, 226, 232, 240);   // slate-200

        // Nom du produit
        imagettftext($base, 21, 0, $P, $headerH + 52, $ink, $this->font, $this->fit($name, 42));

        // Boîte CODE (pleine largeur)
        $cY1 = $headerH + 76; $cY2 = $cY1 + 104;
        $this->box($base, $P, $cY1, $W - $P, $cY2, 14, $boxBg, $boxBd);
        imagettftext($base, 11, 0, $P + 22, $cY1 + 32, $mute, $this->font, $isUrl ? 'LIEN' : 'CODE');
        if ($isUrl) {
            $short = preg_replace('#^https?://#', '', $code);
            $short = strlen($short) > 50 ? substr($short, 0, 50) . '…' : $short;
            imagettftext($base, 18, 0, $P + 22, $cY1 + 76, $ink, $this->font, $short);
        } else {
            imagettftext($base, 26, 0, $P + 22, $cY1 + 80, $ink, $this->font, $this->fit($code !== '' ? $code : '—', 34));
        }

        // Ligne PIN / EXPIRE
        $rY1 = $cY2 + 18; $rY2 = $rY1 + 104;
        $mid = (int) ($W / 2);
        $hasPin = $pin !== '';
        if ($hasPin) {
            $this->box($base, $P, $rY1, $mid - 8, $rY2, 14, $boxBg, $boxBd);
            imagettftext($base, 11, 0, $P + 22, $rY1 + 32, $mute, $this->font, 'PIN');
            imagettftext($base, 24, 0, $P + 22, $rY1 + 78, $ink, $this->font, $pin);
            if ($expires !== '') {
                $this->box($base, $mid + 8, $rY1, $W - $P, $rY2, 14, $boxBg, $boxBd);
                imagettftext($base, 11, 0, $mid + 30, $rY1 + 32, $mute, $this->font, 'EXPIRE');
                imagettftext($base, 22, 0, $mid + 30, $rY1 + 78, $ink, $this->font, $expires);
            }
        } elseif ($expires !== '') {
            $this->box($base, $P, $rY1, $mid - 8, $rY2, 14, $boxBg, $boxBd);
            imagettftext($base, 11, 0, $P + 22, $rY1 + 32, $mute, $this->font, 'EXPIRE');
            imagettftext($base, 22, 0, $P + 22, $rY1 + 78, $ink, $this->font, $expires);
        }

        // Pied
        $foot = imagecolorallocate($base, 100, 116, 139);
        imagettftext($base, 12, 0, $P, $H - 28, $foot, $this->fontReg, 'Retrouve ta carte dans ton compte · kardafrica.com');

        // Image rectangulaire pleine (sans bord / sans coin transparent)
        ob_start();
        imagepng($base);
        $png = ob_get_clean();
        imagedestroy($base);
        return $png;
    }

    // ---- helpers --------------------------------------------------------

    /** Couleur de marque — même logique que la vue cards/index (palette + hash). */
    private function brandColor(string $name): string
    {
        $palette = [
            'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
            'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
            'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
            'Roblox' => '#00A2FF', 'Nintendo' => '#E60012', 'Disney' => '#0E47A1',
            'StarzPlay' => '#7C3AED', 'Talabat' => '#FF5A00', 'HUAWEI' => '#C7000B', 'IKEA' => '#0058A3',
        ];
        if ($name === '') { return '#1F2937'; }
        foreach ($palette as $k => $c) {
            if (stripos($name, $k) !== false) { return $c; }
        }
        $fallback = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0; $len = strlen($name);
        for ($i = 0; $i < $len; $i++) {
            $hash = (ord($name[$i]) + (($hash << 5) - $hash)) & 0x7FFFFFFF;
        }
        $idx = (($hash % count($fallback)) + count($fallback)) % count($fallback);
        return $fallback[$idx];
    }

    private function statusPill($img, int $xRight, int $y, string $status): void
    {
        [$label, $dot] = match ($status) {
            'used'    => ['Utilisée', false],
            'expired' => ['Expirée', false],
            default   => ['Active', true],
        };
        $padX = 14; $h = 26;
        $tw = $this->textW(11, $this->font, $label);
        $w = $tw + $padX * 2 + ($dot ? 16 : 0);
        $x1 = $xRight - $w; $x2 = $xRight;
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 95);
        $this->roundedRect($img, $x1, $y, $x2, $y + $h, 13, $bg);
        $tx = $x1 + $padX;
        if ($dot) {
            $green = imagecolorallocate($img, 134, 239, 172);
            imagefilledellipse($img, $tx + 4, $y + (int)($h / 2), 8, 8, $green);
            $tx += 16;
        }
        $wTxt = imagecolorallocate($img, 255, 255, 255);
        imagettftext($img, 11, 0, $tx, $y + 18, $wTxt, $this->font, $label);
    }

    private function box($img, $x1, $y1, $x2, $y2, $r, $bg, $border): void
    {
        $this->roundedRect($img, $x1, $y1, $x2, $y2, $r, $bg);
        imagesetthickness($img, 1);
        $this->roundedStroke($img, $x1, $y1, $x2, $y2, $r, $border);
    }

    private function glowSprite(int $r, int $g, int $b, int $baseAlpha, int $S = 120)
    {
        $s = imagecreatetruecolor($S, $S);
        imagesavealpha($s, true);
        imagealphablending($s, false);
        imagefill($s, 0, 0, imagecolorallocatealpha($s, 0, 0, 0, 127));
        $c = ($S - 1) / 2;
        for ($y = 0; $y < $S; $y++) {
            for ($x = 0; $x < $S; $x++) {
                $d = sqrt(($x - $c) ** 2 + ($y - $c) ** 2) / $c;
                if ($d > 1) { $d = 1; }
                $t = (1 - $d) ** 2;
                $a = (int) round(127 - (127 - $baseAlpha) * $t);
                imagesetpixel($s, $x, $y, imagecolorallocatealpha($s, $r, $g, $b, $a));
            }
        }
        return $s;
    }

    private function hex2rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) { return [31, 41, 55]; }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private function textW(float $size, string $font, string $text): int
    {
        $b = imagettfbbox($size, 0, $font, $text);
        return abs($b[2] - $b[0]);
    }

    private function fit(string $text, int $max): string
    {
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1) . '…' : $text;
    }

    private function fetchImage(?string $url)
    {
        if (! $url) { return null; }
        try {
            $ctx = stream_context_create([
                'http' => ['timeout' => 6, 'user_agent' => 'Mozilla/5.0 KardAfrica'],
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $data = @file_get_contents($url, false, $ctx);
            if (! $data) { return null; }
            $im = @imagecreatefromstring($data);
            if ($im) { imagealphablending($im, true); }
            return $im ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Puce dorée EMV (gradient jaune + contour interne). */
    private function chip($img, int $x, int $y, int $w, int $h): void
    {
        for ($i = 0; $i < $h; $i++) {
            $t = $i / max(1, $h - 1);
            $c = imagecolorallocate($img, (int)(254 - 4 * $t), (int)(240 - 36 * $t), (int)(170 - 90 * $t));
            imageline($img, $x, $y + $i, $x + $w, $y + $i, $c);
        }
        $inner = imagecolorallocatealpha($img, 90, 60, 0, 90);
        imagesetthickness($img, 1);
        imagerectangle($img, $x + 5, $y + 5, $x + $w - 5, $y + $h - 5, $inner);
        imageline($img, $x + 5, $y + (int)($h / 2), $x + $w - 5, $y + (int)($h / 2), $inner);
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

    private function roundedStroke($img, $x1, $y1, $x2, $y2, $r, $color): void
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

    /** Copie $src sur $dst en clippant aux coins arrondis de rayon $r. */
    private function copyRounded($dst, $src, int $ox, int $oy, int $w, int $h, int $r): void
    {
        for ($y = 0; $y < $h; $y++) {
            $inset = 0;
            if ($y < $r)          { $dy = $r - $y;        $inset = (int) round($r - sqrt(max(0, $r * $r - $dy * $dy))); }
            elseif ($y > $h - $r) { $dy = $y - ($h - $r); $inset = (int) round($r - sqrt(max(0, $r * $r - $dy * $dy))); }
            $xs = $inset; $xe = $w - $inset;
            imagecopy($dst, $src, $ox + $xs, $oy + $y, $xs, $y, max(1, $xe - $xs), 1);
        }
    }
}
