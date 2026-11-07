<?php

namespace App\Services;

use App\Enums\Carroceria;
use App\Enums\LeadType;
use App\Models\BotSession;
use App\Models\Vehicle;
use App\Support\Settings;
use App\Support\Whatsapp;
use Illuminate\Support\Str;

/**
 * Assistente virtual baseado em regras (sem IA externa).
 *
 * Intenções: buscar veículo (marca/modelo/carroceria/câmbio/"até X mil"), simulação em etapas,
 * troca, LocarX (locação), contato humano e captura de nome + fone → Lead tipo bot.
 *
 * Retorno de reply(): ['text' => string, 'cards' => Vehicle[], 'quick' => string[], 'links' => [[label,url]]]
 */
class BotEngine
{
    protected const MARCAS = ['chevrolet', 'gm', 'volkswagen', 'vw', 'toyota', 'fiat', 'honda', 'jeep', 'hyundai', 'renault', 'nissan', 'ford', 'peugeot', 'citroen', 'citroën', 'bmw', 'mercedes', 'audi', 'kia', 'mitsubishi', 'caoa', 'chery', 'byd', 'ram', 'volvo', 'land rover'];

    protected const ALIAS = ['gm' => 'chevrolet', 'vw' => 'volkswagen', 'citroën' => 'citroen'];

    public function __construct(
        protected FinancingCalculator $calc,
        protected LeadService $leads,
    ) {}

    public function greeting(): string
    {
        return (string) Settings::get('bot.saudacao', 'Olá! Sou o assistente virtual da Autocom Motors. Posso ajudar a encontrar um carro, simular financiamento ou avaliar seu usado na troca. O que você procura?');
    }

    public function defaultQuick(): array
    {
        return ['Quero um SUV', 'Até 100 mil', 'Simular financiamento', 'Tenho carro na troca', 'Alugar (LocarX)', 'Falar com humano'];
    }

