<div>
    <section class="border-b line py-10">
        <div class="wrap">
            <div class="mb-3 text-sm text-dim"><a href="{{ route('home') }}" class="text-muted hover:text-fg">Início</a> / Estoque</div>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-bold md:text-4xl">Estoque completo</h1>
                    <p class="mt-2 text-muted">Seminovos revisados e com procedência. Filtre e encontre o seu.</p>
                </div>
                <label class="relative w-full max-w-md">
                    <x-site.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-dim" />
                    <input type="search" wire:model.live.debounce.400ms="busca" placeholder="Buscar por marca, modelo, versão ou cor" class="input pl-10">
                </label>
            </div>
        </div>
    </section>

    <section class="wrap grid gap-8 py-8 lg:grid-cols-[290px_1fr] lg:items-start">
        <div>
            <button type="button" wire:click="$toggle('mostrarFiltros')" class="btn btn-block mb-4 lg:hidden">{{ $mostrarFiltros ? 'Ocultar filtros' : 'Mostrar filtros' }}</button>
            <aside class="panel {{ $mostrarFiltros ? 'grid' : 'hidden lg:grid' }} gap-5 p-5 lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-auto">
                <h3 class="flex items-center justify-between text-base font-bold">Filtros <button type="button" wire:click="limpar" class="text-sm font-medium text-brand-hi">Limpar</button></h3>

                <div class="grid gap-2">
                    <span class="lbl">Marca</span>
                    <select wire:model.live="marca" class="input">
                        <option value="">Todas</option>
                        @foreach ($this->marcas as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                    </select>
                </div>
                @if ($this->modelos)
                    <div class="grid gap-2">
                        <span class="lbl">Modelo</span>
                        <select wire:model.live="modelo" class="input">
                            <option value="">Todos</option>
                            @foreach ($this->modelos as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                        </select>
                    </div>
                @endif
                @if ($this->versoes)
                    <div class="grid gap-2">
                        <span class="lbl">Versão</span>
                        <select wire:model.live="versao" class="input">
                            <option value="">Todas</option>
                            @foreach ($this->versoes as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                        </select>
                    </div>
                @endif

                <div class="grid gap-2">
                    <span class="lbl">Ano (modelo)</span>
                    <div class="grid grid-cols-2 gap-2">
                        <select wire:model.live="anoMin" class="input"><option value="">De</option>@foreach ($this->anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</select>
                        <select wire:model.live="anoMax" class="input"><option value="">Até</option>@foreach ($this->anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</select>
                    </div>
                </div>

                <div class="grid gap-2">
                    <span class="lbl">Preço (R$)</span>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" inputmode="numeric" step="1000" min="0" wire:model.live.debounce.500ms="precoMin" placeholder="Mín." class="input">
                        <input type="number" inputmode="numeric" step="1000" min="0" wire:model.live.debounce.500ms="precoMax" placeholder="Máx." class="input">
                    </div>
                </div>

                <div class="grid gap-2">
                    <span class="lbl">Km até</span>
                    <select wire:model.live="kmMax" class="input">
                        <option value="">Qualquer</option>
                        @foreach ([20000, 40000, 60000, 80000, 100000, 150000] as $k)<option value="{{ $k }}">{{ number_format($k, 0, ',', '.') }} km</option>@endforeach
                    </select>
                </div>

                <div class="grid gap-2">
                    <span class="lbl">Carroceria</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($carrocerias as $c)<button type="button" wire:click="toggle('carroceria', '{{ $c->value }}')" class="chip {{ in_array($c->value, $carroceria) ? 'on' : '' }}">{{ $c->getLabel() }}</button>@endforeach
                    </div>
                </div>
                <div class="grid gap-2">
                    <span class="lbl">Câmbio</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($cambios as $c)<button type="button" wire:click="toggle('cambio', '{{ $c->value }}')" class="chip {{ in_array($c->value, $cambio) ? 'on' : '' }}">{{ $c->getLabel() }}</button>@endforeach
                    </div>
                </div>
                <div class="grid gap-2">
                    <span class="lbl">Combustível</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($combustiveis as $c)<button type="button" wire:click="toggle('combustivel', '{{ $c->value }}')" class="chip {{ in_array($c->value, $combustivel) ? 'on' : '' }}">{{ $c->getLabel() }}</button>@endforeach
                    </div>
                </div>
                @if ($this->cores)
                    <div class="grid gap-2">
                        <span class="lbl">Cor</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($this->cores as $c)<button type="button" wire:click="toggle('cor', '{{ $c }}')" class="chip {{ in_array($c, $cor) ? 'on' : '' }}">{{ $c }}</button>@endforeach
                        </div>
                    </div>
                @endif
                <label class="flex items-center gap-2 text-sm text-muted"><input type="checkbox" wire:model.live="oferta" class="accent-brand h-4 w-4"> Somente ofertas</label>
            </aside>
        </div>

        <div>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <b class="font-display text-lg">{{ $veiculos->total() }} {{ $veiculos->total() === 1 ? 'veículo' : 'veículos' }}</b>
                <label class="flex items-center gap-2 text-sm text-muted">Ordenar
                    <select wire:model.live="ordem" class="input h-10 w-auto">
                        <option value="recentes">Mais recentes</option>
                        <option value="menor_preco">Menor preço</option>
                        <option value="maior_preco">Maior preço</option>
                        <option value="menor_km">Menor km</option>
                        <option value="mais_novo">Mais novos</option>
                    </select>
                </label>
            </div>

            @if ($this->filtrosAtivos)
                <div class="mb-4 flex flex-wrap gap-1.5">
                    @foreach ($this->filtrosAtivos as $f)
                        <button type="button" wire:click="removerFiltro('{{ $f['campo'] }}', {{ $f['valor'] !== null ? "'".addslashes($f['valor'])."'" : 'null' }})" class="chip on">{{ $f['label'] }} <span aria-hidden="true">×</span></button>
                    @endforeach
                </div>
            @endif

            <div wire:loading.class="opacity-50" class="transition">
                @if ($veiculos->isEmpty())
                    <div class="rounded-[14px] border border-dashed line2 px-6 py-14 text-center text-muted">
                        <b class="mb-1 block font-display text-xl text-fg">Nenhum veículo com esses filtros</b>
                        Ajuste a busca ou <a href="{{ route('whatsapp', ['msg' => 'Olá! Procuro um carro que não encontrei no site da Autocom Motors.']) }}" target="_blank" class="text-brand-hi underline">peça no WhatsApp</a> — buscamos para você.
                    </div>
                @else
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($veiculos as $v)
                            <x-site.vehicle-card :vehicle="$v" :key="$v->id" />
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $veiculos->links() }}</div>
                @endif
            </div>
        </div>
    </section>
</div>
