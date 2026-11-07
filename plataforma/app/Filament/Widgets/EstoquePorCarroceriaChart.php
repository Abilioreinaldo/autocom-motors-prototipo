<?php

namespace App\Filament\Widgets;

use App\Enums\Carroceria;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Filament\Widgets\ChartWidget;

class EstoquePorCarroceriaChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Estoque por carroceria';

    protected static ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $counts = Vehicle::query()->where('status', VehicleStatus::Disponivel)->selectRaw('carroceria, count(*) as n')->groupBy('carroceria')->pluck('n', 'carroceria');

        $labels = [];
        $data = [];
        foreach ($counts as $k => $n) {
            $labels[] = $k ? (Carroceria::tryFrom($k)?->getLabel() ?? $k) : 'Não informado';
            $data[] = (int) $n;
        }

        return [
            'datasets' => [['data' => $data, 'backgroundColor' => ['#1595b8', '#38b9de', '#3fbf7f', '#e0a33a', '#e05a52', '#8b5cf6', '#f472b6', '#64748b', '#22d3ee']]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return ['scales' => ['x' => ['display' => false], 'y' => ['display' => false]], 'plugins' => ['legend' => ['display' => true, 'position' => 'bottom']]];
    }
}
