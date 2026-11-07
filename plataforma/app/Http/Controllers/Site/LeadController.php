<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Endpoint HTTP clássico (fallback/API) para criação de leads. Os formulários do site usam Livewire.
 */
class LeadController extends Controller
{
    public function store(Request $request, LeadService $leads, string $tipo): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'fone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'mensagem' => ['nullable', 'string', 'max:2000'],
            'veiculo_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'meta' => ['nullable', 'array'],
            'website' => ['nullable', 'max:0'], // honeypot
        ]);

        $vehicle = ! empty($data['veiculo_id']) ? Vehicle::find($data['veiculo_id']) : null;
        $lead = $leads->create($tipo, $data, $vehicle);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'id' => $lead->id, 'whatsapp' => $vehicle?->whatsappUrl()]);
        }

        return back()->with('lead_ok', 'Recebemos seus dados! Em instantes um consultor entra em contato.');
    }
}
