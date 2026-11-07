<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('whatsapp')->label('Abrir WhatsApp')->icon('heroicon-o-chat-bubble-oval-left')->color('success')
                ->url(fn (Lead $record) => $record->whatsapp_url)->openUrlInNewTab()->visible(fn (Lead $record) => (bool) $record->whatsapp_url),
            Actions\Action::make('contato')->label('Registrar contato')->icon('heroicon-o-phone')
                ->form([
                    Forms\Components\Textarea::make('nota')->label('O que foi conversado?')->required()->rows(3),
                    Forms\Components\Select::make('status')->label('Novo status (opcional)')->options(LeadStatus::class)->native(false),
                ])
                ->action(function (Lead $record, array $data) {
                    $record->registerContact($data['nota']);
                    if (! empty($data['status'])) {
                        $record->update(['status' => $data['status']]);
                    }
                    Notification::make()->title('Contato registrado')->success()->send();
                    $this->refreshFormData(['status', 'last_contact_at']);
                }),
            Actions\EditAction::make(),
        ];
    }
}
