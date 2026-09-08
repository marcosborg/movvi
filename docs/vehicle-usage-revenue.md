# Utilização e faturação das viaturas

O relatório `/admin/vehicle-usage` e o PDF partilham filtros e cálculos.

- Por defeito, inclui apenas viaturas atualmente não suspensas, não vendidas e com utilização iniciada. O filtro de histórico permite incluir viaturas inativas; viaturas nunca utilizadas continuam excluídas.
- A primeira utilização é o menor início de um registo não eliminado de `vehicle_usages`, de tipo `usage` (ou sem exceção e com motorista), com fim posterior ao início ou aberto. Não se usa `created_at` nem a data de aquisição. A data/hora inicial aparece junto da matrícula.
- O denominador é o tempo desde essa primeira utilização, limitado ao período selecionado e à venda, até ao fim do dia selecionado (nunca depois do dia atual). Mantém-se a convenção de dias de 24 horas do relatório, incluindo frações de dia e dias parados.
- Dias sem uso = dias decorridos − utilização. Incluem manutenção, sinistros, uso pessoal e tempo sem atribuição; o detalhe discrimina estas categorias. As sobreposições conservam a regra anterior: prevalece o registo iniciado mais recentemente.
- A faturação reutiliza `VehicleProfitabilityService::makeWeek`: cedência, comissão e ajustes que esse serviço já atribui à viatura. Não é uma soma de recebimentos nem uma nova definição contabilística. Falta de relatórios validados é assinalada com `*`.
- A receita de cada semana TVDE é distribuída proporcionalmente pelo tempo operacional dessa semana, desde a primeira utilização até ao fim da semana, limitado à venda e ao dia atual. Só depois é recortada pelo filtro e agrupada por ano, mês ou semana ISO. Assim, intervalos adjacentes conservam o total semanal; valores mensais e cortes parciais são identificados como estimados. Não há arredondamento intermédio, apenas na apresentação.
- Média diária = receita incluída / dias decorridos (incluindo dias parados). A média do grupo usa a soma dos dias de todas as viaturas, não a média simples das médias individuais.
- Os valores financeiros só são consultados/apresentados a quem tem a permissão `vehicle_profitability_access`. A utilização mantém a permissão anterior.

## Validação

`VehicleUsageReportTest` cobre primeira utilização, dias parados, frações de dia, sobreposições, venda, datas futuras, mudanças de mês/ano/semana ISO, receita negativa e conservação dos totais entre intervalos. `VehicleUsageReportHttpTest` cobre PDF, seleção/empresa, filtro de ativas, primeira utilização anterior ao período e permissão financeira. Os testes usam SQLite em memória ou modelos em memória.

Não são necessárias migrações, alterações de dados, dependências ou compilação de assets.
