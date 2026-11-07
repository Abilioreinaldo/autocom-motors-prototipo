<x-layouts.site :title="null">
@php($S = \App\Support\Settings::class)
{{-- HERO --}}
<section class="hero-glow relative overflow-hidden border-b line">
    <div class="wrap relative z-10 grid items-center gap-8 py-12 md:grid-cols-[1.05fr_1fr] md:py-20">
        <div>
            <div class="eyebrow">Tatuí-SP · seminovos selecionados</div>
            <h1 class="mt-4 font-display text-5xl font-extrabold leading-[0.95] tracking-[-0.03em] md:text-6xl lg:text-7xl" style="font-stretch:125%">
                {{ $hero?->titulo ? Str::before($hero->titulo, ',') : 'O seu próximo carro' }}<br><em class="not-italic text-brand-hi">{{ $hero?->titulo && Str::contains($hero->titulo, ',') ? trim(Str::after($hero->titulo, ',')) : 'já está aqui.' }}</em>
            </h1>
            <p class="mt-5 max-w-lg text-lg text-muted">{{ $hero?->subtitulo ?? 'Cada carro passa por vistoria completa, com histórico e documentação transparentes. Negocie direto no WhatsApp.' }}</p>
            <form action="{{ route('estoque') }}" method="get" class="mt-8 flex max-w-lg gap-2 rounded-full border line2 bg-graphite p-1.5 pl-5">
                <input type="search" name="q" placeholder="Buscar marca, modelo ou versão" class="min-w-0 flex-1 bg-transparent outline-none placeholder:text-dim">
                <button type="submit" class="btn btn-primary"><x-site.icon name="search" class="w-4 h-4" /> Buscar</button>
            </form>
            <div class="mt-6 flex flex-wrap gap-5 text-sm text-muted">
                <span><b class="text-fg">{{ $total }}</b> veículos em estoque</span>
                <span><b class="text-fg">Vistoria</b> de 120 itens</span>
                <span><b class="text-fg">Troca</b> e financiamento</span>
            </div>
        </div>
        <div class="relative -mx-2 overflow-hidden rounded-[20px] border line2 shadow-[var(--shadow-deep)] md:mx-0" style="aspect-ratio:16/10">
            @if ($hero?->imagem_url)
                <img src="{{ $hero->imagem_url }}" alt="{{ $hero->titulo }}" class="h-full w-full object-cover">
            @elseif ($heroVehicle?->cover_url)
                <img src="{{ $heroVehicle->cover_url }}" alt="{{ $heroVehicle->titulo_completo }}" class="h-full w-full object-cover">
            @else
                <div class="nophoto">Autocom Motors</div>
            @endif
            <div class="pointer-events-none absolute inset-0" style="background:linear-gradient(205deg,transparent 58%,rgba(4,6,8,.6))"></div>
            @if ($heroVehicle && ! $hero?->imagem_url)
                <a href="{{ $heroVehicle->url }}" class="absolute right-3 bottom-3 rounded-lg px-4 py-2.5 text-white shadow-lg" style="background:#0e1216">
                    <small class="block text-xs text-muted">{{ $heroVehicle->titulo }} {{ $heroVehicle->ano_mod }}</small>
                    <b class="font-display">{{ $heroVehicle->preco_formatado }}</b>
                </a>
            @endif
        </div>
    </div>
</section>

