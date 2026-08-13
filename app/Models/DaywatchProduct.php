<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DaywatchProduct extends Model
{
    protected $fillable = [
        'plan_id',
        'name',
        'slug',
        'subtitle',
        'description',
        'duration_days',
        'price_xaf',
        'original_price_xaf',
        'currency',
        'features',
        'max_profiles',
        'max_devices',
        'image_url',
        'image_back_url',
        'color',
        'is_active',
        'is_featured',
        'sort_order',
        'stock',
        'synced_at',
    ];

    protected $casts = [
        'features'      => 'array',
        'is_active'     => 'boolean',
        'is_featured'   => 'boolean',
        'duration_days' => 'integer',
        'price_xaf'     => 'integer',
        'sort_order'    => 'integer',
        'stock'         => 'integer',
        'plan_id'            => 'integer',
        'original_price_xaf' => 'integer',
        'max_profiles'       => 'integer',
        'max_devices'        => 'integer',
        'synced_at'          => 'datetime',
    ];

    /** Rayon du catalogue auquel Daywatch appartient (cf. config/catalog.php). */
    public const CATEGORY_ID = 5;

    protected static function booted(): void
    {
        static::creating(function (self $product) {
            if (empty($product->slug)) {
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'daywatch';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /**
     * Format compatible avec les produits API afrikard pour réutiliser
     * la vue boutique sans branche supplémentaire.
     */
    public function toCatalogItem(): array
    {
        return [
            'id'          => 'daywatch_' . $this->id,
            'internalId'  => 'daywatch_' . $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            // Champs du classifieur. Daywatch ne passe pas par
            // `processCatalogItem()` (il n'est pas issu d'afrikard) : sans eux,
            // il serait trié à score 0, donc en bas du rayon « Populaire » —
            // alors que c'est le seul produit vendu en FCFA, sans contrainte
            // de pays ni de compte étranger.
            'redeem_model'   => 'global',
            'visible'        => true,
            'matched_by'     => 'daywatch',
            'hidden_reason'  => null,
            'priority_score' => (int) config('catalog.priority.daywatch_score', 150),
            'price'      => [
                'min'          => $this->price_xaf,
                'max'          => $this->price_xaf,
                'currencyCode' => $this->currency,
            ],
            // Valeur faciale = prix barré Daywatch. C'est ce que compare
            // `ProductPricing::bestSavingPercent` pour afficher « −30 % » et
            // alimenter le tri « promo ». Sans elle, une remise réelle de
            // 4 500 F sur l'abonnement annuel restait invisible.
            'minFaceValue' => $this->original_price_xaf ?: $this->price_xaf,
            'maxFaceValue' => $this->original_price_xaf ?: $this->price_xaf,
            'cardType' => [
                'id'          => 'daywatch_' . $this->id,
                'internalId'  => 'daywatch_' . $this->id,
                'name'        => $this->name,
                'logoUrl'     => $this->image_url,
                'countryCode' => 'GA',
                'description' => $this->description,
                'categories'  => [static::catalogCategory()],
                'region'      => 'africa',
                'popular_in_africa' => true,
            ],
            'meta' => [
                'source'        => 'daywatch',
                'duration_days' => $this->duration_days,
                'max_profiles'  => $this->max_profiles,
                'max_devices'   => $this->max_devices,
                'image_back'    => $this->image_back_url,
                'features'      => $this->features ?? [],
                'color'         => $this->color,
            ],
        ];
    }

    /**
     * Le rayon tel que l'attendent les filtres de la boutique : nom, icône et
     * emoji viennent de `config/catalog.php`, jamais recopiés en dur — sinon
     * renommer le rayon dans la config laisserait Daywatch avec l'ancien nom.
     *
     * @return array{id:int, name:string, icon:?string, emoji:?string}
     */
    public static function catalogCategory(): array
    {
        $rayon = config('catalog.categories.' . self::CATEGORY_ID, []);

        return [
            'id'    => self::CATEGORY_ID,
            'name'  => $rayon['name'] ?? 'Daywatch',
            'icon'  => $rayon['icon'] ?? 'tv',
            'emoji' => $rayon['emoji'] ?? '📺',
        ];
    }
}
