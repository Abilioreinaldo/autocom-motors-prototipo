# Plataforma Autocom Motors

Aplicação web da **Autocom Motors** (loja multimarcas de Tatuí-SP): site público premium + painel do lojista + integração com a Webmotors.

| Camada | Tecnologia |
| --- | --- |
| Backend | Laravel 12 · PHP 8.3 |
| Interatividade | Livewire 3 |
| Painel | Filament 3 |
| Banco | MySQL 8 (SQLite nos testes) |
| Front | Blade + Tailwind CSS 4 via Vite |
| Testes | Pest 3 |
| Deploy | Docker Compose (php-fpm + nginx + mysql + queue + scheduler) |

---

## 1. Visão geral

### Site público
- **Home** `/`: hero, busca rápida, destaques, recém-chegados, ofertas, institucional, banners, troca, simulador, parceria **LocarX** (preto `#101010` / vermelho `#e23036`), contato.
- **Estoque** `/estoque`: filtros Livewire (marca, modelo, versão, ano, preço, km, combustível, câmbio, carroceria, cor, ofertas), ordenação e paginação; filtros refletidos na URL.
- **Veículo** `/veiculo/{slug}`: galeria com lightbox, ficha técnica, opcionais, simulador, proposta, troca e relacionados.
- **WhatsApp em tudo**: `/whatsapp/{veiculo?}` registra um lead do tipo `whatsapp` e redireciona para `wa.me` com a mensagem *"Olá! Vi o {marca modelo ano} no site da Autocom Motors..."* (número e textos em Configurações).
- **Leads**: proposta, troca, financiamento e contato (Livewire) + endpoint `POST /leads/{tipo}`.
- **Simulador** (`App\Services\FinancingCalculator`, sistema PRICE: `parcela = f·i / (1 − (1+i)^−n)`), taxa padrão 1,69% a.m. editável no painel, com o aviso *"Simulação ilustrativa, sujeita à análise e condições da instituição financeira."*
- **Assistente virtual** (`App\Services\BotEngine`): chat baseado em regras — busca por marca/modelo/carroceria/câmbio/"até X mil", simulação em etapas, troca, LocarX, atendimento humano, captura nome + fone → lead `bot`. Conversas persistem em `bot_sessions`.

### Painel do lojista (`/admin`)
- **Veículos**: cadastro completo, abas por status, filtros, ações em massa, **fotos** (upload com editor, por URL, capa, reordenação), campos protegidos do sync.
- **Leads**: abas por status (funil), troca de status na própria lista, registrar contato (histórico em `lead_events`), link `wa.me`, atribuição de responsável.
- **Banners**, **Usuários** (admin/gerente/vendedor), **Assistente virtual** (transcrições + liga/desliga), **Sincronizações** (rodar/enfileirar o sync Webmotors).
- **Importar por link** (Webmotors): cola-se a URL → ID extraído do fim do path → busca no estoque sincronizado e na fonte → formulário de revisão; não encontrado → cadastro assistido com `source_url`; duplicidade por `external_id` e por marca+modelo+ano+km (±500 km) com opção de vincular.
- **Configurações**: loja, WhatsApp, contato, financiamento, LocarX, bot e status da Webmotors.
- **Dashboard**: KPIs (estoque, valor total, leads novos, leads 7 dias), leads por dia, funil por status, estoque por carroceria, últimos leads.

### Integração Webmotors (desacoplada)
```
App\Integrations\Webmotors\VehicleSourceInterface   fetchInventory() · fetchById() · pushLead()
 ├─ FakeWebmotorsClient   fixtures locais (IDs 54810001, 54810003, 54810021, 54810022)
 └─ ApiSiteClient         API Site oficial (OAuth client-credentials) — config/webmotors.php
```
- Driver escolhido por `WEBMOTORS_DRIVER=fake|api`; integração **desligada por padrão** (`WEBMOTORS_ENABLED=false`).
- Job `SyncWebmotorsInventory` + comando `autocom:sync-webmotors`, agendado **a cada 30 min** (`routes/console.php`).
- Upsert por `(provider, external_id)` em `vehicle_external_refs`; campos listados em `locked_fields` (editados no painel) **nunca** são sobrescritos; `payload_hash` evita gravações redundantes; anúncios ausentes do feed viram `vendido`.
- **Scraping do site da Webmotors é proibido** — apenas a API oficial contratada pelo lojista.

---

## 2. Rodando localmente

Pré-requisitos: PHP 8.3 (ext: pdo_mysql/sqlite, intl, gd, zip, mbstring), Composer 2, Node 20+, MySQL 8 (ou SQLite).

```bash
cd plataforma
composer install
cp .env.example .env
php artisan key:generate
```

Edite o `.env` (MySQL) ou use SQLite rapidamente:

```bash
sed -i 's/^DB_CONNECTION=mysql/DB_CONNECTION=sqlite/; s/^DB_HOST=/#DB_HOST=/; s/^DB_PORT=/#DB_PORT=/; s/^DB_DATABASE=.*/#DB_DATABASE=/; s/^DB_USERNAME=/#DB_USERNAME=/; s/^DB_PASSWORD=/#DB_PASSWORD=/' .env
touch database/database.sqlite
```

```bash
php artisan migrate --seed
php artisan storage:link
npm install && npm run build      # ou: npm run dev
php artisan serve
```

