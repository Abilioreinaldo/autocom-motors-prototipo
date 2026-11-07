<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;

class VehicleController extends Controller
{
    public function __invoke(Vehicle $vehicle): View
    {
        abort_if($vehicle->status === \App\Enums\VehicleStatus::Vendido && ! request()->user(), 404);

        $vehicle->load('photos');

        $relacionados = Vehicle::query()->published()->with('cover')
            ->whereKeyNot($vehicle->id)
            ->where(fn ($q) => $q->where('carroceria', $vehicle->carroceria?->value)->orWhere('marca', $vehicle->marca))
            ->whereBetween('preco', [(float) $vehicle->preco * 0.7, (float) $vehicle->preco * 1.3])
            ->inRandomOrder()->take(4)->get();

        if ($relacionados->count() < 4) {
            $relacionados = $relacionados->merge(
                Vehicle::query()->published()->with('cover')->whereKeyNot($vehicle->id)->whereNotIn('id', $relacionados->pluck('id'))->inRandomOrder()->take(4 - $relacionados->count())->get()
            );
        }

        return view('site.vehicle', compact('vehicle', 'relacionados'));
    }
}
