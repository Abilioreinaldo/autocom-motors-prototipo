<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class LeadsPorDiaChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Leads por dia';

    protected static ?string $maxHeight = '260px';

    public ?string $filter = '30';

    protected int|string|array $columnSpan = ['md' => 2];

    protected function getFilters(): ?array
    {
        return ['7' => '7 dias', '30' => '30 dias', '90' => '90 dias'];
    }

    protected function getData(): array
    {
        $dias = (int) $this->filter;
        $inicio = today()->subDays($dias - 1);

        $porDia = Lead::query()->where('created_at', '>=', $inicio)->get(['created_at', 'tipo'])
            ->groupBy(fn ($l) => $l->created_at->format('Y-m-d'));

        $labels = [];
        $total = [];
        $bot = [];
        for ($d = $inicio->copy(); $d <= today(); $d->addDay()) {
            $k = $d->format('Y-m-d');
            $labels[] = $d->format($dias > 31 ? 'd/m' : 'd/m');
            $grupo = $porDia->get($k, collect());
            $total[] = $grupo->count();
            $bot[] = $grupo->where('tipo.value', 'bot')->count();
        }

        return [
            'datasets' => [
                ['label' => 'Todos os leads', 'data' => $total, 'borderColor' => '#38b9de', 'backgroundColor' => 'rgba(21,149,184,.18)', 'fill' => true, 'tension' => .35],
                ['label' => 'Via assistente', 'data' => $bot, 'borderColor' => '#e0a33a', 'backgroundColor' => 'transparent', 'tension' => .35],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]], 'plugins' => ['legend' => ['display' => true]]];
    }
}
