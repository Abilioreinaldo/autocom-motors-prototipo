<div class="panel p-6">
    <h3 class="font-display text-xl font-bold">{{ $titulo }}</h3>
    @if ($this->vehicle)<p class="mt-1 text-sm text-muted">{{ $this->vehicle->titulo_completo }} · {{ $this->vehicle->preco_formatado }}</p>@endif

    @if ($enviado)
        <div class="mt-5 grid justify-items-center gap-2 py-4 text-center">
            <div class="grid h-14 w-14 place-items-center rounded-full border-2 text-ok" style="border-color:var(--color-ok)"><x-site.icon name="check" class="w-7 h-7" /></div>
            <b class="font-display text-lg">Recebemos seus dados!</b>
            <p class="text-sm text-muted">Um consultor entra em contato em breve. Quer adiantar? Continue no WhatsApp.</p>
            <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-wa mt-1"><x-site.icon name="whatsapp" class="w-4 h-4" /> Abrir WhatsApp</a>
        </div>
    @else
        <form wire:submit="enviar" class="mt-5 grid gap-3 sm:grid-cols-2">
            <input type="text" wire:model="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <label class="grid gap-1"><span class="lbl">Nome</span><input type="text" wire:model="nome" class="input" autocomplete="name">@error('nome')<span class="text-xs text-bad">{{ $message }}</span>@enderror</label>
            <label class="grid gap-1"><span class="lbl">WhatsApp</span><input type="tel" wire:model="fone" class="input" placeholder="(15) 9 9999-9999" autocomplete="tel">@error('fone')<span class="text-xs text-bad">{{ $message }}</span>@enderror</label>
            <label class="grid gap-1 sm:col-span-2"><span class="lbl">E-mail (opcional)</span><input type="email" wire:model="email" class="input" autocomplete="email">@error('email')<span class="text-xs text-bad">{{ $message }}</span>@enderror</label>

            @if ($tipo === 'proposta')
                <label class="grid gap-1 sm:col-span-2"><span class="lbl">Valor da proposta (R$)</span><input type="number" step="500" min="1000" wire:model="valorProposta" class="input">@error('valorProposta')<span class="text-xs text-bad">{{ $message }}</span>@enderror</label>
            @endif

            @if ($tipo === 'troca')
                <label class="grid gap-1"><span class="lbl">Marca do seu carro</span><input type="text" wire:model="trocaMarca" class="input">@error('trocaMarca')<span class="text-xs text-bad">{{ $message }}</span>@enderror</label>
                <label class="grid gap-1"><span class="lbl">Modelo</span><input type="text" wire:model="trocaModelo" class="input">@error('trocaModelo')<span class="text-xs text-bad">{{ $message }}</span>@enderror</label>
                <label class="grid gap-1"><span class="lbl">Ano</span><input type="number" min="1990" max="{{ date('Y') + 1 }}" wire:model="trocaAno" class="input">@error('trocaAno')<span class="text-xs text-bad">{{ $message }}</span>@enderror</label>
                <label class="grid gap-1"><span class="lbl">Km</span><input type="number" min="0" step="1000" wire:model="trocaKm" class="input">@error('trocaKm')<span class="text-xs text-bad">{{ $message }}</span>@enderror</label>
            @endif

            <label class="grid gap-1 sm:col-span-2"><span class="lbl">Mensagem</span><textarea wire:model="mensagem" class="input" rows="3" placeholder="{{ $tipo === 'troca' ? 'Estado geral, revisões, detalhes…' : 'Conte o que você precisa' }}"></textarea></label>
            <button type="submit" class="btn btn-primary sm:col-span-2" wire:loading.attr="disabled">
                <span wire:loading.remove>Enviar</span><span wire:loading>Enviando…</span>
            </button>
            <p class="disclaimer sm:col-span-2">Seus dados são usados apenas para este atendimento.</p>
        </form>
    @endif
</div>