{{-- BUSCA RÁPIDA --}}
<section class="wrap relative z-20 -mt-10">
    <form action="{{ route('estoque') }}" method="get" class="panel grid gap-3 p-4 shadow-[var(--shadow-deep)] sm:grid-cols-2 lg:grid-cols-[repeat(4,1fr)_auto] lg:items-end">
        <label class="grid gap-1"><span class="lbl">Marca</span><select name="marca" class="input"><option value="">Todas</option>@foreach ($marcas as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></label>
        <label class="grid gap-1"><span class="lbl">Carroceria</span><select name="carroceria[]" class="input"><option value="">Todas</option>@foreach (\App\Enums\Carroceria::cases() as $c)<option value="{{ $c->value }}">{{ $c->getLabel() }}</option>@endforeach</select></label>
        <label class="grid gap-1"><span class="lbl">Preço até</span><select name="preco_max" class="input"><option value="">Qualquer</option>@foreach ([60000, 80000, 100000, 130000, 160000, 200000, 300000] as $p)<option value="{{ $p }}">R$ {{ number_format($p, 0, ',', '.') }}</option>@endforeach</select></label>
        <label class="grid gap-1"><span class="lbl">Câmbio</span><select name="cambio[]" class="input"><option value="">Qualquer</option>@foreach (\App\Enums\Cambio::cases() as $c)<option value="{{ $c->value }}">{{ $c->getLabel() }}</option>@endforeach</select></label>
        <button type="submit" class="btn btn-primary h-12 sm:col-span-2 lg:col-span-1">Buscar</button>
    </form>
</section>

{{-- DESTAQUES --}}
@if ($destaques->isNotEmpty())
<section class="wrap sec">
    <x-site.section-head eyebrow="Selecionados" title="Destaques da semana" text="Os carros mais procurados do estoque, com fotos reais e histórico verificado." :href="route('estoque')" />
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($destaques as $v)<x-site.vehicle-card :vehicle="$v" />@endforeach
    </div>
</section>
@endif

{{-- RECÉM-CHEGADOS --}}
@if ($recentes->isNotEmpty())
<section class="border-y line bg-graphite/40">
    <div class="wrap sec">
        <x-site.section-head eyebrow="Novidades" title="Recém-chegados" :href="route('estoque', ['ordem' => 'recentes'])" />
        <div class="scroller grid grid-flow-col auto-cols-[minmax(280px,1fr)] gap-5 overflow-x-auto pb-2 lg:auto-cols-[calc((100%-3.75rem)/4)]">
            @foreach ($recentes as $v)<x-site.vehicle-card :vehicle="$v" />@endforeach
        </div>
    </div>
</section>
@endif

{{-- OFERTAS --}}
@if ($ofertas->isNotEmpty())
<section class="wrap sec">
    <x-site.section-head eyebrow="Oportunidades" title="Ofertas com preço reduzido" text="Valores revisados para venda rápida. Válido enquanto durar o estoque." :href="route('estoque', ['oferta' => 1])" link="Ver todas as ofertas" />
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($ofertas as $v)<x-site.vehicle-card :vehicle="$v" />@endforeach
    </div>
</section>
@endif

{{-- INSTITUCIONAL --}}
<section class="border-t line" id="sobre">
    <div class="wrap sec grid items-center gap-10 md:grid-cols-2">
        <div>
            <div class="eyebrow">A Autocom</div>
            <h2 class="mt-3 font-display text-3xl font-bold md:text-4xl">Loja multimarcas em Tatuí com padrão de concessionária</h2>
            <p class="mt-4 max-w-md text-muted">Selecionamos cada seminovo com rigor: procedência checada, laudo cautelar, revisão mecânica e documentação pronta para transferência. Você compra com a tranquilidade de quem sabe exatamente o que está levando.</p>
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('estoque') }}" class="btn btn-primary">Ver estoque</a>
                <a href="{{ route('whatsapp') }}" target="_blank" rel="noopener" class="btn btn-wa"><x-site.icon name="whatsapp" class="w-4 h-4" /> Falar agora</a>
            </div>
        </div>
        <div class="grid overflow-hidden rounded-[14px] border line sm:grid-cols-2" style="gap:1px;background:rgba(214,232,240,.09)">
            @foreach ([['shield', 'Procedência garantida', 'Laudo cautelar e checagem de histórico em todos os veículos.'], ['doc', 'Documentação pronta', 'Transferência e vistoria resolvidas pela loja.'], ['card', 'Financiamento facilitado', 'Parceria com os principais bancos e aprovação rápida.'], ['swap', 'Seu usado na troca', 'Avaliação justa em até 1 hora, à vista ou como entrada.']] as [$icon, $t, $d])
                <div class="bg-graphite p-6">
                    <x-site.icon :name="$icon" class="h-7 w-7 text-brand-hi" />
                    <b class="mt-3 block font-display text-lg">{{ $t }}</b>
                    <p class="mt-1 text-sm text-muted">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- FAIXAS DE BANNER --}}
@if ($faixas->isNotEmpty())
<section class="wrap grid gap-4 md:grid-cols-2">
    @foreach ($faixas as $b)
        <a href="{{ $b->link ?: route('estoque') }}" class="panel group relative overflow-hidden p-7 transition hover:border-brand">
            @if ($b->imagem_url)<img src="{{ $b->imagem_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30 transition group-hover:opacity-40">@endif
            <div class="relative">
                <b class="font-display text-2xl font-bold">{{ $b->titulo }}</b>
                @if ($b->subtitulo)<p class="mt-2 text-sm text-muted">{{ $b->subtitulo }}</p>@endif
                <span class="mt-4 inline-block text-sm font-semibold text-brand-hi">{{ $b->cta ?: 'Saiba mais' }} →</span>
            </div>
        </a>
    @endforeach
</section>
@endif

{{-- TROCA --}}
<section class="wrap sec" id="troca">
    <div class="relative grid gap-8 overflow-hidden rounded-[20px] border line bg-graphite p-6 md:grid-cols-[1fr_1.1fr] md:p-12">
        <div class="pointer-events-none absolute -right-[20%] -top-[60%] h-[160%] w-[70%]" style="background:radial-gradient(closest-side,rgba(21,149,184,.16),transparent)"></div>
        <div class="relative">
            <div class="eyebrow">Troca</div>
            <h2 class="mt-3 font-display text-3xl font-bold md:text-4xl">Seu usado vale mais aqui</h2>
            <p class="mt-4 text-muted">Avaliamos seu carro em até 1 hora e você usa o valor como entrada ou recebe à vista. Sem compromisso.</p>
            <ol class="mt-6 grid gap-3 text-sm text-muted">
                @foreach (['Conte sobre o seu carro no formulário ao lado', 'Receba uma pré-avaliação pelo WhatsApp', 'Traga o carro para a vistoria final', 'Feche o negócio com a entrada garantida'] as $i => $p)
                    <li class="flex items-baseline gap-3"><span class="grid h-6 w-6 flex-none place-items-center rounded-full border border-brand font-mono text-xs text-brand-hi">{{ $i + 1 }}</span>{{ $p }}</li>
                @endforeach
            </ol>
        </div>
        <div class="relative"><livewire:site.lead-form tipo="troca" titulo="Avaliar meu carro" /></div>
    </div>
