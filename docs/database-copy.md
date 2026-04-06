# Database Copy

Este projeto tem dois caminhos diferentes para copiar dados entre bases:

1. `HTTP snapshot`
2. `Artisan clone`

O caminho recomendado para copiar a base de dados de `production` para `sandbox` e o comando Artisan com dump/import integral em streaming.

## Metodo recomendado

Usar:

```powershell
php artisan db:copy-production-to-sandbox
```

Este alias executa o mesmo fluxo de:

```powershell
php artisan db:make-install-package --source=mysql_production --target=mysql_sandbox --mode=dump --transport=pipe
```

### O que este comando faz

- le a base de origem configurada na ligacao `mysql_production`
- recria a base configurada na ligacao `mysql_sandbox`
- faz `mysqldump` da origem
- envia esse dump diretamente para `mysql` no destino
- evita a copia tabela a tabela
- evita guardar o dump completo em disco quando usado com `--transport=pipe`

### Porque e mais rapido

O modo `pipe` faz stream direto de `mysqldump` para `mysql`:

```text
mysqldump production | mysql sandbox
```

Isto reduz:

- round-trips entre PHP e a base
- iteracao tabela a tabela
- inserts em batches controlados pela aplicacao
- custo de I/O com ficheiro temporario

## Opcoes do comando

Comando base:

```powershell
php artisan db:make-install-package
```

Opcoes suportadas:

- `--source=`: ligacao de origem
  - default: `mysql_production`
- `--target=`: ligacao de destino
  - default: `mysql_sandbox`
- `--target-database=`: substitui o nome da base configurado na ligacao destino
- `--mode=dump|legacy`
  - `dump`: recomendado
  - `legacy`: copia antiga tabela a tabela
- `--transport=pipe|file`
  - `pipe`: recomendado; stream direto entre origem e destino
  - `file`: gera dump temporario em disco e importa depois
- `--chunk=`: apenas usado no modo `legacy`
- `--mysqldump-bin=`: permite indicar o binario `mysqldump`
- `--mysql-bin=`: permite indicar o binario `mysql`

## Quando usar cada modo

### `dump + pipe`

Usar quando:

- queres clonar a base completa
- estás a copiar `production` para `sandbox`
- queres o caminho mais rapido

Comando:

```powershell
php artisan db:copy-production-to-sandbox
```

### `dump + file`

Usar quando:

- o pipeline direto falha no ambiente
- precisas de inspecionar o dump temporario
- tens restricoes no shell ou nos binarios

Comando:

```powershell
php artisan db:make-install-package --source=mysql_production --target=mysql_sandbox --mode=dump --transport=file
```

### `legacy`

Usar apenas como fallback.

Este modo:

- recria tabela a tabela
- percorre os dados via PHP
- insere em chunks
- e significativamente mais lento

Comando:

```powershell
php artisan db:make-install-package --source=mysql_production --target=mysql_sandbox --mode=legacy
```

## Metodo HTTP antigo

Existe tambem a rota:

```text
GET /db-copy/latest-500?token=SEU_TOKEN&limit=500
```

### O que faz

- copia apenas as ultimas `N` linhas de cada tabela
- usa o metodo antigo tabela a tabela
- usa a configuracao `DB_COPY_*`

### Quando usar

So deve ser usada para:

- snapshots pequenos
- testes rapidos
- ambientes onde nao queres clonar a base completa

### Quando nao usar

Nao deve ser usada para:

- copia integral de `production` para `sandbox`
- refresh completo de ambiente
- cenarios onde o tempo de copia importa

## Configuracao

No `.env`, o projeto suporta alternar rapidamente entre bases com:

```env
DB_MODE=sandbox
```

ou

```env
DB_MODE=production
```

As ligacoes relevantes estao em `config/database.php`:

- `mysql_production`
- `mysql_sandbox`

## Resumo pratico

Se a intencao for copiar a base completa de `production` para `sandbox`, usar sempre:

```powershell
php artisan db:copy-production-to-sandbox
```

Se esse modo falhar por causa do ambiente, usar:

```powershell
php artisan db:make-install-package --source=mysql_production --target=mysql_sandbox --mode=dump --transport=file
```

Evitar a rota HTTP e evitar `--mode=legacy` para clonagem total.
