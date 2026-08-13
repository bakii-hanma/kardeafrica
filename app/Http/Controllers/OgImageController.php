<?php

namespace App\Http\Controllers;

use App\Models\MerchantCard;
use App\Services\ProductApiService;
use App\Support\Money;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

/**
 * Génère dynamiquement l'image Open Graph (1200×630) d'une carte, pour un
 * aperçu riche lors du partage (WhatsApp = canal n°1). Rendu GD, police TTF
 * bundlée (resources/fonts). Mise en cache FICHIER (zéro recalcul) + cache HTTP.
 */
class OgImageController extends Controller
{
    private const W = 1200;
    private const H = 630;
    private const TTL = 86400;                 // 24 h avant régénération

    public function __construct(private ProductApiService $productService) {}

    /** Carte du catalogue afrikard (card-type). */
    public function card(int|string $cardTypeId)
    {
        return $this->serve("card-{$cardTypeId}", function () use ($cardTypeId) {
            $c = $this->productService->getCardTypeById($cardTypeId);
            $name = $c['name'] ?? 'Carte cadeau';
            $min = collect($c['products'] ?? [])
                ->map(fn ($p) => $p['price']['min'] ?? null)->filter(fn ($v) => $v && $v > 0)->min();
            $cur = $c['products'][0]['price']['currencyCode'] ?? 'XAF';
            return [
                'name'     => $name,
                'price'    => $min ? Money::formatFcfa($min, $cur) : null,
                'accent'   => $this->brandColor($name),
                'subtitle' => 'CARTES CADEAUX · MOBILE MONEY',
            ];
        });
    }

    /** Bannière générique 1200×630 — og:image par défaut des pages sans visuel dédié. */
    public function default()
    {
        return $this->serve('default', fn () => [
            'name'     => 'KardAfrica',
            'price'    => null,
            'accent'   => [68, 160, 141],
            'subtitle' => 'CARTES CADEAUX · MOBILE MONEY · GABON',
        ]);
    }

    /** Carte locale (Carte Gabon / merchant). */
    public function gabon(MerchantCard $merchantCard)
    {
        return $this->serve("gabon-{$merchantCard->id}", function () use ($merchantCard) {
            $min = collect($merchantCard->denominations ?? [])->min();
            return [
                'name'     => $merchantCard->name,
                'price'    => $min ? number_format((float) $min, 0, ',', ' ') . ' FCFA' : null,
                'accent'   => [68, 160, 141],  // teal KardAfrica (pas de couleur de marque)
                'subtitle' => 'CARTE GABON · À OFFRIR ET UTILISER SUR PLACE',
            ];
        });
    }

    // ------------------------------------------------------------------
    // Cache fichier + réponse
    // ------------------------------------------------------------------

    private function serve(string $key, callable $data)
    {
        $path = storage_path("app/og/{$key}.png");

        if (!File::exists($path) || (time() - File::lastModified($path)) > self::TTL) {
            File::ensureDirectoryExists(dirname($path));
            $d = $data();
            File::put($path, $this->render($d['name'], $d['price'], $d['accent'], $d['subtitle']));
        }

        return Response::make(File::get($path), 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=86400, s-maxage=604800',
        ]);
    }

    private function brandColor(string $name): array
    {
        $map = [
            'netflix' => [229, 9, 20], 'spotify' => [29, 185, 84], 'apple' => [40, 40, 45],
            'playstation' => [0, 55, 145], 'xbox' => [16, 124, 16], 'steam' => [40, 60, 90],
            'amazon' => [255, 153, 0], 'nintendo' => [230, 0, 18], 'google' => [1, 135, 95],
            'roblox' => [0, 162, 255], 'disney' => [17, 60, 207], 'chatgpt' => [16, 163, 127],
            'deezer' => [162, 56, 255],
        ];
        $l = strtolower($name);
        foreach ($map as $k => $rgb) {
            if (str_contains($l, $k)) return $rgb;
        }
        return [68, 160, 141]; // #44A08D
    }

    private function render(string $name, ?string $price, array $accent, string $subtitle): string
    {
        $img  = imagecreatetruecolor(self::W, self::H);
        $bold = resource_path('fonts/DejaVuSans-Bold.ttf');
        $reg  = resource_path('fonts/DejaVuSans.ttf');

        // Fond : dégradé vertical #0F172A → #0F3D34
        [$t, $b] = [[15, 23, 42], [15, 61, 52]];
        for ($y = 0; $y < self::H; $y++) {
            $r = (int) ($t[0] + ($b[0] - $t[0]) * $y / self::H);
            $g = (int) ($t[1] + ($b[1] - $t[1]) * $y / self::H);
            $bl = (int) ($t[2] + ($b[2] - $t[2]) * $y / self::H);
            imagefilledrectangle($img, 0, $y, self::W, $y, imagecolorallocate($img, $r, $g, $bl));
        }

        // Halo d'accent (coin bas-droit) + barre verticale gauche
        imagefilledellipse($img, self::W - 120, self::H - 60, 620, 620,
            imagecolorallocatealpha($img, $accent[0], $accent[1], $accent[2], 95));
        imagefilledrectangle($img, 0, 0, 14, self::H,
            imagecolorallocate($img, $accent[0], $accent[1], $accent[2]));

        $white = imagecolorallocate($img, 255, 255, 255);
        $teal  = imagecolorallocate($img, 78, 205, 196);
        $muted = imagecolorallocate($img, 148, 163, 184);
        $padX  = 90;

        imagettftext($img, 30, 0, $padX, 110, $white, $bold, 'KardAfrica');
        imagettftext($img, 16, 0, $padX, 145, $teal, $reg, $subtitle);

        // Nom de la carte (gros, max 2 lignes)
        $lines = $this->wrap($name, $bold, 66, self::W - $padX - 200);
        $y = 300;
        foreach (array_slice($lines, 0, 2) as $line) {
            imagettftext($img, 66, 0, $padX, $y, $white, $bold, $line);
            $y += 84;
        }

        if ($price) {
            imagettftext($img, 22, 0, $padX, $y + 24, $muted, $reg, 'À partir de');
            imagettftext($img, 46, 0, $padX, $y + 82, $teal, $bold, $price);
        }

        imagettftext($img, 20, 0, $padX, self::H - 48, $muted, $reg,
            'Airtel Money · Moov Money · Carte bancaire  —  code reçu en 30 s');

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);
        return $data;
    }

    /** Découpe un texte en lignes tenant dans $maxWidth px à la taille $size. */
    private function wrap(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $cur = '';
        foreach ($words as $w) {
            $try = $cur === '' ? $w : "$cur $w";
            $bbox = imagettfbbox($size, 0, $font, $try);
            if (($bbox[2] - $bbox[0]) > $maxWidth && $cur !== '') {
                $lines[] = $cur;
                $cur = $w;
            } else {
                $cur = $try;
            }
        }
        if ($cur !== '') $lines[] = $cur;
        return $lines ?: [$text];
    }
}