    public function reply(BotSession $session, string $input): array
    {
        $ctx = $session->context ?? [];
        $text = trim($input);
        $norm = $this->norm($text);

        // ----- fluxos em andamento (etapas) -------------------------------------------------
        if (($ctx['etapa'] ?? null) && ! $this->isGlobalIntent($norm)) {
            $out = $this->continueFlow($session, $ctx, $text, $norm);
            if ($out) {
                return $out;
            }
        }

        // ----- intenções globais ------------------------------------------------------------
        if ($this->matches($norm, ['humano', 'atendente', 'vendedor', 'consultor', 'pessoa', 'whatsapp', 'ligar'])) {
            return $this->askContact($session, $ctx, 'humano', 'Claro! Me passa seu nome e WhatsApp que um consultor te chama agora mesmo. Qual é o seu nome?');
        }

        if ($this->matches($norm, ['alug', 'locac', 'locar', 'locarx', 'diaria', 'diária', 'mensal'])) {
            $session->context = $ctx + ['tema' => 'locarx'];

            return [
                'text' => 'Para locação, a Autocom é parceira da LocarX: carros novos com diária, semanal ou mensal, seguro incluso e sem burocracia. Quer falar com a LocarX ou que eu te passe para um consultor da Autocom?',
                'cards' => [],
                'quick' => ['Falar com a LocarX', 'Falar com humano', 'Ver estoque'],
                'links' => [['Falar com a LocarX', Whatsapp::link('Olá! Vim pelo site da Autocom Motors e quero informações sobre locação.', (string) Settings::get('locarx.whatsapp', config('autocom.locarx.whatsapp')))]],
            ];
        }

        if ($this->matches($norm, ['troca', 'trocar', 'meu carro', 'meu usado', 'avaliar', 'avaliacao', 'avaliação'])) {
            $session->context = ['etapa' => 'troca_carro', 'fluxo' => 'troca'];

            return $this->out('Ótimo! Aceitamos seu usado como entrada. Me diz marca, modelo e ano do seu carro (ex.: "Fiat Argo 2019").', [], []);
        }

        if ($this->matches($norm, ['simul', 'financi', 'parcel', 'entrada', 'prestac', 'prestaç'])) {
            $session->context = ['etapa' => 'sim_valor', 'fluxo' => 'simulacao', 'sim' => []];
            $veiculoCtx = $ctx['ultimo_veiculo'] ?? null;
            if ($veiculoCtx && ($v = Vehicle::find($veiculoCtx))) {
                $session->context = ['etapa' => 'sim_entrada', 'fluxo' => 'simulacao', 'sim' => ['preco' => (float) $v->preco, 'veiculo_id' => $v->id]];

                return $this->out("Vamos simular o {$v->titulo} ({$v->preco_formatado}). Qual valor de entrada você consegue dar?", [], ['R$ 10 mil', 'R$ 20 mil', 'R$ 30 mil', 'Sem entrada']);
            }

            return $this->out('Vamos simular! Qual o valor do veículo (ou me diga qual carro do estoque te interessa)?', [], ['R$ 60 mil', 'R$ 100 mil', 'R$ 150 mil']);
        }

        if ($this->matches($norm, ['endereco', 'endereço', 'onde fica', 'horario', 'horário', 'aberto', 'funciona'])) {
            return $this->out('Estamos em '.Settings::get('contato.endereco', config('autocom.contato.endereco')).'. Horário: '.Settings::get('contato.horario', config('autocom.contato.horario')).'. Quer que eu te mande o mapa?', [], ['Ver estoque', 'Falar com humano'], [['Abrir no mapa', (string) Settings::get('contato.maps', config('autocom.contato.maps'))]]);
        }

        if ($this->matches($norm, ['oi', 'ola', 'olá', 'bom dia', 'boa tarde', 'boa noite', 'ajuda', 'menu', 'inicio', 'início'], exact: true)) {
            return $this->out($this->greeting(), [], $this->defaultQuick());
        }

        if ($this->matches($norm, ['obrigad', 'valeu', 'tchau', 'ate mais', 'até mais'])) {
            return $this->out('Eu que agradeço! Qualquer coisa é só chamar. 🚗', [], ['Ver estoque', 'Falar com humano']);
        }

        // ----- busca no estoque -------------------------------------------------------------
        $filtros = $this->parseSearch($norm);
        if ($filtros !== []) {
            $resultados = $this->search($filtros);
            if ($resultados->isNotEmpty()) {
                $session->context = $ctx + ['ultima_busca' => $filtros, 'ultimo_veiculo' => $resultados->first()->id];
                $n = $resultados->count();
                $desc = $this->describeFilters($filtros);

                return $this->out("Encontrei {$n} ".($n === 1 ? 'opção' : 'opções')." {$desc}. Toque para ver os detalhes ou me peça para simular.", $resultados->take(4)->all(), ['Simular financiamento', 'Ver estoque completo', 'Falar com humano']);
            }
            $session->context = $ctx + ['ultima_busca' => $filtros];

            return $this->out('Não achei nada com exatamente essas características no estoque agora, mas buscamos para você! Me passa seu nome e WhatsApp que eu aviso quando chegar algo assim?', [], ['Sim, quero', 'Ver estoque completo']);
        }

        if ($this->matches($norm, ['sim', 'quero', 'pode', 'ok', 'claro'], exact: true) && isset($ctx['ultima_busca'])) {
            return $this->askContact($session, $ctx, 'busca', 'Perfeito! Qual é o seu nome?');
        }

        if ($this->matches($norm, ['estoque', 'carros', 'veiculos', 'veículos', 'ver tudo', 'ver estoque'])) {
            $v = Vehicle::published()->with('cover')->featured()->take(4)->get();

            return $this->out('Esses são alguns destaques do estoque. Me diga marca, modelo, tipo ou faixa de preço que eu filtro para você.', $v->all(), ['Quero um SUV', 'Até 80 mil', 'Automático'], [['Ver estoque completo', route('estoque')]]);
        }

        return $this->out('Não entendi muito bem. Você pode me dizer, por exemplo: "quero um SUV automático até 120 mil", "simular financiamento" ou "tenho carro na troca". Ou, se preferir, falo com um consultor humano.', [], $this->defaultQuick());
    }

