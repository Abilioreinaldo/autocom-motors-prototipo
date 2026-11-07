<?php

namespace App\Integrations\Webmotors;

use InvalidArgumentException;

/**
 * Extrai o ID do anúncio a partir de uma URL da Webmotors.
 * Ex.: https://www.webmotors.com.br/comprar/chevrolet/onix/.../2022-2023/54810001  → 54810001
 *      https://www.webmotors.com.br/comprar/.../54810001?utm=x#foo                 → 54810001
 *      54810001                                                                   → 54810001
 */
class WebmotorsLinkParser
{
    public static function extractId(string $input): string
    {
        $input = trim($input);
        if ($input === '') {
            throw new InvalidArgumentException('Informe o link do anúncio.');
        }

        if (preg_match('/^\d{5,12}$/', $input)) {
            return $input;
        }

        $url = preg_match('~^https?://~i', $input) ? $input : 'https://'.$input;
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $allowed = config('webmotors.allowed_hosts', ['webmotors.com.br', 'www.webmotors.com.br']);
        $hostOk = collect($allowed)->contains(fn ($h) => $host === $h || str_ends_with($host, '.'.$h));

        if (! $hostOk) {
            throw new InvalidArgumentException('O link informado não é da Webmotors.');
        }

        $path = rtrim($parts['path'] ?? '', '/');
        $last = basename($path);

        if (preg_match('/^(\d{5,12})$/', $last, $m)) {
            return $m[1];
        }

        // alguns links trazem o id como "...-54810001" ou "id=54810001"
        if (preg_match('/(\d{5,12})(?:\.html?)?$/', $last, $m)) {
            return $m[1];
        }
        parse_str($parts['query'] ?? '', $q);
        foreach (['id', 'codigo', 'anuncio'] as $k) {
            if (! empty($q[$k]) && preg_match('/^\d{5,12}$/', (string) $q[$k])) {
                return (string) $q[$k];
            }
        }

        throw new InvalidArgumentException('Não foi possível identificar o ID do anúncio no link.');
    }

    public static function isWebmotorsUrl(string $input): bool
    {
        try {
            self::extractId($input);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
