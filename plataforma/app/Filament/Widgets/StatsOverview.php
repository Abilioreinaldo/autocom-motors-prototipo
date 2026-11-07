<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Enums\VehicleStatus;
use App\Models\Lead;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $disponiveis = Vehicle::where('status', VehicleStatus::Disponivel)->count();
        $reservados = Vehicle::where('status', VehicleStatus::Reservado)->count();
        $valor = (float) Vehicle::where('status', VehicleStatus::Disponivel)->sum('preco');
        $novos = Lead::where('status', LeadStatus::Novo)->count();
        $hoje = Lead::whereDate('created_at', today())->count();
        $semana = Lead::where('created_at', '>=', now()->subDays(7))->count();
        $semanaAnterior = Lead::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();
        $vendidosMes = Vehicle::where('status', VehicleStatus::Vendido)->whereMonth('updated_at', now()->month)->count();

        $sparkLeads = collect(range(6, 0))->map(fn ($d) => Lead::whereDate('created_at', today()->subDays($d))->count())->all();
        $delta = $semanaAnterior > 0 ? round(($semana - $semanaAnterior) / $semanaAnterior * 100) : null;

        return [
            Stat::make('Veículos disponíveis', $disponiveis)->description($reservados.' reservado(s) · '.$vendidosMes.' vendido(s) no mês')->icon('heroicon-o-truck')->color('primary'),
            Stat::make('Valor do estoque', 'R$ '.number_format($valor, 0, ',', '.'))->description('Ticket médio R$ '.number_format($disponiveis ? $valor / $disponiveis : 0, 0, ',', '.'))->icon('heroicon-o-banknotes'),
            Stat::make('Leads novos', $novos)->description($hoje.' recebido(s) hoje')->icon('heroicon-o-bell-alert')->color($novos > 0 ? 'info' : 'gray')->chart($sparkLeads),
            Stat::make('Leads · 7 dias', $semana)->description($delta === null ? 'sem base de comparação' : ($delta >= 0 ? '+' : '').$delta.'% vs. semana anterior')->descriptionIcon($delta !== null && $delta < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')->color($delta !== null && $delta < 0 ? 'danger' : 'success')->chart($sparkLeads),
        ];
    }
}
