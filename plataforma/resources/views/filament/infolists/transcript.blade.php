<div class="space-y-2 max-h-[32rem] overflow-y-auto rounded-lg bg-gray-950/40 p-4">
    @forelse ($getState() ?? [] as $m)
        <div class="flex {{ ($m['role'] ?? '') === 'user' ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm {{ ($m['role'] ?? '') === 'user' ? 'bg-primary-600 text-white' : 'bg-gray-800 text-gray-100' }}">
                <div class="whitespace-pre-line">{{ str_replace('**', '', $m['text'] ?? '') }}</div>
                @if (! empty($m['cards']))<div class="mt-1 text-xs opacity-70">{{ count($m['cards']) }} veículo(s) sugerido(s): {{ collect($m['cards'])->pluck('titulo')->implode(', ') }}</div>@endif
                <div class="mt-1 text-[10px] opacity-60">{{ isset($m['at']) ? \Illuminate\Support\Carbon::parse($m['at'])->format('d/m H:i') : '' }}</div>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400">Sem mensagens.</p>
    @endforelse
</div>