    // ---------------------------------------------------------------------------------------
    // Fluxos
    // ---------------------------------------------------------------------------------------

    protected function continueFlow(BotSession $session, array $ctx, string $text, string $norm): ?array
    {
        switch ($ctx['etapa']) {
            case 'sim_valor':
                $valor = $this->parseMoney($norm);
                if (! $valor) {
                    $busca = $this->parseSearch($norm);
                    $v = $busca ? $this->search($busca)->first() : null;
                    if ($v) {
                        $ctx['sim'] = ['preco' => (float) $v->preco, 'veiculo_id' => $v->id];
                        $ctx['etapa'] = 'sim_entrada';
                        $session->context = $ctx;

                        return $this->out("Vamos simular o {$v->titulo} ({$v->preco_formatado}). Qual valor de entrada?", [$v], ['R$ 10 mil', 'R$ 20 mil', 'R$ 30 mil', 'Sem entrada']);
                    }

                    return $this->out('Me diz um valor, por exemplo "90 mil" ou "R$ 120.000".', [], ['R$ 60 mil', 'R$ 100 mil', 'R$ 150 mil']);
                }
                $ctx['sim'] = ['preco' => $valor];
                $ctx['etapa'] = 'sim_entrada';
                $session->context = $ctx;

                return $this->out('Boa. E qual valor de entrada você consegue dar?', [], ['R$ 10 mil', 'R$ 20 mil', 'R$ 30 mil', 'Sem entrada']);

            case 'sim_entrada':
                $entrada = $this->matches($norm, ['sem', 'zero', 'nenhum', 'nada']) ? 0.0 : $this->parseMoney($norm);
                if ($entrada === null) {
                    return $this->out('Me diz o valor da entrada (ex.: "20 mil" ou "sem entrada").', [], ['R$ 10 mil', 'R$ 20 mil', 'Sem entrada']);
                }
                if ($entrada >= $ctx['sim']['preco']) {
                    return $this->out('A entrada precisa ser menor que o valor do veículo. Qual seria?', [], ['R$ 10 mil', 'R$ 20 mil']);
                }
                $ctx['sim']['entrada'] = $entrada;
                $ctx['etapa'] = 'sim_prazo';
                $session->context = $ctx;

                return $this->out('Em quantas parcelas?', [], array_map(fn ($n) => "{$n}x", $this->calc->terms()));

            case 'sim_prazo':
                preg_match('/(\d{1,3})/', $norm, $m);
                $n = (int) ($m[1] ?? 0);
                if ($n < 6 || $n > 72) {
                    return $this->out('Escolha um prazo entre 12 e 60 parcelas.', [], array_map(fn ($n) => "{$n}x", $this->calc->terms()));
                }
                $r = $this->calc->simulate($ctx['sim']['preco'], $ctx['sim']['entrada'], $n);
                $ctx['sim'] = $ctx['sim'] + $r;
                $ctx['etapa'] = 'sim_contato_nome';
                $session->context = $ctx;
                $txt = sprintf('Ficou assim: entrada de R$ %s + %dx de **R$ %s** (taxa %s%% a.m.). %s Quer receber uma proposta oficial? Me diz seu nome.',
                    number_format($r['entrada'], 0, ',', '.'), $n, number_format($r['parcela'], 2, ',', '.'), number_format($r['taxa_mensal'], 2, ',', '.'), $this->calc->disclaimer());

                return $this->out($txt, [], ['Não, obrigado']);

            case 'troca_carro':
                $ctx['troca'] = ['descricao' => $text];
                $ctx['etapa'] = 'troca_km';
                $session->context = $ctx;

                return $this->out('Anotado. Quantos km ele tem, aproximadamente?', [], ['30 mil', '60 mil', '100 mil', 'Não sei']);

            case 'troca_km':
                $ctx['troca']['km'] = $this->parseMoney($norm);
                $ctx['etapa'] = 'troca_contato_nome';
                $session->context = $ctx;

                return $this->out('Perfeito. Nossa avaliação é feita em até 1 hora na loja. Me diz seu nome para eu adiantar com o consultor.', [], []);

            case 'sim_contato_nome':
            case 'troca_contato_nome':
            case 'humano_nome':
            case 'busca_nome':
                if ($this->matches($norm, ['nao', 'não', 'obrigad'])) {
                    $session->context = [];

                    return $this->out('Tudo bem! Se precisar, é só chamar. Posso ajudar com mais alguma coisa?', [], $this->defaultQuick());
                }
                if (mb_strlen($text) < 2 || mb_strlen($text) > 80) {
                    return $this->out('Pode me dizer seu nome?', [], []);
                }
                $ctx['nome'] = Str::title($text);
                $ctx['etapa'] = str_replace('_nome', '_fone', $ctx['etapa']);
                $session->context = $ctx;
                $primeiro = explode(' ', $ctx['nome'])[0];

                return $this->out("Prazer, {$primeiro}! E qual seu WhatsApp com DDD?", [], []);

            case 'sim_contato_fone':
            case 'troca_contato_fone':
            case 'humano_fone':
            case 'busca_fone':
                $digits = preg_replace('/\D+/', '', $text);
                if (strlen($digits) < 10 || strlen($digits) > 13) {
                    return $this->out('Esse número parece incompleto. Manda com DDD, ex.: (15) 99999-0000.', [], []);
                }

                return $this->finish($session, $ctx, $text);
        }

        return null;
    }

