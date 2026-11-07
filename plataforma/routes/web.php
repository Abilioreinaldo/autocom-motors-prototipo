<?php

use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LeadController;
use App\Http\Controllers\Site\VehicleController;
use App\Http\Controllers\Site\WhatsappController;
use App\Livewire\Site\Estoque;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/estoque', Estoque::class)->name('estoque');
Route::get('/veiculo/{vehicle:slug}', VehicleController::class)->name('veiculo.show');
Route::get('/whatsapp/{vehicle?}', WhatsappController::class)->name('whatsapp');

Route::post('/leads/{tipo}', [LeadController::class, 'store'])
    ->whereIn('tipo', ['proposta', 'troca', 'financiamento', 'contato'])
    ->middleware('throttle:20,1')
    ->name('leads.store');
