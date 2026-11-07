<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        $ref = $this->record->webmotorsRef;

        return [
            Actions\Action::make('ver')->label('Ver no site')->icon('heroicon-o-eye')->color('gray')->url(fn () => $this->record->url)->openUrlInNewTab(),
            Actions\Action::make('webmotors')
                ->label(fn () => 'Webmotors #'.$ref?->external_id)
                ->icon('heroicon-o-link')->color('info')
                ->visible((bool) $ref)
                ->url(fn () => $ref?->source_url)->openUrlInNewTab(),
            Actions\Action::make('destravar')
                ->label(fn () => 'Campos travados ('.count($ref?->locked_fields ?? []).')')
                ->icon('heroicon-o-lock-closed')->color('warning')
                ->visible(fn () => $ref && ! empty($ref->locked_fields))
                ->modalDescription(fn () => 'Estes campos foram editados no painel e NÃO são sobrescritos pela sincronização: '.implode(', ', $ref->locked_fields ?? []).'. Deseja liberar todos para o sync voltar a atualizá-los?')
                ->requiresConfirmation()
                ->action(function () use ($ref) {
                    $ref->update(['locked_fields' => []]);
                    Notification::make()->title('Campos liberados para sincronização')->success()->send();
                }),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * Ao editar um veículo sincronizado, os campos alterados manualmente entram em locked_fields
     * para que o próximo sync da Webmotors não os sobrescreva.
     */
    protected function afterSave(): void
    {
        $ref = $this->record->webmotorsRef;
        if (! $ref) {
            return;
        }
        $changed = array_intersect(array_keys($this->record->getChanges()), VehicleResource::SYNC_FIELDS);
        if ($changed) {
            $ref->lock(array_values($changed))->save();
            Notification::make()->title('Campos protegidos do sync: '.implode(', ', $changed))->info()->send();
        }
    }
}
