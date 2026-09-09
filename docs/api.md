# Manual tecnico da API (Movvi)

Base URL (local): `http://127.0.0.1:8000`

## Autenticacao (Sanctum)

Os endpoints em `/api/v1/*` usam `auth:sanctum`.

- Header obrigatorio:
  - `Authorization: Bearer <access_token>`
  - `Accept: application/json`

### Obter token

1. Fazer login em `POST /api/login`.
2. Usar o `access_token` retornado como Bearer token nos requests seguintes.

Notas:
- O token e um personal access token do Sanctum.
- Se o token estiver ausente ou invalido, o servidor responde com `401 Unauthorized`.

## 1) `POST /api/login`

Cria um token de autenticacao (Sanctum) para um utilizador.

### Request

- Method: `POST`
- Path: `/api/login`
- Headers:
  - `Accept: application/json`
  - `Content-Type: application/json`
- Body (JSON):
  - `email` (string, obrigatorio; formato email)
  - `password` (string, obrigatorio)

Exemplo:
```bash
curl -s -X POST "http://127.0.0.1:8000/api/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"user@example.com\",\"password\":\"secret\"}"
```

### Response (200)

```json
{
  "access_token": "...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Nome",
    "email": "user@example.com",
    "roles": ["Motoristas"]
  }
}
```

### Erros

- `422 Unprocessable Entity`: validacao falhou (email/password ausentes, email invalido, credenciais invalidas).

## 2) `GET /api/v1/mobile/me`

Payload estavel para inicializar a app mobile apos login.

### Seguranca

- Requer token (Sanctum).

### Request

- Method: `GET`
- Path: `/api/v1/mobile/me`
- Headers:
  - `Accept: application/json`
  - `Authorization: Bearer <access_token>`

### Response (200)

```json
{
  "user": {
    "id": 1,
    "name": "Nome",
    "email": "user@example.com",
    "roles": ["Motoristas"]
  },
  "driver": {
    "id": 10,
    "code": "DRV001",
    "name": "Nome",
    "email": "user@example.com",
    "phone": "910000000",
    "company": {
      "id": 1,
      "name": "Movvi"
    },
    "state": null,
    "contract_vat": null
  }
}
```

## 3) `GET /api/v1/mobile/dashboard`

Resumo semanal do utilizador autenticado para uso no mobile.

### Seguranca

- Requer token (Sanctum).

### Request

- Method: `GET`
- Path: `/api/v1/mobile/dashboard`
- Headers:
  - `Accept: application/json`
  - `Authorization: Bearer <access_token>`
- Query params:
  - `date` (string `d-m-Y`) - opcional; quando ausente usa a semana TVDE mais recente

### Response (200)

```json
{
  "driver": {},
  "week": {
    "id": 123,
    "number": 10,
    "start_date": "2026-03-09",
    "end_date": "2026-03-15",
    "requested_date": "2026-03-09"
  },
  "account_summary": {},
  "balance": {
    "value": 0,
    "last_balance": 0,
    "new_balance": 0,
    "vat": 0,
    "rf": 0,
    "final": 0
  },
  "vehicle": {
    "id": 1,
    "license_plate": "00-AA-00",
    "model": "Model"
  },
  "vehicle_profitability": {}
}
```

### Erros

- `401 Unauthorized`: token ausente/invalido.
- `404 Not Found`: utilizador sem motorista associado ou semana nao encontrada.

## 4) `GET /api/v1/sales-by-week/{date}`

Devolve o relatorio de vendas por semana TVDE (usa `Reports::getWeekReport`).

### Seguranca

- Requer token (Sanctum).
- Requer permissao `company_report_access` (Gate).

### Request

- Method: `GET`
- Path: `/api/v1/sales-by-week/{date}`
- `{date}`: obrigatorio no formato `d-m-Y` (ex: `03-11-2025`)
- Headers:
  - `Accept: application/json`
  - `Authorization: Bearer <access_token>`

Exemplo:
```bash
curl -s "http://127.0.0.1:8000/api/v1/sales-by-week/03-11-2025" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <access_token>"
```

### Response (200)

Estrutura (alto nivel):
```json
{
  "requested_date": "03-11-2025",
  "start_date": "...",
  "end_date": "...",
  "tvde_week_id": 123,
  "data": {}
}
```

Notas:
- No estado atual do codigo, o `company_id` usado e fixo (`1`).

### Erros

