<div>
    <button type="button" wire:click="abrir" aria-label="Abrir assistente virtual"
        class="fixed right-4 bottom-4 z-[70] flex h-14 items-center gap-2 rounded-full pl-1.5 pr-5 text-sm font-semibold text-white shadow-xl transition hover:bg-[#1a2027] {{ $aberto ? 'hidden' : '' }}" style="background:#0e1216">
        <span class="relative grid h-11 w-11 place-items-center rounded-full bg-brand">
            <x-site.icon name="bot" class="w-6 h-6" />
            <i class="absolute right-0.5 bottom-0.5 h-3 w-3 rounded-full border-2 bg-ok" style="border-color:#0e1216"></i>
        </span>
        <span class="hidden sm:inline">Assistente</span>
    </button>

    @if ($aberto)
        <div class="pop fixed right-2 bottom-2 z-[71] flex h-[min(620px,calc(100vh-1rem))] w-[min(390px,calc(100vw-1rem))] flex-col overflow-hidden rounded-2xl border line bg-graphite shadow-2xl sm:right-4 sm:bottom-4"
             x-data x-on:bot-scroll.window="$nextTick(() => { const b = $refs.body; b.scrollTop = b.scrollHeight })" x-init="$nextTick(() => { $refs.body.scrollTop = $refs.body.scrollHeight })">
            <div class="flex items-center gap-3 px-4 py-3 text-white" style="background:#0e1216">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9">
                <div>
                    <b class="block font-display text-base">{{ $this->nome }}</b>
                    <small class="flex items-center gap-1.5 text-xs text-muted"><i class="h-1.5 w-1.5 rounded-full bg-ok"></i> Online agora</small>
                </div>
                <button type="button" wire:click="fechar" class="ml-auto grid h-9 w-9 place-items-center rounded-full border border-white/20" aria-label="Fechar"><x-site.icon name="close" class="w-4 h-4" /></button>
            </div>

            <div class="flex flex-1 flex-col gap-2 overflow-y-auto bg-ink p-4" x-ref="body">
                @foreach ($mensagens as $m)
                    @if ($m['role'] === 'user')
                        <div class="max-w-[86%] self-end rounded-2xl rounded-br-sm bg-brand px-3 py-2 text-sm text-white">{{ $m['text'] }}</div>
                    @else
                        <div class="max-w-[86%] self-start rounded-2xl rounded-bl-sm border line bg-graphite px-3 py-2 text-sm leading-relaxed">{!! nl2br(e(str_replace('**', '', $m['text']))) !!}</div>
                        @if (! empty($m['cards']))
                            <div class="grid gap-2">
                                @foreach ($m['cards'] as $c)
                                    <a href="{{ $c['url'] }}" class="grid grid-cols-[96px_1fr] items-center gap-3 rounded-xl border line bg-graphite p-1.5 transition hover:border-brand">
                                        <span class="aspect-[16/10] overflow-hidden rounded-lg bg-steel">@if ($c['foto'])<img src="{{ $c['foto'] }}" alt="" class="h-full w-full object-cover">@endif</span>
                                        <span class="min-w-0"><b class="block truncate text-sm">{{ $c['titulo'] }}</b><small class="block truncate text-xs text-muted">{{ $c['versao'] }} · {{ $c['ano'] }}</small><span class="mt-0.5 block font-display font-bold">{{ $c['preco'] }}</span></span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        @if (! empty($m['links']))
                            <div class="flex flex-wrap gap-2">
                                @foreach ($m['links'] as [$label, $url])
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-wa btn-sm">{{ $label }}</a>
                                @endforeach
                            </div>
                        @endif
                    @endif
                @endforeach
                <div wire:loading wire:target="enviar" class="typing flex gap-1 self-start rounded-2xl border line bg-graphite px-3 py-3"><i class="h-1.5 w-1.5 rounded-full bg-dim"></i><i class="h-1.5 w-1.5 rounded-full bg-dim"></i><i class="h-1.5 w-1.5 rounded-full bg-dim"></i></div>
            </div>

            @if ($quick)
                <div class="flex flex-wrap gap-1.5 border-t line px-4 pt-2">
                    @foreach ($quick as $q)
                        <button type="button" wire:click="enviar('{{ addslashes($q) }}')" class="rounded-full border px-3 py-1 text-xs font-semibold text-brand transition hover:bg-brand/10" style="border-color:rgba(21,149,184,.45)">{{ $q }}</button>
                    @endforeach
                </div>
            @endif
            <form wire:submit="enviar" class="flex gap-2 px-4 py-3">
                <input type="text" wire:model="texto" placeholder="Escreva sua mensagem…" class="h-11 flex-1 rounded-full border line2 bg-ink px-4 text-sm outline-none focus:border-brand" maxlength="500" autocomplete="off">
                <button type="submit" class="grid h-11 w-11 place-items-center rounded-full bg-brand text-white" aria-label="Enviar"><x-site.icon name="send" class="w-5 h-5" /></button>
            </form>
            <p class="px-4 pb-2 text-[0.65rem] text-dim">Assistente automático. Valores ilustrativos; um consultor confirma tudo.</p>
        </div>
    @endif
</div>
