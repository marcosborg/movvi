# Despesas de viaturas para integração

`GET https://movvi.com.pt/api/v1/vehicle-expenses?company_id=1`

Usar a autenticação existente da plataforma (`Authorization: Bearer <token>` e `Accept: application/json`). Requer a permissão `vehicle_expense_access`. O utilizador tem de pertencer à empresa pedida (titular ou motorista associado); apenas Admin pode escolher outra empresa. Esta alteração não cria credenciais nem atribui permissões.

## Consulta

- `company_id`: obrigatório.
- `per_page`: 1 a 200, por omissão 100.
- `after_id`: começa em 0. Enquanto `meta.has_more` for verdadeiro, repetir com `meta.next_after_id`.
- `updated_since`: opcional, `YYYY-MM-DD HH:mm:ss`, inclusivo, no fuso indicado em `meta.timezone`. Abrange atualizações e eliminações.

Cada item inclui `id`, `source_id`, `company_id`, `vehicle_id`, `license_plate`, `expense_type` (grupo), `date`, `description` (texto), `value`, `vat`, `currency`, `updated_at` e `deleted_at`. `value` e `vat` são os valores registados no formulário, sem conversão contabilística. A moeda é EUR. `date` é a data registada da despesa, não uma data de pagamento inferida. As despesas de viaturas arquivadas continuam incluídas; registos sem viatura associada não podem ser atribuídos a uma empresa e não são exportados.

## Evitar duplicados

Guardar uma associação única entre `source_id` (por exemplo `movvi:vehicle-expense:123`) e o identificador do lançamento criado no Conta Azul. Repetir uma consulta deve atualizar/reconciliar o mesmo lançamento, nunca criar outro apenas porque apareceu novamente na resposta. Se `deleted_at` estiver preenchido, reconciliar a anulação segundo as regras contabilísticas do destino; não criar uma despesa nova.

Na primeira sincronização, percorrer todas as páginas. Nas seguintes, recomeçar `after_id=0` com `updated_since` anterior ao início da última execução concluída, mantendo uma sobreposição para alterações concorrentes. Guardar o ponto de sincronização só após todas as páginas serem processadas com sucesso. A consulta é dinâmica, não uma fotografia transacional: fazer também reconciliações completas periódicas, sobretudo se mudar a empresa de uma viatura. Repetições são esperadas e devem ser idempotentes no consumidor.

## Modelo fornecido pelo Carlos

O ficheiro `Planilha_Modelo_ContaAzul.xls`, folha `Dados`, tem: Data de Competência, Data de Vencimento, Data de Pagamento, Valor, Categoria, Descrição, Cliente/Fornecedor, CNPJ/CPF Cliente/Fornecedor, Centro de Custo e Observações. O exemplo usa valores negativos para despesas.

A plataforma fornece data, valor, grupo, descrição e matrícula. Não regista neste módulo fornecedor, documento fiscal do fornecedor, vencimento ou pagamento. Não preencher esses campos com dados inventados. O Carlos deve confirmar o tratamento do IVA e dos sinais/valores no destino, mapear grupo para categoria e, se pretendido, matrícula para centro de custo. Esta API fornece a origem; o conector e o controlo de duplicados no Conta Azul ficam a cargo da integração do Carlos.

## Grupos de despesas no site

Em Viaturas → Despesas da viatura, o campo Tipo de despesa aceita um grupo existente ou um novo nome. Ao guardar uma despesa, o nome fica disponível nas sugestões e no filtro da listagem. Os grupos antigos mantêm-se compatíveis, incluindo a apresentação de `Penus` como `Pneus`. Não é necessária migração da base de dados.
