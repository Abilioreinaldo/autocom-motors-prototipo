<?php

namespace App\Filament\Pages;

use App\Enums\VehicleStatus;
use App\Filament\Resources\VehicleResource;
use App\Integrations\Webmotors\VehicleSourceInterface;
use App\Integrations\Webmotors\WebmotorsLinkParser;
use App\Integrations\Webmotors\WebmotorsVehicle;
use App\Models\Vehicle;
use App\Services\VehicleDuplicateFinder;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Importar por link da Webmotors:
 *  1) recebe a URL, extrai o ID do fim do path
 *  2) procura no estoque já sincronizado (vehicle_external_refs) e na fonte (API/fake)
 *  3) encontrado → preenche o formulário para revisão
 *     não encontrado → cadastro assistido guardando source_url
 *  4) checa duplicidade por external_id e por marca+modelo+ano+km (±500)
 */
class ImportarPorLink extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Integrações';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Importar por link';

    protected static ?string $title = 'Importar veículo por link da Webmotors';

    protected static string $view = 'filament.pages.importar-por-link';

    public ?array $urlData = ['url' => ''];

    public ?array $data = [];

    public ?string $externalId = null;

    public ?string $sourceUrl = null;

    public string $stage = 'url';          // url | review | manual | done

    public ?array $found = null;           // array do WebmotorsVehicle encontrado

    public ?array $duplicate = null;       // ['id'=>..,'titulo'=>..,'reason'=>..]

    public ?int $createdId = null;

    /** @var list<string> */
    public array $fotos = [];

    protected function getForms(): array
    {
        return ['urlForm', 'vehicleForm'];
    }

    public function mount(): void
    {
        $this->urlForm->fill(['url' => request()->query('url', '')]);
        $this->vehicleForm->fill(['status' => VehicleStatus::Disponivel->value, 'destaque' => false]);
    }

    public function urlForm(Form $form): Form
    {
        return $form->statePath('urlData')->schema([
            Forms\Components\TextInput::make('url')
                ->label('Link do anúncio na Webmotors')
                ->placeholder('https://www.webmotors.com.br/comprar/marca/modelo/.../54810001')
                ->required()
                ->helperText('Cole o link completo do anúncio ou apenas o número do ID. Exemplos no driver fake: 54810001, 54810003, 54810021, 54810022.')
                ->suffixAction(Forms\Components\Actions\Action::make('buscar')->icon('heroicon-o-magnifying-glass')->action('buscar'))
                ->extraInputAttributes(['x-on:keydown.enter.prevent' => '$wire.buscar()']),
        ]);
    }

    public function vehicleForm(Form $form): Form
    {
        return $form->statePath('data')->schema(VehicleResource::formSchema());
    }

    public function buscar(): void
    {
        $this->urlForm->validate();
        $this->duplicate = null;
        $this->found = null;

        try {
            $this->externalId = WebmotorsLinkParser::extractId($this->urlData['url']);
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Link inválido')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->sourceUrl = WebmotorsLinkParser::isWebmotorsUrl($this->urlData['url']) && str_contains($this->urlData['url'], '/') ? trim($this->urlData['url']) : null;

        $finder = app(VehicleDuplicateFinder::class);

        // 1) já está no estoque (sincronizado anteriormente)?
        if ($existing = $finder->byExternalId($this->externalId)) {
            $this->duplicate = ['id' => $existing->id, 'titulo' => $existing->titulo_completo, 'reason' => 'external_id'];
            $this->stage = 'url';
            Notification::make()->title('Este anúncio já está no estoque')->body($existing->titulo_completo)->warning()->send();

            return;
        }

        // 2) busca na fonte (API Site / fake)
        try {
            $item = app(VehicleSourceInterface::class)->fetchById($this->externalId);
        } catch (Throwable $e) {
            report($e);
            Notification::make()->title('Falha ao consultar a Webmotors')->body($e->getMessage())->danger()->persistent()->send();
            $item = null;
        }

        if ($item instanceof WebmotorsVehicle) {
            $this->found = $item->toArray();
            $this->fotos = $item->fotos;
            $this->sourceUrl = $item->sourceUrl ?? $this->sourceUrl;
            $attrs = $item->toVehicleAttributes();
            $this->vehicleForm->fill($attrs + ['status' => VehicleStatus::Disponivel->value, 'destaque' => false, 'preco_anterior' => null]);

            // 3) duplicidade por especificações (cadastro manual parecido)
            if ($dup = $finder->bySpecs($item->marca, $item->modelo, $item->anoMod, $item->km)) {
                $this->duplicate = ['id' => $dup->id, 'titulo' => $dup->titulo_completo, 'reason' => 'specs'];
            }
            $this->stage = 'review';

            return;
        }

        // não encontrado → cadastro assistido
        $this->fotos = [];
        $this->vehicleForm->fill(['status' => VehicleStatus::Disponivel->value, 'destaque' => false]);
        $this->stage = 'manual';
        Notification::make()->title('Anúncio não encontrado na fonte')->body('Preencha os dados manualmente; o link ficará salvo como referência (source_url).')->info()->send();
    }

    public function importar(bool $vincularDuplicado = false): void
    {
        $data = $this->vehicleForm->getState();
        $finder = app(VehicleDuplicateFinder::class);

        // re-checa duplicidade por specs com os dados finais do formulário
        $dup = $finder->bySpecs($data['marca'], $data['modelo'], (int) $data['ano_mod'], (int) $data['km']);
        if ($dup && ! $vincularDuplicado) {
            $this->duplicate = ['id' => $dup->id, 'titulo' => $dup->titulo_completo, 'reason' => 'specs'];
            Notification::make()->title('Possível duplicidade')->body('Já existe '.$dup->titulo_completo.' com km semelhante. Escolha "Vincular ao existente" ou "Cadastrar mesmo assim".')->warning()->send();

            return;
        }

        $vehicle = DB::transaction(function () use ($data, $dup, $vincularDuplicado) {
            if ($vincularDuplicado && $dup) {
                $vehicle = $dup;
            } else {
                $vehicle = Vehicle::create($data + ['published_at' => now()]);
            }

            if ($this->externalId) {
                $vehicle->externalRefs()->updateOrCreate(
                    ['provider' => 'webmotors'],
                    [
                        'external_id' => $this->externalId,
                        'source_url' => $this->sourceUrl,
                        'payload' => $this->found,
                        'payload_hash' => $this->found ? hash('sha256', json_encode([$this->found, $this->fotos])) : null,
                        'synced_at' => $this->found ? now() : null,
                        // tudo que foi revisado manualmente no formulário fica protegido do sync
                        'locked_fields' => $vincularDuplicado ? VehicleResource::SYNC_FIELDS : ['preco', 'descricao'],
                    ]
                );
            }

            if ($this->fotos && $vehicle->photos()->count() === 0) {
                foreach ($this->fotos as $i => $url) {
                    $vehicle->photos()->create(['path' => $url, 'ordem' => $i, 'capa' => $i === 0, 'alt' => $vehicle->titulo]);
                }
            }

            return $vehicle;
        });

        $this->createdId = $vehicle->id;
        $this->stage = 'done';
        Notification::make()->title($vincularDuplicado ? 'Anúncio vinculado ao veículo existente' : 'Veículo importado')->body($vehicle->titulo_completo)->success()->send();
    }

    public function vincular(): void
    {
        $this->importar(vincularDuplicado: true);
    }

    public function cadastrarMesmoAssim(): void
    {
        $data = $this->vehicleForm->getState();
        $vehicle = Vehicle::create($data + ['published_at' => now()]);
        if ($this->externalId) {
            $vehicle->externalRefs()->create(['provider' => 'webmotors', 'external_id' => $this->externalId, 'source_url' => $this->sourceUrl, 'payload' => $this->found, 'synced_at' => $this->found ? now() : null, 'locked_fields' => ['preco', 'descricao']]);
        }
        foreach ($this->fotos as $i => $url) {
            $vehicle->photos()->create(['path' => $url, 'ordem' => $i, 'capa' => $i === 0, 'alt' => $vehicle->titulo]);
        }
        $this->createdId = $vehicle->id;
        $this->stage = 'done';
        Notification::make()->title('Veículo cadastrado')->success()->send();
    }

    public function reiniciar(): void
    {
        $this->reset(['data', 'externalId', 'sourceUrl', 'found', 'duplicate', 'createdId', 'fotos']);
        $this->stage = 'url';
        $this->urlForm->fill(['url' => '']);
        $this->vehicleForm->fill(['status' => VehicleStatus::Disponivel->value, 'destaque' => false]);
    }

    public function getEditUrl(): ?string
    {
        return $this->createdId ? VehicleResource::getUrl('edit', ['record' => $this->createdId]) : null;
    }

    public function getDuplicateUrl(): ?string
    {
        return $this->duplicate ? VehicleResource::getUrl('edit', ['record' => $this->duplicate['id']]) : null;
    }
}