    protected function askContact(BotSession $session, array $ctx, string $fluxo, string $pergunta): array
    {
        $ctx['fluxo'] = $fluxo;
        $ctx['etapa'] = $fluxo.'_nome';
        $session->context = $ctx;

        return $this->out($pergunta, [], []);
    }

    protected function finish(BotSession $session, array $ctx, string $fone): array
    {
        $fluxo = $ctx['fluxo'] ?? 'humano';
        $veiculo = isset($ctx['sim']['veiculo_id']) ? Vehicle::find($ctx['sim']['veiculo_id']) : (isset($ctx['ultimo_veiculo']) ? Vehicle::find($ctx['ultimo_veiculo']) : null);

        $resumo = match ($fluxo) {
            'simulacao' => sprintf('Simulação via bot: preço R$ %s, entrada R$ %s, %dx de R$ %s', number_format($ctx['sim']['preco'], 0, ',', '.'), number_format($ctx['sim']['entrada'], 0, ',', '.'), $ctx['sim']['parcelas'], number_format($ctx['sim']['parcela'], 2, ',', '.')),
            'troca' => 'Troca via bot: '.($ctx['troca']['descricao'] ?? '').($ctx['troca']['km'] ? ' · '.number_format($ctx['troca']['km'], 0, ',', '.').' km' : ''),
            'busca' => 'Busca via bot sem resultado: '.$this->describeFilters($ctx['ultima_busca'] ?? []),
            default => 'Pediu atendimento humano via bot',
        };

        $lead = $this->leads->create(LeadType::Bot, [
            'nome' => $ctx['nome'] ?? 'Visitante do chat',
            'fone' => $fone,
            'mensagem' => $resumo,
            'meta' => ['fluxo' => $fluxo, 'bot_session' => $session->uuid, 'sim' => $ctx['sim'] ?? null, 'troca' => $ctx['troca'] ?? null, 'busca' => $ctx['ultima_busca'] ?? null],
        ], $veiculo, 'bot');

        $session->nome = $ctx['nome'] ?? null;
        $session->fone = $fone;
        $session->lead_id = $lead->id;
        $session->context = ['lead_id' => $lead->id, 'ultimo_veiculo' => $veiculo?->id];

        $primeiro = explode(' ', $ctx['nome'] ?? 'tudo bem')[0];
        $wa = Whatsapp::link(sprintf('Olá! Sou %s, falei com o assistente do site da Autocom Motors. %s (ref. #%d)', $ctx['nome'] ?? '', $resumo, $lead->id));

        return $this->out("Pronto, {$primeiro}! Já passei tudo para a equipe e um consultor vai te chamar no WhatsApp em instantes. Se quiser adiantar, é só tocar abaixo.", [], ['Ver estoque', 'Simular outro'], [['Continuar no WhatsApp', $wa]]);
    }

