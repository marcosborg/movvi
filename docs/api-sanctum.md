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

Estas rotas exigem utilizador com role `Admin`.

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
