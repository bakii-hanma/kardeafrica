<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DaywatchProduct extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'subtitle',
        'description',
        'duration_days',
        'price_xaf',
        'currency',
        'features',
        'image_url',
        'color',
        'is_active',
        'is_featured',
        'sort_order',
        'stock',
    ];

    protected $casts = [
        'features'      => 'array',
        'is_active'     => 'boolean',
        'is_featured'   => 'boolean',
        'duration_days' => 'integer',
        'price_xaf'     => 'integer',
        'sort_order'    => 'integer',
        'stock'         => 'integer',
    ];

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
            'id'         => 'daywatch_' . $this->id,
            'internalId' => 'daywatch_' . $this->id,
            'name'       => $this->name,
            'price'      => [
                'min'          => $this->price_xaf,
                'max'          => $this->price_xaf,
                'currencyCode' => $this->currency,
            ],
            'cardType' => [
                'id'         => 'daywatch_' . $this->id,
                'internalId' => 'daywatch_' . $this->id,
                'name'       => $this->name,
                'logoUrl'    => $this->image_url,
                'countryCode'=> 'GA',
            ],
            'meta' => [
                'source'        => 'daywatch',
                'duration_days' => $this->duration_days,
                'features'      => $this->features ?? [],
                'color'         => $this->color,
            ],
        ];
    }
}
