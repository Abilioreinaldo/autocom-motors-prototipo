@php($v = $vehicle)
<x-layouts.site :title="$v->titulo_completo" :description="Str::limit($v->descricao ?? $v->titulo_completo, 150)" :og-image="$v->cover_url">
<div class="wrap pt-6 text-sm text-dim"><a href="{{ route('home') }}" class="text-muted hover:text-fg">Início</a> / <a href="{{ route('estoque') }}" class="text-muted hover:text-fg">Estoque</a> / {{ $v->titulo }}</div>

<section class="wrap grid items-start gap-8 py-6 pb-16 lg:grid-cols-[1.55fr_1fr]">
    {{-- Galeria --}}
    <div class="min-w-0" x-data="{ photos: {{ Js::from($v->photos->map->url->values()) }}, i: 0, open: false, set(n){ this.i = n }, next(){ this.i = (this.i + 1) % this.photos.length }, prev(){ this.i = (this.i - 1 + this.photos.length) % this.photos.length } }">
        <div class="relative aspect-[16/10] cursor-zoom-in overflow-hidden rounded-[14px] border line bg-steel" @click="open = photos.length > 0">
            <template x-if="photos.length"><img :src="photos[i]" alt="{{ $v->titulo_completo }}" class="h-full w-full object-cover"></template>
            @if ($v->photos->isEmpty())<div class="nophoto">Sem foto</div>@endif
            <span class="absolute left-3 bottom-3 rounded-md px-2 py-1 font-mono text-xs text-white" style="background:rgba(14,18,22,.82)"><span x-text="photos.length ? i + 1 : 0"></span>/<span x-text="photos.length"></span></span>
            <span class="absolute right-3 bottom-3 rounded-md px-2 py-1 font-mono text-xs text-white" style="background:rgba(14,18,22,.82)">Ampliar</span>
        </div>
        @if ($v->photos->count() > 1)
            <div class="mt-2 grid grid-cols-4 gap-2 sm:grid-cols-6">
                @foreach ($v->photos as $n => $p)
                    <button type="button" @click="set({{ $n }})" :class="i === {{ $n }} ? 'opacity-100 border-brand' : 'opacity-60'" class="aspect-[16/10] overflow-hidden rounded-lg border line bg-steel transition hover:opacity-100">
                        <img src="{{ $p->url }}" alt="{{ $p->alt ?? '' }}" loading="lazy" class="h-full w-full object-cover">
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Lightbox --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-[90] grid grid-rows-[auto_1fr_auto] bg-ink" @keydown.escape.window="open = false" @keydown.right.window="open && next()" @keydown.left.window="open && prev()">
            <div class="flex items-center justify-between px-4 py-3 text-sm text-muted"><span>{{ $v->titulo_completo }}</span><button type="button" @click="open = false" class="grid h-10 w-10 place-items-center rounded-full border line2" aria-label="Fechar"><x-site.icon name="close" class="w-4 h-4" /></button></div>
            <div class="relative grid place-items-center overflow-hidden px-4">
                <img :src="photos[i]" alt="" class="max-h-full w-[min(1200px,100%)] rounded-lg object-contain">
                <button type="button" @click.stop="prev()" class="absolute left-4 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full border line2" style="background:rgba(18,21,25,.85)" aria-label="Anterior">‹</button>
                <button type="button" @click.stop="next()" class="absolute right-4 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full border line2" style="background:rgba(18,21,25,.85)" aria-label="Próxima">›</button>
            </div>
            <div class="py-3 text-center font-mono text-xs text-dim"><span x-text="i + 1"></span> / <span x-text="photos.length"></span> · use as setas do teclado</div>
        </div>

        {{-- Ficha --}}
        <div class="mt-7 grid grid-cols-2 overflow-hidden rounded-[14px] border line sm:grid-cols-4" style="gap:1px;background:rgba(214,232,240,.09)">
            @foreach ([['Ano', "{$v->ano_fab}/{$v->ano_mod}"], ['Km', $v->km_formatado], ['Câmbio', $v->cambio?->getLabel() ?? '—'], ['Combustível', $v->combustivel?->getLabel() ?? '—'], ['Carroceria', $v->carroceria?->getLabel() ?? '—'], ['Cor', $v->cor ?? '—'], ['Motor', $v->motor ?? '—'], ['Portas', $v->portas ?? '—'], ['Final da placa', $v->placa_final ?? '—'], ['Código', '#'.str_pad((string) $v->id, 5, '0', STR_PAD_LEFT)]] as [$k, $val])
                <div class="bg-graphite p-4"><small class="block font-mono text-[0.68rem] uppercase tracking-widest text-dim">{{ $k }}</small><b class="mt-1 block font-semibold">{{ $val }}</b></div>
            @endforeach
        </div>

        @if ($v->descricao)
            <div class="mt-10"><h2 class="mb-3 font-display text-2xl font-bold">Sobre este veículo</h2><p class="max-w-[65ch] whitespace-pre-line text-muted">{{ $v->descricao }}</p></div>
        @endif

        @if ($v->opcionais)
            <div class="mt-10"><h2 class="mb-3 font-display text-2xl font-bold">Opcionais e itens de série</h2>
                <ul class="grid gap-x-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($v->opcionais as $o)<li class="flex items-center gap-2 border-b line py-2 text-sm text-muted"><i class="h-1.5 w-1.5 flex-none rounded-sm bg-brand"></i>{{ $o }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="mt-10"><livewire:site.simulador :vehicle="$v" /></div>

        <div class="mt-10 grid gap-5 md:grid-cols-2">
            <livewire:site.lead-form tipo="proposta" :vehicle="$v" />
            <livewire:site.lead-form tipo="troca" :vehicle="$v" titulo="Usar meu carro na troca" />
        </div>
    </div>

    {{-- Painel lateral --}}
    <aside class="panel grid gap-4 p-6 shadow-[var(--shadow-deep)] lg:sticky lg:top-24">
        <div>
            <div class="flex flex-wrap gap-1.5">
                @if ($v->destaque)<span class="badge badge-brand">Destaque</span>@endif
                @if ($v->is_oferta)<span class="badge badge-offer">Oferta</span>@endif
                @if ($v->status === \App\Enums\VehicleStatus::Reservado)<span class="badge">Reservado</span>@endif
            </div>
            <h1 class="mt-3 font-display text-3xl font-bold">{{ $v->titulo }}</h1>
            <p class="mt-1 text-muted">{{ $v->versao }} · {{ $v->ano_fab }}/{{ $v->ano_mod }}</p>
        </div>
        <div class="flex flex-wrap items-baseline gap-3">
            <span class="price text-4xl">{{ $v->preco_formatado }}</span>
            @if ($v->is_oferta)<span class="font-mono text-sm text-dim line-through">R$ {{ number_format((float) $v->preco_anterior, 0, ',', '.') }}</span>@endif
        </div>
        @php($sim = app(\App\Services\FinancingCalculator::class))
        @php($parcela = $sim->installment((float) $v->preco * (1 - $sim->minDownPaymentPercent() / 100), 48, $sim->defaultRate()))
        <div class="flex justify-between gap-3 rounded-lg border px-4 py-3 text-sm" style="background:rgba(21,149,184,.14);border-color:rgba(21,149,184,.35)">
            <span class="text-muted">48x de</span><b class="text-brand-hi">R$ {{ number_format($parcela, 2, ',', '.') }}*</b>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('whatsapp', $v) }}" target="_blank" rel="noopener" class="btn btn-wa col-span-2"><x-site.icon name="whatsapp" class="w-4 h-4" /> Falar no WhatsApp</a>
            <a href="#simulador" class="btn">Simular</a>
            <a href="#proposta" class="btn btn-primary">Fazer proposta</a>
        </div>
        <p class="disclaimer">*{{ $sim->disclaimer() }} Entrada de {{ $sim->minDownPaymentPercent() }}%.</p>
        <div class="border-t line pt-4 text-xs text-dim">Compartilhe: <a href="https://wa.me/?text={{ rawurlencode($v->titulo_completo.' - '.$v->preco_formatado.' '.$v->url) }}" target="_blank" rel="noopener" class="text-brand-hi hover:underline">WhatsApp</a></div>
    </aside>
</section>

@if ($relacionados->isNotEmpty())
<section class="border-t line bg-graphite/40">
    <div class="wrap sec">
        <x-site.section-head eyebrow="Você também pode gostar" title="Veículos relacionados" :href="route('estoque')" />
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($relacionados as $r)<x-site.vehicle-card :vehicle="$r" />@endforeach
        </div>
    </div>
</section>
@endif
</x-layouts.site>
