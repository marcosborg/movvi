# Database copy / install package

Ferramentas disponiveis para gerar um snapshot limitado ou clonar a base de dados completa.

- **Rota HTTP**: `GET /db-copy/latest-500?token=SEU_TOKEN&limit=500`
  - Protegida por token (`DB_COPY_TOKEN` no `.env`).
  - Copia as ultimas `limit` linhas de cada tabela da ligacao principal para a base definida em `DB_COPY_DATABASE` (com `DB_COPY_HOST/PORT/USERNAME/PASSWORD/CHARSET/COLLATION` opcionais).
- **Comando Artisan**: `php artisan db:make-install-package --source=mysql_production --target=mysql_sandbox`
  - Copia a base de dados completa da ligacao de origem (tipicamente `mysql_production`) para a ligacao de destino (`mysql_sandbox`) em lote, usando `mysqldump` + `mysql`.
  - A base destino e recriada antes da importacao para acelerar a clonagem integral e evitar copia tabela-a-tabela.
  - Opcoes:
    - `--source=`: nome da ligacao de origem (default `mysql_production`).
    - `--target=`: ligacao destino (default `mysql_sandbox`).
    - `--target-database=`: substitui o nome da base de dados configurado na ligacao destino.
    - `--mode=dump|legacy`: `dump` por defeito; `legacy` usa o metodo antigo tabela-a-tabela.
    - `--chunk=`: numero de linhas por lote de insert, apenas no modo `legacy`.
    - `--mysqldump-bin=` / `--mysql-bin=`: permitem indicar binarios custom se nao estiverem no `PATH`.

No `.env`, use `DB_MODE=sandbox|production` para alternar rapidamente entre a base local (`DB_SANDBOX_*`) e a externa (`DB_PRODUCTION_*`).
