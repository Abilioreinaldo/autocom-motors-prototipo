<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Configurações editáveis no painel (tabela settings), com fallback para config/autocom.php.
 */
class Settings
{
    protected const CACHE_KEY = 'autocom.settings';

    /** @return array<string,string|null> */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return Setting::query()->pluck('value', 'key')->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        return $default ?? config('autocom.'.$key);
    }

    public static function set(string $key, mixed $value, string $group = 'geral'): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : $value, 'group' => $group]);
        self::flush();
    }

    public static function setMany(array $values, string $group = 'geral'): void
    {
        foreach ($values as $k => $v) {
            Setting::updateOrCreate(['key' => $k], ['value' => is_array($v) ? json_encode($v) : $v, 'group' => $group]);
        }
        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function float(string $key, float $default): float
    {
        $v = self::get($key, $default);

        return (float) str_replace(',', '.', (string) $v);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return filter_var(self::get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
}