    // ---------------------------------------------------------------------------------------
    // Busca
    // ---------------------------------------------------------------------------------------

    public function parseSearch(string $norm): array
    {
        $f = [];
        foreach (self::MARCAS as $m) {
            if (preg_match('/\b'.preg_quote($m, '/').'\b/u', $norm)) {
                $f['marca'] = self::ALIAS[$m] ?? $m;
                break;
            }
        }
        $modelos = Vehicle::published()->select('modelo')->distinct()->pluck('modelo')->map(fn ($m) => $this->norm($m))->filter()->all();
        foreach ($modelos as $m) {
            if (preg_match('/\b'.preg_quote($m, '/').'\b/u', $norm)) {
                $f['modelo'] = $m;
                break;
            }
        }
        foreach (['suv' => Carroceria::Suv, 'picape' => Carroceria::Picape, 'pickup' => Carroceria::Picape, 'caminhonete' => Carroceria::Picape, 'sedan' => Carroceria::Sedan, 'seda' => Carroceria::Sedan, 'hatch' => Carroceria::Hatch, 'minivan' => Carroceria::Minivan, 'utilitario' => Carroceria::Utilitario, 'perua' => Carroceria::Perua] as $k => $c) {
            if (preg_match('/\b'.$k.'\b/u', $norm)) {
                $f['carroceria'] = $c->value;
                break;
            }
        }
        if (preg_match('/\b(automatico|automatica|cvt)\b/u', $norm)) {
            $f['cambio'] = ['automatico', 'cvt', 'automatizado'];
        } elseif (preg_match('/\bmanual\b/u', $norm)) {
            $f['cambio'] = ['manual'];
        }
        if (preg_match('/\bdiesel\b/u', $norm)) {
            $f['combustivel'] = 'diesel';
        }
        if (preg_match('/\b(ate|até|no maximo|no máximo|maximo|máximo|menos de|abaixo de)\s*(?:de\s*)?(r\$\s*)?([\d.,]+)\s*(mil|k)?/u', $norm, $m)) {
            $f['preco_max'] = $this->toMoney($m[3], (bool) ($m[4] ?? false));
        } elseif (preg_match('/\b(a partir de|acima de|mais de)\s*(r\$\s*)?([\d.,]+)\s*(mil|k)?/u', $norm, $m)) {
            $f['preco_min'] = $this->toMoney($m[3], (bool) ($m[4] ?? false));
        } elseif (preg_match('/(?:r\$\s*)?([\d.,]+)\s*(mil|k)\b/u', $norm, $m) && ! isset($f['marca']) && ! isset($f['modelo'])) {
            $f['preco_max'] = $this->toMoney($m[1], true);
        }
        if (preg_match('/\b(20\d{2})\b/', $norm, $m)) {
            $f['ano_min'] = (int) $m[1];
        }
        if (preg_match('/\b(branco|preto|prata|cinza|vermelho|azul|verde|marrom|bege|dourado)\b/u', $norm, $m)) {
            $f['cor'] = $m[1];
        }

        return $f;
    }

