# Manual técnico da API (Movvi)

Base URL (local): `http://127.0.0.1:8000`

## Autenticação (Sanctum)

Os endpoints em `/api/v1/*` usam `auth:sanctum`.

- Header obrigatório:
  - `Authorization: Bearer <access_token>`
  - `Accept: application/json`

### Obter token

1) Fazer login em `POST /api/login`.
2) Usar o `access_token` retornado como Bearer token nos requests seguintes.

Notas:
- O token é um *personal access token* do Sanctum.
- Se o token estiver ausente ou inválido, o servidor responde com `401 Unauthorized`.

## 1) `POST /api/login`

Cria um token de autenticação (Sanctum) para um utilizador.

### Request

- Method: `POST`
- Path: `/api/login`
- Headers:
  - `Accept: application/json`
  - `Content-Type: application/json`
- Body (JSON):
  - `email` (string, obrigatório; formato email)
  - `password` (string, obrigatório)

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
  "access_token": "..."
}
```

### Erros

- `422 Unprocessable Entity`: validação falhou (email/password ausentes, email inválido, credenciais inválidas).

## 2) `GET /api/v1/sales-by-week/{date}`

Devolve o relatório de vendas por semana TVDE (usa `Reports::getWeekReport`).

### Segurança

- Requer token (Sanctum).
- Requer permissão `company_report_access` (Gate).

### Request

- Method: `GET`
- Path: `/api/v1/sales-by-week/{date}`
- `{date}`: obrigatório no formato `d-m-Y` (ex: `03-11-2025`)
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

Estrutura (alto nível):
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
- No estado atual do código, o `company_id` usado é fixo (`1`).

### Erros

- `401 Unauthorized`: token ausente/inválido.
- `403 Forbidden`: permissão `company_report_access` em falta.
- `404 Not Found`: semana TVDE não encontrada (por `start_date`).
- `422 Unprocessable Entity`: formato de data inválido.

## 3) `GET /api/v1/vehicle-profitabilities`

Expõe os mesmos cálculos usados na UI de `/admin/vehicle-profitabilities`, mas em JSON:
- **Modo “week”** (default): totais por viatura para uma semana (`VehicleProfitabilityService::makeWeek`).
- **Modo “vehicle”**: detalhe por motorista para uma viatura numa semana (`VehicleProfitabilityService::make`).

### Segurança

- Requer token (Sanctum).
- Requer permissão `vehicle_profitability_access` (Gate).

### Request

- Method: `GET`
- Path: `/api/v1/vehicle-profitabilities`
- Headers:
  - `Accept: application/json`
  - `Authorization: Bearer <access_token>`
- Query params:
  - `tvde_week_id` (int) **ou** `date` (string `d-m-Y`) — obrigatório
  - `vehicle_id` (int) — opcional

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
- `mode=vehicle`: devolve `vehicle`, `week`, `revenues` e `meta.drivers[]` (inclui `usage_seconds` e flags de validação).

### Erros

- `401 Unauthorized`: token ausente/inválido.
- `403 Forbidden`: permissão `vehicle_profitability_access` em falta.
- `404 Not Found`: semana (`tvde_week_id` / `date`) ou viatura (`vehicle_id`) não encontrada.
- `422 Unprocessable Entity`: parâmetros obrigatórios em falta ou `date` com formato inválido.

