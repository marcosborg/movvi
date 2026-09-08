# Leitura privada de imagens de produção

O browser pede o anexo à rota local, que mantém a autorização do ticket.
Apenas em pedidos GET em loopback, o servidor local consulta produção por HTTPS,
com uma chave dedicada no header X-Movvi-Image-Key. Não há escrita remota nem
cópia permanente local. Não se reutilizam cookies, chaves da aplicação ou cPanel.

## Instalação pelo cPanel

- `deploy/production-image.php` → `/home3/movvi/public_html/public/production-image.php`.
- Configuração JSON → `/home3/movvi/.movvi-image-reader.json`, fora da raiz web:
  `{"token_hash":"SHA256_DA_CHAVE","expires_at":UNIX_TIMESTAMP}`.
- Gerar chave aleatória de 32 bytes em hexadecimal; guardar apenas no `.env` local
  como `PRODUCTION_IMAGE_READ_KEY`. Guardar em produção apenas o hash SHA-256.
- Validade inicial: 90 dias. Para revogar, remover a configuração no servidor;
  para renovar, substituir a chave e o hash com nova data de validade.
- Executar `php artisan config:clear` localmente.

O endpoint aceita apenas GET e caminhos `support-tickets/<id>/<uuid>.jpg|jpeg|png|webp`.
Verifica a chave, validade, caminho real dentro da pasta e MIME de imagem raster,
com limite de 8 MB. Falha fechado sem configuração válida. A chave dá acesso de
leitura aos anexos de todos os tickets e deve ser tratada como segredo; não concede
acesso à base de dados, restantes ficheiros ou escrita. A rota local continua a
verificar as permissões do utilizador antes da chamada remota.

Validação após publicar: pedido sem chave → 403; pedido autorizado → imagem;
confirmar a miniatura do ticket no browser local. Testes locais usam HTTP simulado,
sem aceder a produção.

Neste PC, `PRODUCTION_IMAGE_READ_IP=162.241.85.34` contorna uma falha do resolver
DNS do cURL do PHP. A ligação mantém o hostname e a validação do certificado TLS.
Atualizar ou remover esta opção se o IP do alojamento mudar.