- `401 Unauthorized`: token ausente/invalido.
- `403 Forbidden`: permissao `company_report_access` em falta.
- `404 Not Found`: semana TVDE nao encontrada (por `start_date`).
- `422 Unprocessable Entity`: formato de data invalido.

## 5) `GET /api/v1/vehicle-profitabilities`

Expoe os mesmos calculos usados na UI de `/admin/vehicle-profitabilities`, mas em JSON:
- Modo `week` (default): totais por viatura para uma semana (`VehicleProfitabilityService::makeWeek`).
- Modo `vehicle`: detalhe por motorista para uma viatura numa semana (`VehicleProfitabilityService::make`).

### Seguranca

- Requer token (Sanctum).
- Requer permissao `vehicle_profitability_access` (Gate).

### Request

- Method: `GET`
- Path: `/api/v1/vehicle-profitabilities`
- Headers:
  - `Accept: application/json`
  - `Authorization: Bearer <access_token>`
- Query params:
  - `tvde_week_id` (int) ou `date` (string `d-m-Y`) - obrigatorio
  - `vehicle_id` (int) - opcional
  - `company_id` (int) - opcional; limita a empresa

#### Exemplo (modo week)
```bash
curl -s "http://127.0.0.1:8000/api/v1/vehicle-profitabilities?tvde_week_id=123" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <access_token>"
```

#### Exemplo (modo vehicle)
```bash
curl -s "http://127.0.0.1:8000/api/v1/vehicle-profitabilities?tvde_week_id=123&vehicle_id=456" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <access_token>"
```

#### Exemplo (resolver semana por data)
```bash
curl -s "http://127.0.0.1:8000/api/v1/vehicle-profitabilities?date=03-11-2025" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <access_token>"
```

### Response (200)

Envelope comum:
```json
{
  "mode": "week",
  "params": {
    "tvde_week_id": 123
  },
  "data": {}
}
```

Notas sobre `data`:
- `mode=week`: devolve `week`, `vehicles[]` e `totals` (cedência, percentagem, total).
- `mode=vehicle`: devolve `vehicle`, `week`, `revenues` e `meta.drivers[]` (inclui `usage_seconds` e flags de validacao).

### Erros

- `401 Unauthorized`: token ausente/invalido.
- `403 Forbidden`: permissao `vehicle_profitability_access` em falta.
- `404 Not Found`: semana (`tvde_week_id` / `date`) ou viatura (`vehicle_id`) nao encontrada.
- `422 Unprocessable Entity`: parametros obrigatorios em falta ou `date` com formato invalido.

## 6) Integracao Conta Azul

Os endpoints abaixo usam a ligacao OAuth 2.0 guardada por empresa no backend Laravel. A app mobile ou qualquer cliente interno nunca fala diretamente com a API externa da Conta Azul.

### Pre-requisitos

- Configurar no `.env`:
  - `CONTA_AZUL_CLIENT_ID`
  - `CONTA_AZUL_CLIENT_SECRET`
  - `CONTA_AZUL_REDIRECT_URI`
- Criar a ligacao via backoffice em:
  - `/admin/companies/{company}/conta-azul`

### 6.1 `GET /api/v1/conta-azul/status`

Devolve o estado da ligacao da empresa autenticada com a Conta Azul.

- Requer token (Sanctum)
- Requer role `Admin`
- Query params:
  - `company_id` (opcional; apenas para `Admin`)

### 6.2 `GET /api/v1/conta-azul/accounts`

Lista contas financeiras da empresa ligada.

- Requer token (Sanctum)
- Requer role `Admin`
- Query params:
  - `company_id` (opcional; apenas para `Admin`)
  - restantes params sao reenviados para a Conta Azul, por exemplo `pagina` e `tamanho_pagina`

### 6.3 `GET /api/v1/conta-azul/balances`

Lista contas financeiras e acrescenta `saldo_atual` por conta.

- Requer token (Sanctum)
- Requer role `Admin`

### 6.4 `GET /api/v1/conta-azul/categories`

Lista categorias financeiras da Conta Azul.

### 6.5 `GET /api/v1/conta-azul/receivables`

Consulta contas a receber via endpoint `contas-a-receber/buscar`.

### 6.6 `GET /api/v1/conta-azul/payables`

Consulta contas a pagar via endpoint `contas-a-pagar/buscar`.

### 6.7 `GET /api/v1/conta-azul/manager/profit-loss`

