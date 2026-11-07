<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\LeadService;
use App\Support\Whatsapp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Registra o clique como lead tipo "whatsapp" e redireciona para wa.me com a mensagem pronta.
 */
class WhatsappController extends Controller
{
    public function __invoke(Request $request, LeadService $leads, ?Vehicle $vehicle = null): RedirectResponse
    {
        if (! $request->session()->has('wa_click_'.($vehicle?->id ?? 0))) {
            $leads->whatsappClick($vehicle, ['utm' => $request->only('utm_source', 'utm_medium', 'utm_campaign'), 'referer' => $request->headers->get('referer')]);
            $request->session()->put('wa_click_'.($vehicle?->id ?? 0), true);
        }

        $url = $vehicle ? Whatsapp::linkForVehicle($vehicle) : Whatsapp::link($request->string('msg')->toString() ?: null);

        return redirect()->away($url);
    }
}
