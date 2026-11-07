<div class="panel grid gap-6 p-6 md:grid-cols-[1.2fr_1fr]" id="simulador">
    <div class="grid gap-5">
        <div>
            <div class="eyebrow">Financiamento</div>
            <h2 class="mt-2 font-display text-2xl font-bold md:text-3xl">Simule sua parcela</h2>
            @if ($this->vehicle)<p class="mt-1 text-sm text-muted">{{ $this->vehicle->titulo_completo }}</p>@endif
        </div>

        <div class="grid gap-2">
            <div class="flex justify-between text-sm text-muted">Valor do veículo <b class="font-mono text-fg">R$ {{ number_format($preco, 0, ',', '.') }}</b></div>
            @if (! $this->vehicle)
                <input type="range" min="20000" max="400000" step="1000" wire:model.live="preco">
            @endif
        </div>
        <div class="grid gap-2">
            <div class="flex justify-between text-sm text-muted">Entrada <b class="font-mono text-fg">R$ {{ number_format($entrada, 0, ',', '.') }} ({{ $preco > 0 ? round($entrada / $preco * 100) : 0 }}%)</b></div>
            <input type="range" min="0" max="{{ max(1000, $preco - 1000) }}" step="500" wire:model.live="entrada">
        </div>
        <div class="grid gap-2">
            <span class="lbl">Prazo</span>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($this->calc->terms() as $n)
                    <button type="button" wire:click="$set('prazo', {{ $n }})" class="h-10 min-w-14 flex-1 rounded-lg border font-mono text-sm transition {{ $prazo === $n ? 'bg-brand border-brand text-white' : 'line2 text-muted hover:text-fg' }}">{{ $n }}x</button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid content-center gap-3 rounded-lg border line2 bg-steel p-6">
        @if ($this->resultado)
            <span class="lbl">{{ $prazo }} parcelas de</span>
            <div class="font-display text-4xl font-extrabold tabular-nums" style="font-stretch:118%">R$ {{ number_format($this->resultado['parcela'], 2, ',', '.') }}</div>
            <div class="grid gap-1 text-sm text-muted">
                <div class="flex justify-between">Valor financiado <b class="font-mono font-medium text-fg">R$ {{ number_format($this->resultado['financiado'], 0, ',', '.') }}</b></div>
                <div class="flex justify-between">Taxa <b class="font-mono font-medium text-fg">{{ number_format($this->resultado['taxa_mensal'], 2, ',', '.') }}% a.m.</b></div>
                <div class="flex justify-between">Total a pagar <b class="font-mono font-medium text-fg">R$ {{ number_format($this->resultado['total'], 0, ',', '.') }}</b></div>
            </div>
        @else
            <p class="text-sm text-muted">Ajuste os valores para simular.</p>
        @endif

        @if ($enviado)
            <div class="rounded-lg border p-3 text-sm" style="border-color:rgba(63,191,127,.4);background:rgba(63,191,127,.06)">
                <b class="block">Simulação registrada!</b> Um consultor vai te chamar. Se preferir, continue no WhatsApp:
                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-wa btn-sm mt-2">Abrir WhatsApp</a>
            </div>
        @else
            <form wire:submit="enviar" class="grid gap-2 pt-2">
                <input type="text" wire:model="nome" placeholder="Seu nome" class="input" autocomplete="name">
                @error('nome')<span class="text-xs text-bad">{{ $message }}</span>@enderror
                <input type="tel" wire:model="fone" placeholder="WhatsApp com DDD" class="input" autocomplete="tel">
                @error('fone')<span class="text-xs text-bad">{{ $message }}</span>@enderror
                <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled">Receber proposta no WhatsApp</button>
            </form>
        @endif
        <p class="disclaimer">{{ $this->calc->disclaimer() }}</p>
    </div>
</div>
