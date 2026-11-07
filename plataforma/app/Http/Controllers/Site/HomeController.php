<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $base = fn () => Vehicle::query()->published()->with('cover');

        return view('site.home', [
            'hero' => Banner::active('home_hero')->first(),
            'faixas' => Banner::active('home_faixa')->get(),
            'destaques' => $base()->featured()->latest('published_at')->take(6)->get(),
            'recentes' => $base()->latest('published_at')->take(8)->get(),
            'ofertas' => $base()->onOffer()->latest('updated_at')->take(4)->get(),
            'marcas' => Vehicle::published()->select('marca')->distinct()->orderBy('marca')->pluck('marca'),
            'total' => Vehicle::published()->count(),
            'heroVehicle' => $base()->featured()->inRandomOrder()->first() ?? $base()->latest()->first(),
        ]);
    }
}