- Site: <http://127.0.0.1:8000>
- Painel: <http://127.0.0.1:8000/admin> — **admin@exemplo.com / password** (seed)
- Sync de demonstração com o driver fake: `php artisan autocom:sync-webmotors --force`

Os seeds criam 10 veículos **claramente fictícios** (sufixo "DEMO"), banners, leads de exemplo e as configurações padrão.

### Testes
```bash
php artisan test
```
Cobrem: calculadora PRICE, parser de link Webmotors, duplicidade, sincronização com cliente fake (criação, idempotência, `locked_fields`, ausentes), criação de leads, páginas do site, formulários Livewire, bot e páginas do painel (importar por link, configurações, trava de campos, registrar contato).

---

## 3. Deploy em VPS (Hostinger) com Docker

Arquivos: `Dockerfile` (multi-stage: node → composer → php-fpm 8.3), `docker-compose.yml` (app, nginx, mysql 8, queue worker, scheduler), `docker/nginx/default.conf`, `docker/php/*`, `docker/mysql/my.cnf`, `docker/entrypoint.sh` (espera o MySQL, `migrate --force`, caches).

### 3.1 Preparar o servidor (Ubuntu 22.04/24.04)
```bash
sudo apt update && sudo apt install -y ca-certificates curl git
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER && newgrp docker
```

### 3.2 Clonar e configurar
```bash
git clone https://github.com/Abilioreinaldo/autocom-motors-prototipo.git
cd autocom-motors-prototipo/plataforma
cp .env.production.example .env
nano .env   # APP_URL, senhas do banco, WhatsApp, (opcional) credenciais da Webmotors
```

### 3.3 Subir
```bash
docker compose build
docker compose up -d
docker compose exec app php artisan key:generate --force
docker compose restart app queue scheduler      # recarrega a APP_KEY
docker compose exec app php artisan migrate --seed --force
docker compose exec app php artisan storage:link
```
> O entrypoint já executa `migrate --force` e `storage:link` a cada subida do container `app` (`AUTOCOM_AUTO_MIGRATE=true`). O `--seed` é só na primeira vez.

Verifique: `curl -I http://IP-DO-SERVIDOR:8080` deve responder `200`.

### 3.4 Domínio + HTTPS (nginx do host como proxy + certbot)
Aponte o DNS (`A` → IP da VPS). No host:
```bash
sudo apt install -y nginx certbot python3-certbot-nginx
sudo tee /etc/nginx/sites-available/autocom <<'NGINX'
server {
    server_name autocommotors.com.br www.autocommotors.com.br;
    client_max_body_size 40m;
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
NGINX
sudo ln -sf /etc/nginx/sites-available/autocom /etc/nginx/sites-enabled/autocom
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d autocommotors.com.br -d www.autocommotors.com.br
```
Com o proxy ativo, mantenha `APP_URL=https://...` e `SESSION_SECURE_COOKIE=true` no `.env`. O Laravel já confia nos cabeçalhos `X-Forwarded-*` do proxy.

### 3.5 Operação
```bash
docker compose logs -f app queue scheduler     # logs
docker compose exec app php artisan autocom:sync-webmotors   # sync manual
docker compose exec mysql mysqldump -u root -p autocom > backup-$(date +%F).sql   # backup
```
Atualizar versão:
```bash
git pull
docker compose build && docker compose up -d
```

### 3.6 Ativar a Webmotors de verdade
No `.env`: `WEBMOTORS_ENABLED=true`, `WEBMOTORS_DRIVER=api`, `WEBMOTORS_CLIENT_ID`, `WEBMOTORS_CLIENT_SECRET`, `WEBMOTORS_DEALER_ID` (fornecidos pela Webmotors ao lojista). Reinicie `app`, `queue` e `scheduler`. Ajuste os caminhos dos endpoints em `App\Integrations\Webmotors\ApiSiteClient` conforme a documentação da versão da API Site contratada.

---

## 4. Estrutura principal
```
app/
  Enums/                 VehicleStatus, LeadType, LeadStatus, Cambio, Combustivel, Carroceria
  Filament/              Resources (Vehicle, Lead, Banner, User, BotSession, SyncLog), Pages (ImportarPorLink, Configuracoes), Widgets
  Http/Controllers/Site/ Home, Vehicle, Whatsapp, Lead
  Integrations/Webmotors VehicleSourceInterface, FakeWebmotorsClient, ApiSiteClient, WebmotorsLinkParser, WebmotorsVehicle (DTO)
  Jobs/                  SyncWebmotorsInventory
  Livewire/Site/         Estoque, Simulador, LeadForm, ChatBot
  Models/                Vehicle, VehiclePhoto, Lead, LeadEvent, Banner, Setting, VehicleExternalRef, SyncLog, BotSession, User
  Services/              FinancingCalculator, LeadService, BotEngine, VehicleDuplicateFinder, WebmotorsSyncService
  Support/               Settings (settings com cache), Whatsapp (links wa.me)
config/autocom.php       dados da loja, WhatsApp, financiamento, LocarX, bot
config/webmotors.php     driver, credenciais, intervalo, tolerância de km
```

## 5. Avisos
- Preços, quilometragens, placas e contatos dos seeds são **ilustrativos**; fotos dos seeds são placeholders — substitua no painel.
- O `index.html` na raiz do repositório é o protótipo estático (GitHub Pages) e não faz parte desta aplicação.
