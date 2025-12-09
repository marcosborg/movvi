# Database copy / install package

Ferramentas disponiveis para gerar um snapshot limitado da base de dados.

- **Rota HTTP**: `GET /db-copy/latest-500?token=SEU_TOKEN&limit=500`
  - Protegida por token (`DB_COPY_TOKEN` no `.env`).
  - Copia as ultimas `limit` linhas de cada tabela da ligacao principal para a base definida em `DB_COPY_DATABASE` (com `DB_COPY_HOST/PORT/USERNAME/PASSWORD/CHARSET/COLLATION` opcionais).
- **Comando Artisan**: `php artisan db:make-install-package --limit=500 --path=storage/app/install-package.sql`
  - Gera um `install-package.sql` com DROP/CREATE e INSERT das ultimas linhas de cada tabela.
  - Opcoes:
    - `--limit=`: numero de linhas por tabela (default 500).
    - `--connection=`: nome da ligacao a usar (default configurado no projeto).
    - `--path=`: caminho do ficheiro .sql de saida.

Para restaurar o pacote, importe o ficheiro gerado: `mysql -uUSER -pPASSWORD DB_DEST < storage/app/install-package.sql`.
