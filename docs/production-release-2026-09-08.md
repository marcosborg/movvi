# Atualização de produção de 8 de setembro de 2026

## Código

- Validação de relatórios da empresa: pedidos JSON com contagem esperada e gravação transacional do lote.
- Utilização das viaturas: período limitado ao dia atual, tratamento de sobreposições, seleção de matrículas ou grupos por marca/modelo e PDF de uma ou das três abas.
- Visualização local de imagens: URLs públicos de produção e leitura autenticada dos anexos privados. O endpoint independente é instalado a partir de `deploy/production-image.php`; ver `production-image-reader.md`. As chaves permanecem fora do Git.

Os controladores e vistas dos dois relatórios foram publicados via cPanel. O endpoint de imagens foi instalado separadamente; o suporte de visualização localhost fica no código da aplicação.

## Configuração do servidor

No MultiPHP INI Editor do cPanel, domínio movvi.pt (aplicação também acessível por movvi.com.pt), foi atualizada a configuração `/home3/movvi/public_html/php.ini`:

| Diretiva | Valor |
| --- | --- |
| max_input_vars | 100000 |
| max_execution_time | 300 |
| max_input_time | 300 |
| memory_limit | 1024M |
| post_max_size | 516M (mantido) |
| upload_max_filesize | 512M (mantido) |

Estas definições são configuração do alojamento; um pull Git não as aplica.

## Correção de dados e suporte

O registo incompleto da semana 51 associado à CJ-17-XC foi recuperado em produção e sandbox após confronto com o relatório calculado: cedência de 240,00 EUR e total do motorista de 334,19 EUR. Foi guardada uma cópia dos registos anteriores antes da atualização transacional. Esta intervenção já foi executada e não deve ser repetida como migração. Não foi feita comunicação à Conta Azul.

Foram enviadas respostas nos tickets 1 a 4 através da aplicação de produção, sem erro reportado pelo envio SMTP. Os tickets permaneceram abertos para confirmação e encerramento pelo cliente.

## Verificação

20 testes passaram para imagens locais, proxy de anexos, validação em lote, cálculo da utilização e exportação PDF. O PDF foi também gerado e aberto em produção; foram revistas amostras com três viaturas e com a frota completa.
