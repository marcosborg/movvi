# Integração de manutenção Movvi

A API atual de produção usa a base [movvi.com.pt/api](https://movvi.com.pt/api).
As rotas de consulta existentes continuam documentadas em
[Autenticação e API](api-sanctum.md) e [Despesas de viaturas](vehicle-expenses-api.md).
A integração abaixo está disponível exclusivamente numa instância isolada com
`APP_ENV=maintenance-sandbox`. Para a demonstração local, a base é
`http://127.0.0.1:8013/api/v1/integrations/maintenance`. Para o Adelmo testar
remotamente, publicar uma instância dedicada em HTTPS, com base fictícia, e
preencher `maintenance_api_base` e `maintenance_api_token` na
[coleção Postman](movvi-sanctum.postman_collection.json).

## Preparar uma instância isolada

Criar uma base SQLite nova. A construção abaixo usa apenas a estrutura e os
dados artificiais em `database/fixtures/maintenance-sandbox.sql`, com a
migração real para os campos da integração. Não usar uma cópia da produção.

```sh
maintenance_dir=$(mktemp -d)
sqlite3 "$maintenance_dir/sandbox.sqlite" < database/fixtures/maintenance-sandbox.sql
export APP_ENV=maintenance-sandbox
export APP_CONFIG_CACHE="$maintenance_dir/config.php"
export APP_TIMEZONE=Europe/Lisbon
export DB_CONNECTION=sqlite DB_DATABASE="$maintenance_dir/sandbox.sqlite"
export DATABASE_URL=''
export CACHE_DRIVER=array SESSION_DRIVER=array
export MAINTENANCE_INTEGRATION_ENABLED=true MAINTENANCE_INTEGRATION_COMPANY_ID=1
maintenance_token=$(openssl rand -hex 32)
export MAINTENANCE_INTEGRATION_TOKEN_HASH="$(printf '%s' "$maintenance_token" | openssl dgst -sha256 -r | cut -d ' ' -f 1)"
export MAINTENANCE_INTEGRATION_EXPIRES_AT="$(php -r 'echo gmdate("Y-m-d\TH:i:s+00:00", time() + 90 * 86400);')"
php artisan migrate --path=database/migrations/2026_11_25_100000_add_maintenance_integration_records.php --force
php artisan serve --host=127.0.0.1 --port=8013
```

Definir a expiração para 90 dias após a emissão. Guardar o token num gestor de
segredos e entregar por partilha segura. A aplicação guarda apenas o hash na
configuração. Para revogar, desativar a integração ou remover o hash. Para
rodar, substituir o hash e a expiração e recarregar a configuração da instância.
`MAINTENANCE_INTEGRATION_ALLOWED_IP` pode restringir o acesso ao IP efetivo do
painel quando o proxy de confiança estiver configurado. O limite em Laravel é
60 pedidos por minuto por IP para acessos sem sessão. Num ambiente partilhado,
usar uma cache persistente. Perante `429`, aguardar o `Retry-After`.
As rotas respondem `404` fora do ambiente de teste, mesmo com a integração
ativada por engano. O token dá acesso apenas a estas operações e à empresa
configurada.

## Operações

Enviar `Authorization: Bearer <token>` e `Accept: application/json`.
Para escritas JSON, enviar também `Content-Type: application/json`.

| Método e rota relativa | Comportamento |
| --- | --- |
| `GET /vehicles` | Viaturas ativas da empresa, com ID e matrícula |
| `GET /expense-categories` | Identificadores e nomes das categorias disponíveis |
| `GET /vehicle-usages` | Cedências e exceções, incluindo anulações |
| `GET /vehicle-expenses` | Despesas e anulações |
| `POST /maintenance-periods` | Abre manutenção; envia referência externa, ID ou matrícula e `started_at` |
| `PATCH /maintenance-periods/{reference}` | Encerra o período com `ended_at`; repetir a mesma data é seguro |
| `DELETE /maintenance-periods/{reference}` | Anula um período criado pela integração |
| `POST /vehicle-expenses` | Regista despesa; envia referência externa, ID ou matrícula e os campos abaixo |
| `PUT /vehicle-expenses/{reference}` | Substitui os campos da despesa, mantendo a viatura e a referência |
| `DELETE /vehicle-expenses/{reference}` | Anula uma despesa criada pela integração |

Identificar a viatura através de `vehicle_id` **ou** `license_plate`.
O sandbox disponibiliza a viatura livre **10**, a viatura com cedência ativa
**11** e a viatura **20** de outra empresa para validar o isolamento.
Os campos de horas usam ISO 8601 com offset, por exemplo
`2026-11-25T10:15:00+00:00`. As respostas indicam o fuso da base.
A manutenção nunca fecha uma cedência. Se existir sobreposição ou
um motorista atribuído, corrigir primeiro o estado no Movvi.

Para despesas, enviar `category` (ID devolvido na consulta), `net_amount`
(EUR sem IVA), `vat_rate` (percentagem), `date` (AAAA-MM-DD),
`document_number`, `workshop` e `description`. O valor sem IVA alimenta o
relatório de custos existente, e a oficina é guardada como fornecedor.

## Reenvios e sincronização

- A referência externa tem até 80 caracteres minúsculos, algarismos, ponto,
  hífen ou sublinhado. Guardar a referência no painel. Um POST novo devolve
  `201`. Repeti-lo com o mesmo conteúdo devolve `200` e o mesmo ID,
  incluindo o estado atual caso o registo tenha sido alterado ou anulado.
  Usar PUT/PATCH para corrigir, sem reutilizar a referência com outro POST.
- Nas listas paginadas, usar `per_page` (máximo 100) e continuar a partir de
  `meta.next_after_id` até este ficar nulo. Para sincronizar cedências e
  despesas use `updated_since` inclusivo e volte a `after_id=0` em cada
  ciclo. Guardar o início da consulta quando todas as páginas concluírem,
  com uma sobreposição entre ciclos. Reconciliar anulações via
  `deleted_at` e usar o ID para atualizar o mesmo registo.
- `401` credencial inválida/expirada; `403` IP não autorizado; `404`
  recurso fora do âmbito ou ambiente indisponível; `409` sobreposição,
  referência em conflito ou alteração incompatível; `422` dados inválidos;
  `204` anulação executada ou já aplicada.
- O painel consulta alterações nesta entrega. O webhook e a ativação de
  escrita em produção ficam para depois da validação conjunta.
