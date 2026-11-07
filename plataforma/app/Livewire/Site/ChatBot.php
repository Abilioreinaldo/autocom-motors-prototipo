<?php

namespace App\Livewire\Site;

use App\Models\BotSession;
use App\Models\Vehicle;
use App\Services\BotEngine;
use App\Support\Settings;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ChatBot extends Component
{
    public bool $aberto = false;

    public string $texto = '';

    public ?string $sessionUuid = null;

    /** @var list<array{role:string,text:string,cards?:array,quick?:array,links?:array}> */
    public array $mensagens = [];

    public array $quick = [];

    public function mount(): void
    {
        $this->sessionUuid = session('bot_session_uuid');
    }

    #[Computed]
    public function nome(): string
    {
        return (string) Settings::get('bot.nome', config('autocom.bot.nome'));
    }

    public function abrir(): void
    {
        $this->aberto = true;
        if ($this->mensagens === []) {
            $s = $this->session();
            if ($s->messages === []) {
                $engine = app(BotEngine::class);
                $s->pushMessage('bot', $engine->greeting(), ['quick' => $engine->defaultQuick()])->save();
            }
            $this->hydrateFrom($s);
        }
    }

    public function fechar(): void
    {
        $this->aberto = false;
    }

    public function enviar(?string $texto = null): void
    {
        $texto = trim($texto ?? $this->texto);
        $this->texto = '';
        if ($texto === '' || mb_strlen($texto) > 500) {
            return;
        }

        $s = $this->session();
        $s->pushMessage('user', $texto);

        $engine = app(BotEngine::class);
        $r = $engine->reply($s, $texto);

        $s->pushMessage('bot', $r['text'], [
            'cards' => array_map(fn (Vehicle $v) => ['id' => $v->id, 'titulo' => $v->titulo, 'versao' => $v->versao, 'ano' => "{$v->ano_fab}/{$v->ano_mod}", 'preco' => $v->preco_formatado, 'url' => $v->url, 'foto' => $v->cover_url], $r['cards']),
            'quick' => $r['quick'],
            'links' => $r['links'],
        ]);
        $s->save();

        $this->hydrateFrom($s);
        $this->dispatch('bot-scroll');
    }

    protected function session(): BotSession
    {
        $s = $this->sessionUuid ? BotSession::where('uuid', $this->sessionUuid)->first() : null;
        if (! $s) {
            $s = BotSession::create(['visitor_id' => substr(hash('sha256', request()->ip().'|'.request()->userAgent()), 0, 40), 'messages' => []]);
            $this->sessionUuid = $s->uuid;
            session(['bot_session_uuid' => $s->uuid]);
        }

        return $s;
    }

    protected function hydrateFrom(BotSession $s): void
    {
        $this->mensagens = array_slice($s->messages ?? [], -40);
        $last = end($this->mensagens);
        $this->quick = ($last && $last['role'] === 'bot') ? ($last['quick'] ?? []) : [];
    }

    public function render()
    {
        return view('livewire.site.chat-bot');
    }
}
