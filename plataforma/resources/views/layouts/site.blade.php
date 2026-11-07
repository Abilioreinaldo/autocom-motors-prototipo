<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ \App\Support\Settings::get('nome', config('autocom.nome')) }}</title>
    <meta name="description" content="{{ $description ?? \App\Support\Settings::get('slogan', config('autocom.slogan')) }}">
    @isset($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endisset
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&family=Instrument+Sans:wght@400..700&family=IBM+Plex+Mono:wght@400;500&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">
@php($loja = \App\Support\Settings::get('nome', config('autocom.nome')))
<header class="sticky top-0 z-50 border-b line backdrop-blur-xl" style="background:rgba(10,12,15,.84)">
    <div class="wrap flex h-[76px] items-center gap-6">
        <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0" aria-label="{{ $loja }}">
            <img src="{{ asset('images/logo.png') }}" alt="{{ $loja }}" class="h-13 w-13" width="52" height="52">
            <span class="hidden sm:block font-display font-bold tracking-tight text-lg" style="font-stretch:118%">{{ $loja }}</span>
        </a>
        <nav id="mainNav" class="hidden md:flex gap-6 ml-2 text-sm font-medium text-muted max-md:absolute max-md:top-[76px] max-md:inset-x-0 max-md:flex-col max-md:gap-0 max-md:bg-graphite max-md:border-b max-md:line max-md:px-4 max-md:pb-3">
            @foreach ([['Estoque', route('estoque')], ['Ofertas', route('estoque', ['oferta' => 1])], ['Troca', route('home').'#troca'], ['Financiamento', route('home').'#simulador'], ['LocarX', route('home').'#locarx'], ['Contato', route('home').'#contato']] as [$label, $href])
                <a href="{{ $href }}" class="hover:text-fg transition max-md:py-3 max-md:border-b max-md:line max-md:text-fg">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex gap-2 items-center">
            <a href="{{ route('whatsapp') }}" target="_blank" rel="noopener" class="btn btn-wa hidden md:inline-flex">
                <x-site.icon name="whatsapp" class="w-4 h-4" /> WhatsApp
            </a>
            <a href="{{ route('estoque') }}" class="btn btn-primary">Ver estoque</a>
            <button type="button" data-toggle="#mainNav" class="md:hidden grid place-items-center w-11 h-11 rounded-full border line2" aria-label="Menu">
                <x-site.icon name="menu" class="w-5 h-5" />
            </button>
        </div>
    </div>
</header>

<main class="flex-1">
    {{ $slot }}
</main>

<footer class="border-t line bg-ink py-14">
    <div class="wrap grid gap-8 md:grid-cols-[1.4fr_repeat(3,1fr)]">
        <div>
            <img src="{{ asset('images/logo.png') }}" alt="{{ $loja }}" class="w-18 h-18" width="72" height="72">
            <p class="mt-4 max-w-xs text-sm text-muted">{{ \App\Support\Settings::get('slogan', config('autocom.slogan')) }}. Seminovos revisados, documentação transparente e atendimento direto no WhatsApp.</p>
        </div>
        <div>
            <h4 class="font-mono text-xs uppercase tracking-[0.14em] text-dim mb-4">Navegação</h4>
            <ul class="grid gap-2 text-sm text-muted">
                <li><a href="{{ route('estoque') }}" class="hover:text-fg">Estoque completo</a></li>
                <li><a href="{{ route('estoque', ['oferta' => 1]) }}" class="hover:text-fg">Ofertas</a></li>
                <li><a href="{{ route('home') }}#troca" class="hover:text-fg">Avaliar meu usado</a></li>
                <li><a href="{{ route('home') }}#simulador" class="hover:text-fg">Simular financiamento</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-mono text-xs uppercase tracking-[0.14em] text-dim mb-4">Atendimento</h4>
            <ul class="grid gap-2 text-sm text-muted">
                <li>{{ \App\Support\Settings::get('contato.telefone', config('autocom.contato.telefone')) }}</li>
                <li><a href="{{ route('whatsapp') }}" target="_blank" rel="noopener" class="hover:text-fg">WhatsApp</a></li>
                <li><a href="mailto:{{ \App\Support\Settings::get('contato.email', config('autocom.contato.email')) }}" class="hover:text-fg">{{ \App\Support\Settings::get('contato.email', config('autocom.contato.email')) }}</a></li>
                <li>{{ \App\Support\Settings::get('contato.horario', config('autocom.contato.horario')) }}</li>
            </ul>
        </div>
        <div>
            <h4 class="font-mono text-xs uppercase tracking-[0.14em] text-dim mb-4">Onde estamos</h4>
            <p class="text-sm text-muted">{{ \App\Support\Settings::get('contato.endereco', config('autocom.contato.endereco')) }}</p>
            <a href="{{ \App\Support\Settings::get('contato.maps', config('autocom.contato.maps')) }}" target="_blank" rel="noopener" class="mt-3 inline-block text-sm text-brand-hi hover:underline">Abrir no mapa</a>
            <a href="{{ \App\Support\Settings::get('contato.instagram', config('autocom.contato.instagram')) }}" target="_blank" rel="noopener" class="mt-1 block text-sm text-brand-hi hover:underline">Instagram</a>
        </div>
    </div>
    <div class="wrap mt-12 flex flex-wrap justify-between gap-3 border-t line pt-6 text-xs text-dim">
        <span>© {{ date('Y') }} {{ $loja }} · Tatuí-SP</span>
        <span>Preços e condições sujeitos a alteração sem aviso prévio. Imagens ilustrativas.</span>
        <a href="{{ route('filament.admin.pages.dashboard') }}" class="hover:text-fg">Painel do lojista</a>
    </div>
</footer>

@if (\App\Support\Settings::bool('bot.ativo', config('autocom.bot.ativo')))
    <livewire:site.chat-bot />
@endif

<a href="{{ route('whatsapp') }}" target="_blank" rel="noopener" aria-label="Falar no WhatsApp"
   class="fixed left-4 bottom-4 z-40 grid h-14 w-14 place-items-center rounded-full text-white shadow-lg transition hover:scale-105" style="background:#25d366">
    <x-site.icon name="whatsapp" class="w-7 h-7" />
</a>
</body>
</html>
