# E-mail

O envio de e-mail é centralizado no `App\Services\EmailService`, um cliente SMTP nativo (via sockets), sem dependências externas. Nenhum controller implementa SMTP diretamente.

## Configuração

Em **Administração → Configurações → E-mail** (armazenado na tabela `settings`, grupo `email`):

| Chave | Descrição |
|-------|-----------|
| `mail_driver` | `smtp` (padrão) ou `mail`. |
| `smtp_host` | Servidor SMTP. |
| `smtp_port` | Porta (587 para TLS, 465 para SSL). |
| `smtp_username` | Usuário de autenticação. |
| `smtp_password` | Senha (armazenada como valor secreto). |
| `smtp_encryption` | `tls`, `ssl` ou `none`. |
| `mail_from_name` | Nome do remetente. |
| `mail_from_email` | E-mail do remetente. |
| `mail_reply_to` | Endereço de resposta (opcional). |

> O campo de senha SMTP é tratado como **secreto**: deixá-lo em branco ao salvar **mantém** o valor atual.

## Teste

Na aba de e-mail há o botão **"Enviar e-mail de teste"**. Salve as configurações antes de testar; o teste usa os valores persistidos. Em caso de falha, a mensagem exibe o erro reportado pelo servidor SMTP (útil para diagnóstico).

## Uso no código

```php
use App\Services\EmailService;

$service = new EmailService();
$ok = $service->send($to, $nome, $assunto, $htmlBody);
if (!$ok) {
    // $service->lastError() contém o motivo
}
```

## Suporte a transporte

- **TLS (STARTTLS)** — porta 587.
- **SSL implícito** — porta 465.
- **Sem criptografia** — apenas para redes internas/testes.
- Fallback para a função nativa `mail()` quando `mail_driver = mail`.

## Onde é usado

- Recuperação de senha do painel.
- Notificação de novas mensagens do formulário de contato (para o `contact_email` configurado).
