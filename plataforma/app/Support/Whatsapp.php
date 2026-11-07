<?php

namespace App\Support;

use App\Models\Vehicle;

class Whatsapp
{
    /** Mantém apenas dígitos e garante DDI 55 */
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        return $digits;
    }

    public static function number(): string
    {
        return self::normalize(Settings::get('whatsapp.numero', config('autocom.whatsapp.numero')));
    }

    public static function link(?string $mensagem = null, ?string $numero = null): string
    {
        $numero = self::normalize($numero) ?: self::number();
        $mensagem ??= Settings::get('whatsapp.mensagem_padrao', config('autocom.whatsapp.mensagem_padrao'));

        return 'https://wa.me/'.$numero.'?text='.rawurlencode($mensagem);
    }

    public static function messageForVehicle(Vehicle $vehicle): string
    {
        $template = Settings::get('whatsapp.mensagem_veiculo', config('autocom.whatsapp.mensagem_veiculo'));

        return str_replace('{veiculo}', $vehicle->nome_whatsapp, $template);
    }

    public static function linkForVehicle(Vehicle $vehicle, ?string $mensagem = null): string
    {
        return self::link($mensagem ?? self::messageForVehicle($vehicle));
    }
}
