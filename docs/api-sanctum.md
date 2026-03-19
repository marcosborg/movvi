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

### 4. Vehicle usages

```http
GET /api/v1/vehicle-usages
```

Consulta utilizacoes de viaturas.

Query params suportados:

- `driver`: pesquisa por nome do motorista
- `license_plate`: pesquisa por matricula
- `per_page`: paginação, default `25`, max `100`

Exemplo:

```http
GET /api/v1/vehicle-usages?driver=adelmo&license_plate=62-XQ-20&per_page=25
```

Resposta tipica:

```json
{
  "filters": {
    "driver": "adelmo",
    "license_plate": "62-XQ-20",
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

## Conta Azul `auth:sanctum`

Estas rotas exigem utilizador com role `Admin` ou `Gestor`.

### 5. Status da ligacao

```http
GET /api/v1/conta-azul/status
```

### 6. Accounts

```http
GET /api/v1/conta-azul/accounts
```

### 7. Balances

```http
GET /api/v1/conta-azul/balances
```

### 8. Categories

```http
GET /api/v1/conta-azul/categories
```

### 9. Receivables

```http
GET /api/v1/conta-azul/receivables
```

### 10. Payables

```http
GET /api/v1/conta-azul/payables
```

### 11. Profit and loss

```http
GET /api/v1/conta-azul/manager/profit-loss
```

### 12. Movements

```http
GET /api/v1/conta-azul/manager/movements
```

### 13. Expenses

```http
GET /api/v1/conta-azul/manager/expenses
```

Notas:

- estas rotas aceitam `company_id` opcional
- quando nao sao passadas datas, o backend usa por defeito o mes atual para os endpoints financeiros do gestor

## Mobile authenticated

Tambem existem rotas especificas da app reservada:

- `GET /api/v1/mobile/me`
- `GET /api/v1/mobile/dashboard`
- `GET /api/v1/mobile/inspections`
- `GET /api/v1/mobile/inspections/create-options`
- `POST /api/v1/mobile/inspections`
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
