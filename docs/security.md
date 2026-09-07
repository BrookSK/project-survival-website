# Segurança

Resumo dos mecanismos de segurança implementados.

## Autenticação

- Senhas com `password_hash()` (bcrypt, custo 12) e verificação com `password_verify()`.
- Re-hash automático quando o custo/algoritmo muda (`password_needs_rehash`).
- Nenhuma senha em texto puro é armazenada ou registrada.
- Recuperação de senha por token: armazena-se apenas o **hash** do token, com expiração; a solicitação responde de forma genérica para evitar enumeração de contas.

## Proteção contra brute force

- Tentativas de login (sucesso/falha) são registradas em `login_attempts`.
- Após um número configurável de falhas (`login_max_attempts`), o login é bloqueado por `login_lockout_minutes` para o e-mail/IP.

## CSRF

- Token por sessão gerado por `Csrf`.
- Todas as requisições de escrita (POST/PUT/PATCH/DELETE) validam o token (`Controller::verifyCsrf`), com comparação de tempo constante (`hash_equals`).
- Formulários incluem o token via `csrf_field()`; requisições AJAX podem enviar via header `X-CSRF-TOKEN`.

## Autorização (RBAC)

- Perfis (roles) e permissões granulares (ex.: `news.create`).
- `Controller::authorize('perm')` aborta com 403 quando o usuário não tem a permissão.
- A navegação do painel esconde itens sem permissão.
- `super-admin` tem acesso total (tratado no código).

## Banco de dados

- **PDO com prepared statements** em toda a camada de dados.
- Identificadores dinâmicos (colunas/tabelas em ORDER BY/WHERE) passam por **whitelist**.

## XSS e saída

- Toda saída dinâmica é escapada com `e()` (`htmlspecialchars`).
- Exceção intencional: conteúdo HTML de páginas/notícias/FAQ é renderizado sem escape, pois é **autorado exclusivamente por administradores autenticados** (comportamento típico de CMS). Trate o acesso de edição como confiável.

## Uploads

- Validação por **MIME real** (finfo + `getimagesize`), não apenas extensão.
- Tamanho máximo e verificação de que o arquivo é uma imagem válida.
- Nome de arquivo aleatório; o nome original nunca é preservado.
- `public/uploads/.htaccess` **desativa a execução de PHP/scripts** e a listagem de diretórios.

## Sessão

- Cookies `HttpOnly`, `SameSite=Lax`, `Secure` em produção.
- Regeneração periódica do ID de sessão e no login.
- Expiração por inatividade.

## Headers HTTP

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Strict-Transport-Security` em produção.
- Remoção de `X-Powered-By`.

## Erros e logs

- Em produção, `display_errors` é desligado; visitantes veem páginas de erro amigáveis.
- Detalhes (stack traces) são registrados apenas em `storage/logs/`.

## Configuração sensível

- Credenciais ficam apenas em `config/local.php`, fora do versionamento (`.gitignore`).
- Pastas `config/` e `storage/` têm `.htaccess` que bloqueia acesso direto.

## Auditoria

- Ações administrativas relevantes são registradas em `audit_logs` (usuário, ação, módulo, registro, IP, user agent, data/hora).

## Anti-spam (contato)

- Campo honeypot oculto + verificação de tempo mínimo de preenchimento, além de CSRF e validação de servidor.