Camada canonica para a demonstracao de resultados do gestor.

Devolve:
- `summary`
- `revenue_categories`
- `expense_categories`
- `totals`

Nota:
- nesta primeira versao, o resultado e agregado a partir de `receivables` e `payables`
- o mapeamento fino por DRE/categoria podera ser afinado quando houver payload real da conta ligada

### 6.8 `GET /api/v1/conta-azul/manager/movements`

Camada canonica para extratos de movimentos.

Devolve:
- `accounts`
- `summary`
- `movements`

Combina:
- contas financeiras com saldo atual
- movimentos de entrada (receivables)
- movimentos de saida (payables)

### 6.9 `GET /api/v1/conta-azul/manager/expenses`

Camada canonica para leitura de despesas.

Devolve:
- `summary`
- `categories`
- `items`
- `pagination`

Query params:
- `page`: pagina da lista de despesas (default `1`)
- `per_page`: itens por pagina (default `20`, maximo `100`)

Inclui:
- total de despesas
- despesas abertas
- despesas pagas
- despesas vencidas

Os totais e categorias sao calculados sobre todas as paginas devolvidas pela Conta Azul. Apenas `items` e paginado. A leitura integral fica em cache durante 5 minutos por empresa e periodo.

### Erros esperados

- `401 Unauthorized`: token Sanctum ausente ou invalido
- `403 Forbidden`: utilizador sem role `Admin`
- `404 Not Found`: empresa nao encontrada para o utilizador autenticado
- `422 Unprocessable Entity`: ligacao Conta Azul ausente, OAuth mal configurado, ou erro devolvido pela API externa

## Dashboard externo: receitas e histórico semanal

O dashboard pode obter os dados diretamente por JSON, sem scraping HTML nem
PDFs. As rotas de relatórios da empresa e Conta Azul exigem **Admin**; a
rentabilidade mantém a permissão `vehicle_profitability_access`. Todas exigem
`Authorization: Bearer <access_token>` e `Accept: application/json`.

### Descobrir semanas — `GET /api/v1/weeks`

Parâmetros: `date_from` e `date_to` (`YYYY-MM-DD`, filtram o início da semana),
`page` (mínimo 1) e `per_page` (1–100, padrão 24).
A resposta mantém `filters` e `weeks`; cada semana acrescenta `year` (ano ISO,
que pode diferir do ano civil do primeiro dia). A paginação é:

```json
{"pagination":{"current_page":1,"per_page":24,"total":26,"last_page":2}}
```

Percorrer até `last_page`. A existência de uma semana não certifica que esteja
fechada: não há estado explícito de fecho nesta API. O consumidor agenda as
consultas e decide quando atualizar o dashboard. Não assumir que os dados de
semanas já consultadas são imutáveis.

### Receita operacional — `GET /api/v1/company-reports/operational-revenue`

Parâmetros:

- `company_id`: ID positivo, opcional. Sem ele, usa a empresa do motorista
  associado ao utilizador, depois a empresa principal ou a primeira empresa.
  Para a integração, enviar sempre a empresa pretendida.
- `tvde_week_id`: ID positivo da semana, ou `date` no formato `d-m-Y`.
  Neste endpoint pelo menos um é obrigatório. A data pode ser qualquer dia da semana.
- Com ID e data, ambos têm de identificar a mesma semana.

Exemplo ilustrativo de resposta (os valores não são dados certificados de produção):

```json
{
  "company": {"id": 1, "name": "Empresa exemplo"},
  "week": {"id": 123, "number": 1, "year": 2026,
    "start_date": "29-12-2025", "end_date": "04-01-2026"},
  "currency": "EUR",
  "data": {"car_hire": 100, "percent_value": 20,
    "adjustments": -5, "operational_revenue": 115}
}
```

`operational_revenue = car_hire + percent_value + adjustments`, exatamente os
componentes do cartão de receita operacional do relatório da empresa. Os
montantes são números JSON, sem formatação monetária; formatar para duas casas
na apresentação. Não substituir esta receita pela soma da rentabilidade das viaturas.

### Relatório completo — `GET /api/v1/company-reports/weekly`

Aceita os mesmos filtros de empresa e semana. Mantém `company`, `week`,
`data.drivers` e `data.totals` e os campos anteriores. Acrescenta:

- `data.totals.operational_revenue`: a mesma fórmula do endpoint resumido.
- `data.drivers[].total_net`: `uber_net + bolt_net`, rendimento líquido das
  plataformas **antes das deduções da empresa**; não é faturação bruta das plataformas.
