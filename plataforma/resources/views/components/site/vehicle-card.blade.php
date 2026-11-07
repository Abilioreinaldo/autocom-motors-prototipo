@props(['vehicle', 'compact' => false])
@php($v = $vehicle)
<article class="card">
    <a href="{{ $v->url }}" class="relative block aspect-[16/10] overflow-hidden bg-steel">
        @if ($v->cover_url)
            <img src="{{ $v->cover_url }}" alt="{{ $v->titulo_completo }}" loading="lazy" class="h-full w-full object-cover transition duration-500 hover:scale-[1.04]">
        @else
            <div class="nophoto">Sem foto</div>
        @endif
        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
            @if ($v->destaque)<span class="badge badge-brand">Destaque</span>@endif
            @if ($v->is_oferta)<span class="badge badge-offer">Oferta</span>@endif
            @if ($v->status === \App\Enums\VehicleStatus::Reservado)<span class="badge">Reservado</span>@endif
        </div>
    </a>
    <div class="flex flex-1 flex-col gap-3 p-4">
        <h3 class="font-display text-lg font-bold leading-tight" style="font-stretch:112%">
            <a href="{{ $v->url }}" class="hover:text-brand-hi">{{ $v->titulo }}</a>
            <small class="mt-1 block truncate font-sans text-sm font-normal text-muted" style="font-stretch:100%">{{ $v->versao }}</small>
        </h3>
        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[0.82rem] text-muted">
            <span>{{ $v->ano_fab }}/{{ $v->ano_mod }}</span>
            <span>{{ $v->km_formatado }}</span>
            <span>{{ $v->cambio?->getLabel() ?? '—' }}</span>
            <span>{{ $v->combustivel?->getLabel() ?? '—' }}</span>
        </div>
        <div class="mt-auto flex items-end justify-between gap-2 border-t line pt-3">
            <div>
                @if ($v->is_oferta)<div class="font-mono text-xs text-dim line-through">R$ {{ number_format((float) $v->preco_anterior, 0, ',', '.') }}</div>@endif
                <div class="price">{{ $v->preco_formatado }}</div>
            </div>
            <a href="{{ route('whatsapp', $v) }}" target="_blank" rel="noopener" class="btn btn-wa btn-sm" aria-label="Falar no WhatsApp sobre {{ $v->titulo }}">
                <x-site.icon name="whatsapp" class="w-4 h-4" /> WhatsApp
            </a>
        </div>
    </div>
</article>
