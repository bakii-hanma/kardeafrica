<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_PREFIX = 'app_setting_';
    private const CACHE_TTL    = 300; // 5 minutes

    /**
     * Récupère une valeur de paramètre (avec cache).
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $row = static::where('key', $key)->first();
            return $row ? $row->value : $default;
        });
    }

    /**
     * Enregistre/met à jour une valeur (et invalide le cache).
     */
    public static function set(string $key, $value): void
    {
        $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        static::updateOrCreate(['key' => $key], ['value' => $stored]);

        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Helper booléen — convertit "1"/"0"/"true"/"false" en bool.
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $v = static::get($key, $default ? '1' : '0');
        if (is_bool($v)) return $v;
        return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }

    // ---- Helpers spécifiques ----

    public static function isMaintenanceMode(): bool
    {
        return static::bool('maintenance_mode', false);
    }

    public static function setMaintenanceMode(bool $on): void
    {
        static::set('maintenance_mode', $on);
    }
}