- O campo existente `data.drivers[].total` é o valor final semanal do motorista;
  saldos anteriores e novos continuam nos campos próprios.

Sem filtro de semana, esta rota mantém a escolha da semana de início mais
recente cadastrada, que não é necessariamente a última semana fechada.
Um filtro explícito inválido nunca é substituído silenciosamente pela última semana.

### Rentabilidade — `GET /api/v1/vehicle-profitabilities`

Acrescenta `company_id` opcional. No modo semanal limita as viaturas à empresa;
com `vehicle_id`, uma viatura de outra empresa devolve `404`.
Sem `company_id`, mantém o âmbito global anterior. A seleção por `date` nesta
rota continua a exigir a **data de início** da semana; preferir `tvde_week_id`.

A receita das viaturas segue o serviço de rentabilidade: cedência, comissão e
ajustes atribuídos às viaturas, conforme as regras de alocação temporal e validação.
O modo semanal exclui viaturas de serviço e considera os critérios existentes
para viaturas suspensas e períodos de utilização. Não há um atraso fixo de
vários dias configurado para carros novos. Diferenças face à receita operacional
exigem reconciliação do âmbito e das alocações; não significam automaticamente
“ajustes globais”.

### Fluxo de integração e Conta Azul

Obter o token com `POST /api/login` (email e password do **Movvi**). Usar os IDs
reais devolvidos pela API; os placeholders abaixo devem ser substituídos.
Enviar explicitamente empresa/semana e confirmar a semana devolvida.

```bash
curl 'https://movvi.com.pt/api/login' -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"<password>"}'
curl 'https://movvi.com.pt/api/v1/weeks?page=1&per_page=100' \
  -H 'Authorization: Bearer <access_token>' -H 'Accept: application/json'
curl 'https://movvi.com.pt/api/v1/company-reports/operational-revenue?company_id=<company_id>&tvde_week_id=<week_id>' \
  -H 'Authorization: Bearer <access_token>' -H 'Accept: application/json'
curl 'https://movvi.com.pt/api/v1/company-reports/weekly?company_id=<company_id>&tvde_week_id=<week_id>' \
  -H 'Authorization: Bearer <access_token>' -H 'Accept: application/json'
curl 'https://movvi.com.pt/api/v1/vehicle-profitabilities?company_id=<company_id>&tvde_week_id=<week_id>' \
  -H 'Authorization: Bearer <access_token>' -H 'Accept: application/json'
curl 'https://movvi.com.pt/api/v1/conta-azul/manager/expenses?company_id=<company_id>&data_vencimento_de=2026-09-01&data_vencimento_ate=2026-09-30&page=1&per_page=100' \
  -H 'Authorization: Bearer <access_token>' -H 'Accept: application/json'
```

As despesas usam a ligação Conta Azul já autorizada para a empresa no backoffice
Movvi (`/admin/companies/{company}/conta-azul`). Consultar
`GET /api/v1/conta-azul/status?company_id=<company_id>` para verificar o estado.
O cliente não precisa de implementar outro OAuth para consumir esta API Movvi.
Não usar email/password Conta Azul como token Movvi.

Em `/conta-azul/manager/expenses`, as datas filtram **vencimento**, não constituem
por si uma apuração contabilística por competência. Sem datas, o padrão é o mês
atual. `items` é paginado (`page`, `per_page`, padrão 20, máximo 100), enquanto
`summary` e `categories` abrangem todas as páginas carregadas da Conta Azul.
A leitura integral tem cache de cinco minutos por empresa e período. Não somar
os totais do resumo uma vez por página. `/manager/profit-loss` e
`/manager/movements` continuam disponíveis; não somar novamente receitas Movvi
já representadas no Conta Azul.

### Erros dos relatórios da empresa

- `401`: autenticação ausente/inválida.
- `403`: utilizador sem papel Admin.
- `404`: empresa ou semana inexistente, incluindo data sem semana cadastrada.
- `422`: formato/ID inválido, filtro vazio, ID e data contraditórios, ou seleção
  ausente na rota de receita operacional.

A API Laravel pode devolver `message`/`errors` nos erros de validação e `error`
em respostas existentes; verificar sempre o status HTTP. Nas rotas Conta Azul,
`422` também pode indicar falta de ligação, configuração OAuth ou falha externa.
