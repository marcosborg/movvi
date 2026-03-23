# API Sanctum

Documentacao pratica da API protegida por Sanctum para integracao por developers externos.

## Base URL

- Producao: `https://movvi.com.pt/api`

## Autenticacao

A API usa token Bearer gerado por Sanctum.

### Login

```http
POST /api/login
Content-Type: application/json
Accept: application/json
```

Body:

```json
{
  "email": "user@example.com",
  "password": "secret"
}
```

Resposta:

```json
{
  "access_token": "TOKEN",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Nome",
    "email": "user@example.com",
    "roles": ["Admin"]
  }
}
```

Header a enviar nas rotas autenticadas:

```http
Authorization: Bearer TOKEN
Accept: application/json
```

### Recuperacao de password

```http
POST /api/forgot-password
Content-Type: application/json
Accept: application/json
```

Body:

```json
{
  "email": "user@example.com"
}
```

Resposta:

```json
{
  "message": "Se existir uma conta com esse email, enviamos um link para recuperar a password."
}
```

## Rotas autenticadas com `auth:sanctum`

### 1. Drivers

```http
GET /api/v1/drivers
```

Lista motoristas.

Query params suportados:

- `driver_id`: devolve apenas um motorista especifico
- `q`: pesquisa por nome, email ou codigo
- `state_id`: filtra por estado
- `created_from`: data inicial de criacao (`YYYY-MM-DD`)
- `created_to`: data final de criacao (`YYYY-MM-DD`)

### 2. Sales by week

```http
GET /api/v1/sales-by-week/{date}
```

Exemplo:

```http
GET /api/v1/sales-by-week/18-03-2026
```

Devolve vendas agregadas por semana.

### 3. Vehicle profitabilities

```http
GET /api/v1/vehicle-profitabilities
```

Devolve rentabilidade de viaturas.

Query params suportados:

- `tvde_week_id`
- `date` (`d-m-Y`)
- `vehicle_id`

### 4. Vehicle usages

```http
GET /api/v1/vehicle-usages
```

Consulta utilizacoes de viaturas.

Query params suportados:

- `driver_id`: filtra por id do motorista
- `driver`: pesquisa por nome do motorista
- `license_plate`: pesquisa por matricula
- `start_date_from`: inicio minimo da utilizacao (`YYYY-MM-DD`)
- `start_date_to`: inicio maximo da utilizacao (`YYYY-MM-DD`)
- `end_date_from`: fim minimo da utilizacao (`YYYY-MM-DD`)
- `end_date_to`: fim maximo da utilizacao (`YYYY-MM-DD`)
- `active_on`: devolve apenas utilizacoes ativas numa data (`YYYY-MM-DD`)
- `per_page`: paginacao, default `25`, max `100`

Exemplo:

```http
GET /api/v1/vehicle-usages?driver_id=3&driver=adelmo&license_plate=62-XQ-20&start_date_from=2026-03-01&start_date_to=2026-03-31&per_page=25
```

Resposta tipica:

```json
{
  "filters": {
    "driver_id": 3,
    "driver": "adelmo",
    "license_plate": "62-XQ-20",
    "start_date_from": "2026-03-01",
    "start_date_to": "2026-03-31",
    "end_date_from": null,
    "end_date_to": null,
    "active_on": null,
    "per_page": 25
  },
  "viewer": {
    "roles": ["Admin"],
    "is_admin": true,
    "is_manager": false,
    "is_driver": false
  },
  "items": [
    {
      "id": 1,
      "start_date": "2026-03-01 10:00:00",
      "end_date": "2026-03-10 09:00:00",
      "usage_exception": "usage",
      "usage_exception_label": "Utilizacao",
      "driver": {
        "id": 3,
        "name": "Adelmo Filho",
        "company": {
          "id": 1,
          "name": "Movvi"
        }
      },
      "vehicle": {
        "id": 12,
        "license_plate": "62-XQ-20",
        "brand": "Renault",
        "model": "Clio",
        "company": {
          "id": 1,
          "name": "Movvi"
        }
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 25,
    "total": 61
  }
}
```

Regras:

- `Admin` e `Gestor`: veem todas as utilizacoes
- `Driver`: ve apenas as suas

### 5. Weeks

```http
GET /api/v1/weeks
```

Lista semanas TVDE.

Query params suportados:

- `date_from`: semana com `start_date` igual ou superior (`YYYY-MM-DD`)
- `date_to`: semana com `start_date` igual ou inferior (`YYYY-MM-DD`)

### 6. Company reports weekly

```http
GET /api/v1/company-reports/weekly
```

Leitura semanal consolidada do company report.

Query params suportados:

- `date` (`d-m-Y`)
- `company_id`

## Conta Azul `auth:sanctum`

Estas rotas exigem utilizador com role `Admin` ou `Gestor`.

### 7. Status da ligacao

```http
GET /api/v1/conta-azul/status
```

### 8. Accounts

```http
GET /api/v1/conta-azul/accounts
```

### 9. Balances

```http
GET /api/v1/conta-azul/balances
```

### 10. Categories

```http
GET /api/v1/conta-azul/categories
```

### 11. Receivables

```http
GET /api/v1/conta-azul/receivables
```

### 12. Payables

```http
GET /api/v1/conta-azul/payables
```

### 13. Profit and loss

```http
GET /api/v1/conta-azul/manager/profit-loss
```

### 14. Movements

```http
GET /api/v1/conta-azul/manager/movements
```

### 15. Expenses

```http
GET /api/v1/conta-azul/manager/expenses
```

Notas:

- estas rotas aceitam `company_id` opcional
- sempre que oportuno, pode usar `data_vencimento_de` e `data_vencimento_ate` (`YYYY-MM-DD`)
- quando nao sao passadas datas, o backend usa por defeito o mes atual para os endpoints financeiros do gestor

## Mobile authenticated

Tambem existem rotas especificas da app reservada:

- `GET /api/v1/mobile/me`
- `GET /api/v1/mobile/dashboard`
- `GET /api/v1/mobile/inspections`
- `GET /api/v1/mobile/inspections/create-options`
- `POST /api/v1/mobile/inspections`
- `DELETE /api/v1/mobile/inspections/{inspection}`
- `GET /api/v1/mobile/inspections/{inspection}`
- `POST /api/v1/mobile/inspections/{inspection}/step`
- `POST /api/v1/mobile/inspections/{inspection}/back-step`
- `POST /api/v1/mobile/inspections/{inspection}/damages/{damage}/resolve`
- `POST /api/v1/mobile/inspections/{inspection}/close`
- `GET /api/v1/mobile/driver/weeks`
- `GET /api/v1/mobile/driver/receipts`
- `POST /api/v1/mobile/driver/receipts`
- `POST /api/v1/mobile/driver/expense-receipts`
- `POST /api/v1/mobile/driver/reimbursements`
- `GET /api/v1/mobile/driver/documents`

## Observacoes

- A API responde em JSON.
- Em erro de autenticacao, o backend pode devolver `401`.
- Em erro funcional ou validacao, e comum devolver `422`.
- A integracao externa deve enviar sempre `Accept: application/json`.
