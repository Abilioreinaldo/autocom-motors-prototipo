<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class FunilLeadsChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Funil por status';

    protected static ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $counts = Lead::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $labels = [];
        $data = [];
        foreach (LeadStatus::funnel() as $s) {
            $labels[] = $s->getLabel();
            $data[] = (int) ($counts[$s->value] ?? 0);
        }

        return [
            'datasets' => [[
                'label' => 'Leads',
                'data' => $data,
                'backgroundColor' => ['#38b9de', '#e0a33a', '#1595b8', '#3fbf7f', '#e05a52'],
                'borderRadius' => 6,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ['indexAxis' => 'y', 'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]], 'plugins' => ['legend' => ['display' => false]]];
    }
}
