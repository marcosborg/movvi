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
- `mode=week`: devolve `week`, `vehicles[]` e `totals` (aluguer, percentagem, total).
- `mode=vehicle`: devolve `vehicle`, `week`, `revenues` e `meta.drivers[]` (inclui `usage_seconds` e flags de validacao).

### Erros

- `401 Unauthorized`: token ausente/invalido.
- `403 Forbidden`: permissao `vehicle_profitability_access` em falta.
- `404 Not Found`: semana (`tvde_week_id` / `date`) ou viatura (`vehicle_id`) nao encontrada.
- `422 Unprocessable Entity`: parametros obrigatorios em falta ou `date` com formato invalido.
