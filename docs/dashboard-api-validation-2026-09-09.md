# Validação da API do dashboard — 09/09/2026

Comparação realizada no sandbox copiado de produção em 09/09/2026, com o código
local e a migration local `pays_fuel` aplicada. Empresa: Adelmo Top, Uni. Lda.
Consultas executadas dentro de uma transação MySQL `READ ONLY`, terminada com
rollback. Não foram consultadas nem alteradas bases de produção nesta validação.

Os montantes abaixo estão arredondados para apresentação. A API conserva a
precisão numérica dos cálculos existentes.

| Semana ISO 2026 | ID TVDE | Início | Nova API RecOp | API weekly | Fórmula do painel | Receita viaturas | RecOp − viaturas |
|---|---:|---|---:|---:|---:|---:|---:|
| 34 | 49 | 2026-08-17 | 17.260,24 € | 17.260,24 € | 17.260,24 € | 16.567,72 € | 692,52 € |
| 35 | 50 | 2026-08-24 | 16.863,65 € | 16.863,65 € | 16.863,65 € | 16.704,45 € | 159,20 € |
| 36 | 51 | 2026-08-31 | 18.195,20 € | 18.195,20 € | 18.195,20 € | 18.257,30 € | -62,10 € |

A nova rota e o campo adicionado ao relatório semanal coincidem com a fórmula
usada pelo painel nas três semanas. A comparação usou o controller real e o
serviço de rentabilidade real, com seleção explícita de empresa e semana.
Os testes HTTP automatizados verificam adicionalmente rotas, autenticação,
permissões, filtros e serialização.

As semanas 35 e 36 **não** têm a mesma receita operacional neste snapshot.
A imagem do cliente que atribui 18.195,20 € a ambas não corresponde a esta leitura.
A receita da semana 34 também difere dos 17.257,06 € citados nas imagens; não foi
alterado nenhum dado para fazer os números coincidir. Estas observações não
identificam, por si, a origem histórica da divergência.

As diferenças entre receita da empresa e das viaturas não foram automaticamente
classificadas como ajustes globais. São cálculos com regras próprias de âmbito,
alocação e validação e exigem reconciliação detalhada para explicar cada diferença.

## Verificação automatizada

Resultado: **38 testes, 143 asserções, todos passaram**.

Suíte relevante: DashboardApiTest, VehicleProfitabilityServiceTest,
TemporalVehicleRevenueAllocatorTest, CompanyReportBatchValidationTest,
VehicleUsageReportHttpTest, VehicleUsageReportTest e DriverFuelBillingTest.

Executada numa base SQLite temporária, sem dados reais, com estrutura derivada
em leitura do sandbox. Os testes que definem a própria estrutura usam SQLite em
memória. Esta validação não substitui testes de concorrência ou comportamento
específico de MySQL; os cálculos reais acima foram verificados no MySQL sandbox.