    public function search(array $f)
    {
        return Vehicle::query()->published()->with('cover')
            ->when($f['marca'] ?? null, fn ($q, $v) => $q->whereRaw('LOWER(marca) LIKE ?', ['%'.$v.'%']))
            ->when($f['modelo'] ?? null, fn ($q, $v) => $q->whereRaw('LOWER(modelo) LIKE ?', ['%'.$v.'%']))
            ->when($f['carroceria'] ?? null, fn ($q, $v) => $q->where('carroceria', $v))
            ->when($f['cambio'] ?? null, fn ($q, $v) => $q->whereIn('cambio', $v))
            ->when($f['combustivel'] ?? null, fn ($q, $v) => $q->where('combustivel', $v))
            ->when($f['preco_max'] ?? null, fn ($q, $v) => $q->where('preco', '<=', $v))
            ->when($f['preco_min'] ?? null, fn ($q, $v) => $q->where('preco', '>=', $v))
            ->when($f['ano_min'] ?? null, fn ($q, $v) => $q->where('ano_mod', '>=', $v))
            ->when($f['cor'] ?? null, fn ($q, $v) => $q->whereRaw('LOWER(cor) LIKE ?', ['%'.$v.'%']))
            ->orderByDesc('destaque')->orderBy('preco')
            ->take(8)->get();
    }

    protected function describeFilters(array $f): string
    {
        $p = [];
        if (isset($f['carroceria'])) {
            $p[] = Carroceria::tryFrom($f['carroceria'])?->getLabel();
        }
        if (isset($f['marca'])) {
            $p[] = Str::title($f['marca']);
        }
        if (isset($f['modelo'])) {
            $p[] = Str::title($f['modelo']);
        }
        if (isset($f['cambio'])) {
            $p[] = $f['cambio'][0] === 'manual' ? 'manual' : 'automático';
        }
        if (isset($f['combustivel'])) {
            $p[] = $f['combustivel'];
        }
        if (isset($f['cor'])) {
            $p[] = $f['cor'];
        }
        if (isset($f['ano_min'])) {
            $p[] = 'a partir de '.$f['ano_min'];
        }
        if (isset($f['preco_max'])) {
            $p[] = 'até R$ '.number_format($f['preco_max'], 0, ',', '.');
        }
        if (isset($f['preco_min'])) {
            $p[] = 'a partir de R$ '.number_format($f['preco_min'], 0, ',', '.');
        }

        return $p ? '('.implode(', ', array_filter($p)).')' : '';
    }

    // ---------------------------------------------------------------------------------------
    // Utilitários
    // ---------------------------------------------------------------------------------------

    protected function isGlobalIntent(string $norm): bool
    {
        return $this->matches($norm, ['humano', 'atendente', 'menu', 'inicio', 'cancelar', 'estoque completo', 'locarx', 'alugar']);
    }

    protected function matches(string $norm, array $needles, bool $exact = false): bool
    {
        foreach ($needles as $n) {
            $n = $this->norm($n);
            if ($exact ? ($norm === $n || preg_match('/^'.preg_quote($n, '/').'[!. ]*$/u', $norm)) : str_contains($norm, $n)) {
                return true;
            }
        }

        return false;
    }

    protected function parseMoney(string $norm): ?float
    {
        if (preg_match('/(?:r\$\s*)?([\d.,]+)\s*(mil|k)?/u', $norm, $m) && $m[1] !== '' && $m[1] !== '.' && $m[1] !== ',') {
            return $this->toMoney($m[1], (bool) ($m[2] ?? false));
        }

        return null;
    }

    protected function toMoney(string $raw, bool $mil): float
    {
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
        $v = (float) $raw;
        if ($mil || $v < 1000) {
            $v *= 1000;
        }

        return $v;
    }

    protected function norm(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::of($s)->lower()->ascii()->value()));
    }

    protected function out(string $text, array $cards = [], array $quick = [], array $links = []): array
    {
        return ['text' => $text, 'cards' => $cards, 'quick' => $quick, 'links' => $links];
    }
}