</section>

{{-- SIMULADOR --}}
<section class="wrap pb-14 md:pb-20">
    <livewire:site.simulador />
</section>

{{-- LOCARX --}}
<section class="wrap sec" id="locarx">
    <div class="grid items-center gap-8 rounded-[20px] border p-6 text-white md:grid-cols-[1.2fr_1fr] md:p-12" style="border-color:#262628;background:radial-gradient(60% 80% at 85% 20%,rgba(226,48,54,.14),transparent 70%),#101010">
        <div>
            <img src="{{ asset('images/locarx.png') }}" alt="LocarX" class="mb-4 h-11 w-auto">
            <div class="font-mono text-xs uppercase tracking-[0.16em]" style="color:#e23036">Parceria · locação de veículos</div>
            <h2 class="mt-3 font-display text-3xl font-bold md:text-4xl">Precisa de um carro por alguns dias? <span style="color:#e23036">LocarX</span>.</h2>
            <p class="mt-4 max-w-lg" style="color:#b9b9bf">Diárias, semanais e mensais com carros novos, seguro incluso e retirada em Tatuí. Ideal para viagens, trabalho ou enquanto seu carro está na oficina.</p>
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="https://wa.me/{{ \App\Support\Whatsapp::normalize($S::get('locarx.whatsapp', config('autocom.locarx.whatsapp'))) }}?text={{ rawurlencode('Olá! Vim pelo site da Autocom Motors e quero informações sobre locação.') }}" target="_blank" rel="noopener" class="btn btn-lx">Falar com a LocarX</a>
                <a href="{{ $S::get('locarx.url', config('autocom.locarx.url')) }}" target="_blank" rel="noopener" class="btn border-white/25 text-white hover:border-white">Site da LocarX</a>
            </div>
            <p class="mt-4 text-xs" style="color:#8d8d92">Serviço prestado pela LocarX. Condições, frota e valores sob responsabilidade da locadora.</p>
        </div>
        <ul class="grid overflow-hidden rounded-[14px] border" style="gap:1px;background:#262628;border-color:#262628">
            @foreach ([['Carros novos', 'Frota renovada, revisada e higienizada.'], ['Seguro incluso', 'Proteção básica em todas as modalidades.'], ['Sem burocracia', 'CNH, cartão e pronto. Retirada em minutos.'], ['Planos flexíveis', 'Diária, semanal, mensal e corporativo.']] as [$t, $d])
                <li class="grid gap-0.5 p-4 pl-4" style="background:#161618;border-left:3px solid #e23036"><b class="font-display">{{ $t }}</b><span class="text-sm" style="color:#9c9ca2">{{ $d }}</span></li>
            @endforeach
        </ul>
    </div>
</section>

{{-- CONTATO --}}
<section class="wrap sec border-t line" id="contato">
    <x-site.section-head eyebrow="Contato" title="Venha tomar um café e conhecer o estoque" />
    <div class="grid gap-5 md:grid-cols-[1fr_1.25fr]">
        <div class="panel grid content-start gap-5 p-7">
            <dl class="grid gap-4">
                <div><dt class="lbl">Endereço</dt><dd class="mt-1">{{ $S::get('contato.endereco', config('autocom.contato.endereco')) }}</dd></div>
                <div><dt class="lbl">Telefone / WhatsApp</dt><dd class="mt-1">{{ $S::get('contato.telefone', config('autocom.contato.telefone')) }}</dd></div>
                <div><dt class="lbl">E-mail</dt><dd class="mt-1">{{ $S::get('contato.email', config('autocom.contato.email')) }}</dd></div>
                <div><dt class="lbl">Horário</dt><dd class="mt-1">{{ $S::get('contato.horario', config('autocom.contato.horario')) }}</dd></div>
            </dl>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('whatsapp') }}" target="_blank" rel="noopener" class="btn btn-wa"><x-site.icon name="whatsapp" class="w-4 h-4" /> WhatsApp</a>
                <a href="{{ $S::get('contato.maps', config('autocom.contato.maps')) }}" target="_blank" rel="noopener" class="btn"><x-site.icon name="pin" class="w-4 h-4" /> Como chegar</a>
            </div>
        </div>
        <livewire:site.lead-form tipo="contato" titulo="Envie uma mensagem" />
    </div>
</section>
</x-layouts.site>
