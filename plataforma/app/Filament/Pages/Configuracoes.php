<?php

namespace App\Filament\Pages;

use App\Models\SyncLog;
use App\Support\Settings;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Configuracoes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Configurações';

    protected static ?string $title = 'Configurações da loja';

    protected static string $view = 'filament.pages.configuracoes';

    public ?array $data = [];

    /** chave do setting => grupo */
    public const KEYS = [
        'nome' => 'loja', 'slogan' => 'loja',
        'whatsapp.numero' => 'loja', 'whatsapp.mensagem_veiculo' => 'loja', 'whatsapp.mensagem_padrao' => 'loja',
        'contato.telefone' => 'loja', 'contato.email' => 'loja', 'contato.endereco' => 'loja', 'contato.horario' => 'loja', 'contato.instagram' => 'loja', 'contato.maps' => 'loja',
        'financiamento.taxa_mensal' => 'financiamento', 'financiamento.entrada_minima_percent' => 'financiamento', 'financiamento.disclaimer' => 'financiamento',
        'locarx.url' => 'locarx', 'locarx.whatsapp' => 'locarx',
        'bot.ativo' => 'bot', 'bot.nome' => 'bot', 'bot.saudacao' => 'bot',
    ];

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'gerente'], true);
    }

    public function mount(): void
    {
        $values = [];
        foreach (array_keys(self::KEYS) as $k) {
            $values[self::field($k)] = Settings::get($k, config('autocom.'.$k));
        }
        $values[self::field('bot.ativo')] = Settings::bool('bot.ativo', true);
        $this->form->fill($values);
    }

    public static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Forms\Components\Tabs::make()->tabs([
                Forms\Components\Tabs\Tab::make('Loja')->icon('heroicon-o-building-storefront')->schema([
                    Forms\Components\TextInput::make('nome')->label('Nome da loja')->required(),
                    Forms\Components\TextInput::make('slogan'),
                    Forms\Components\Section::make('WhatsApp')->columns(1)->schema([
                        Forms\Components\TextInput::make('whatsapp__numero')->label('Número (somente dígitos, com DDI)')->placeholder('5515999990000')->required()->helperText('Usado em todos os botões de WhatsApp do site.'),
                        Forms\Components\Textarea::make('whatsapp__mensagem_veiculo')->label('Mensagem para veículo')->helperText('Use {veiculo} para "marca modelo ano".')->rows(2),
                        Forms\Components\Textarea::make('whatsapp__mensagem_padrao')->label('Mensagem padrão')->rows(2),
                    ]),
                    Forms\Components\Section::make('Contato')->columns(2)->schema([
                        Forms\Components\TextInput::make('contato__telefone')->label('Telefone'),
                        Forms\Components\TextInput::make('contato__email')->label('E-mail')->email(),
                        Forms\Components\TextInput::make('contato__endereco')->label('Endereço')->columnSpanFull(),
                        Forms\Components\TextInput::make('contato__horario')->label('Horário'),
                        Forms\Components\TextInput::make('contato__instagram')->label('Instagram (URL)')->url(),
                        Forms\Components\TextInput::make('contato__maps')->label('Link do Google Maps')->url()->columnSpanFull(),
                    ]),
                ]),
                Forms\Components\Tabs\Tab::make('Financiamento')->icon('heroicon-o-banknotes')->schema([
                    Forms\Components\TextInput::make('financiamento__taxa_mensal')->label('Taxa padrão (% a.m.)')->numeric()->step('0.01')->suffix('% a.m.')->required(),
                    Forms\Components\TextInput::make('financiamento__entrada_minima_percent')->label('Entrada mínima sugerida (%)')->numeric()->suffix('%')->required(),
                    Forms\Components\Textarea::make('financiamento__disclaimer')->label('Aviso legal exibido no simulador')->rows(2)->required(),
                ]),
                Forms\Components\Tabs\Tab::make('LocarX')->icon('heroicon-o-key')->schema([
                    Forms\Components\TextInput::make('locarx__url')->label('Site da LocarX')->url(),
                    Forms\Components\TextInput::make('locarx__whatsapp')->label('WhatsApp da LocarX (dígitos com DDI)'),
                ]),
                Forms\Components\Tabs\Tab::make('Assistente virtual')->icon('heroicon-o-sparkles')->schema([
                    Forms\Components\Toggle::make('bot__ativo')->label('Assistente ativo no site'),
                    Forms\Components\TextInput::make('bot__nome')->label('Nome exibido'),
                    Forms\Components\Textarea::make('bot__saudacao')->label('Mensagem de boas-vindas')->rows(3),
                ]),
                Forms\Components\Tabs\Tab::make('Webmotors')->icon('heroicon-o-arrow-path')->schema([
                    Forms\Components\Placeholder::make('wm_status')->label('Status da integração')->content(fn () => (config('webmotors.enabled') ? 'ATIVA' : 'DESATIVADA').' · driver: '.config('webmotors.driver').' · agendamento: a cada '.config('webmotors.sync.interval_minutes').' min'),
                    Forms\Components\Placeholder::make('wm_last')->label('Última sincronização')->content(function () {
                        $l = SyncLog::latest()->first();

                        return $l ? $l->created_at->format('d/m/Y H:i').' — '.$l->status.' — '.($l->message ?? '') : 'nenhuma';
                    }),
                    Forms\Components\Placeholder::make('wm_help')->label('Como ativar')->content('Defina WEBMOTORS_ENABLED=true, WEBMOTORS_DRIVER=api e as credenciais da API Site (WEBMOTORS_CLIENT_ID, WEBMOTORS_CLIENT_SECRET, WEBMOTORS_DEALER_ID) no .env do servidor. A sincronização roda pelo scheduler; use "Integrações → Sincronizações" para rodar manualmente.'),
                ]),
            ])->persistTabInQueryString(),
        ]);
    }

    public function salvar(): void
    {
        $state = $this->form->getState();
        foreach (self::KEYS as $key => $group) {
            $v = $state[self::field($key)] ?? null;
            if (is_bool($v)) {
                $v = $v ? '1' : '0';
            }
            Settings::set($key, $v, $group);
        }
        Notification::make()->title('Configurações salvas')->success()->send();
    }
}
