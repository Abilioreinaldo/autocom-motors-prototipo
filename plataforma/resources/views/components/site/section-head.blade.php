@props(['eyebrow' => null, 'title', 'text' => null, 'href' => null, 'link' => 'Ver todos'])
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        @if ($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
        <h2 class="mt-2 font-display text-3xl font-bold md:text-4xl">{{ $title }}</h2>
        @if ($text)<p class="mt-2 max-w-xl text-muted">{{ $text }}</p>@endif
    </div>
    @if ($href)<a href="{{ $href }}" class="text-sm font-semibold text-brand-hi hover:underline">{{ $link }} →</a>@endif
</div>
