<?php

namespace App\Services;

use App\Core\Logger;

/**
 * Serviço centralizado de envio de e-mail.
 *
 * Implementa um cliente SMTP simples via sockets (sem dependências externas),
 * lendo toda a configuração da tabela `settings` (grupo 'email').
 * Suporta STARTTLS (tls) e SSL implícito (ssl), além do fallback para mail().
 *
 * Nenhum controller deve implementar SMTP diretamente; tudo passa por aqui.
 */
class EmailService
{
    private array $config;
    private ?string $lastError = null;

    public function __construct()
    {
        $this->config = SettingsService::group('email');
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Envia um e-mail HTML.
     *
     * @return bool Sucesso do envio.
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $driver = $this->config['mail_driver'] ?? 'smtp';

        if ($driver === 'mail') {
            return $this->sendWithMail($toEmail, $subject, $htmlBody);
        }

        return $this->sendWithSmtp($toEmail, $toName, $subject, $htmlBody);
    }

    /**
     * Envia usando um template administrável (tabela email_templates),
     * substituindo placeholders {{campo}} e envolvendo no layout responsivo.
     *
     * @param string $key    Chave do template (ex.: 'contact_received').
     * @param array  $vars   Valores para os placeholders.
     */
    public function sendTemplate(string $key, string $toEmail, string $toName, array $vars = []): bool
    {
        $template = (new \App\Models\EmailTemplate())->findByKey($key);

        // site_name sempre disponível
        $vars += ['site_name' => SettingsService::get('site_name', 'Site')];

        if (!$template || (int) $template['is_active'] !== 1) {
            // Sem template ativo: não envia (evita e-mail vazio), mas não é erro fatal.
            $this->lastError = "Template de e-mail '{$key}' inexistente ou inativo.";
            Logger::warning($this->lastError);
            return false;
        }

        $subject = $this->replacePlaceholders($template['subject'], $vars);
        $body = $this->replacePlaceholders($template['body'], $vars);

        return $this->send($toEmail, $toName, $subject, $this->wrap($body));
    }

    /**
     * Substitui placeholders {{chave}} (valores escapados para HTML, exceto
     * quando a chave termina em _raw, permitindo HTML pré-confiável como links).
     */
    private function replacePlaceholders(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($vars) {
            $key = $m[1];
            if (!array_key_exists($key, $vars)) {
                return '';
            }
            $value = (string) $vars[$key];
            // "link" e "*_raw" não são escapados (URLs/HTML controlado)
            if ($key === 'link' || substr($key, -4) === '_raw') {
                return $value;
            }
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }, $text);
    }

    /**
     * Envolve o conteúdo em um layout HTML responsivo com identidade do site.
     */
    public function wrap(string $content): string
    {
        $siteName = htmlspecialchars((string) SettingsService::get('site_name', 'Site'), ENT_QUOTES, 'UTF-8');
        $year = date('Y');
        $accent = '#4f7cff';

        return '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
            . '<body style="margin:0;padding:0;background:#0a0f1e;font-family:Arial,Helvetica,sans-serif;color:#e8ecf5;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0a0f1e;padding:24px 0;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#131a2e;border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.08);">'
            . '<tr><td style="background:linear-gradient(135deg,' . $accent . ',#a855f7);padding:20px 28px;color:#fff;font-size:20px;font-weight:bold;">' . $siteName . '</td></tr>'
            . '<tr><td style="padding:28px;color:#c3cadd;font-size:15px;line-height:1.7;">' . $content . '</td></tr>'
            . '<tr><td style="padding:18px 28px;border-top:1px solid rgba(255,255,255,.08);color:#6b7590;font-size:12px;">'
            . '&copy; ' . $year . ' ' . $siteName . '. Este é um e-mail automático.'
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /**
     * Envia usando a função nativa mail() (fallback simples).
     */
    private function sendWithMail(string $toEmail, string $subject, string $htmlBody): bool
    {
        $fromEmail = $this->config['mail_from_email'] ?? 'noreply@localhost';
        $fromName = $this->config['mail_from_name'] ?? 'Site';

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->encodeHeader($fromName) . " <{$fromEmail}>",
        ];
        if (!empty($this->config['mail_reply_to'])) {
            $headers[] = 'Reply-To: ' . $this->config['mail_reply_to'];
        }

        $ok = @mail($toEmail, $this->encodeHeader($subject), $htmlBody, implode("\r\n", $headers));
        if (!$ok) {
            $this->lastError = 'Falha ao enviar via mail().';
            Logger::error($this->lastError);
        }
        return $ok;
    }

    /**
     * Envia via SMTP com sockets.
     */
    private function sendWithSmtp(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $host = $this->config['smtp_host'] ?? '';
        $port = (int) ($this->config['smtp_port'] ?? 587);
        $user = $this->config['smtp_username'] ?? '';
        $pass = $this->config['smtp_password'] ?? '';
        $encryption = strtolower($this->config['smtp_encryption'] ?? 'tls');
        $fromEmail = $this->config['mail_from_email'] ?? 'noreply@localhost';
        $fromName = $this->config['mail_from_name'] ?? 'Site';
        $replyTo = $this->config['mail_reply_to'] ?? '';

        if ($host === '') {
            $this->lastError = 'SMTP host não configurado.';
            return false;
        }

        $transport = ($encryption === 'ssl') ? "ssl://{$host}" : $host;
        $timeout = 15;

        $socket = @stream_socket_client(
            "{$transport}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            $this->lastError = "Não foi possível conectar ao SMTP: {$errstr} ({$errno})";
            Logger::error($this->lastError);
            return false;
        }

        try {
            $this->expect($socket, 220);

            $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
            $this->command($socket, "EHLO {$hostname}", 250);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', 220);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Falha ao iniciar TLS.');
                }
                $this->command($socket, "EHLO {$hostname}", 250);
            }

            if ($user !== '') {
                $this->command($socket, 'AUTH LOGIN', 334);
                $this->command($socket, base64_encode($user), 334);
                $this->command($socket, base64_encode($pass), 235);
            }

            $this->command($socket, "MAIL FROM:<{$fromEmail}>", 250);
            $this->command($socket, "RCPT TO:<{$toEmail}>", [250, 251]);
            $this->command($socket, 'DATA', 354);

            $message = $this->buildMessage($fromEmail, $fromName, $toEmail, $toName, $subject, $htmlBody, $replyTo);
            $this->write($socket, $message . "\r\n.");
            $this->expect($socket, 250);

            $this->command($socket, 'QUIT', 221);
            fclose($socket);
            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Logger::error('Erro SMTP: ' . $e->getMessage());
            @fclose($socket);
            return false;
        }
    }

    private function buildMessage(
        string $fromEmail,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $replyTo
    ): string {
        $boundary = 'b' . bin2hex(random_bytes(8));
        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . $this->encodeHeader($fromName) . " <{$fromEmail}>";
        $headers[] = 'To: ' . $this->encodeHeader($toName) . " <{$toEmail}>";
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        if ($replyTo !== '') {
            $headers[] = "Reply-To: {$replyTo}";
        }
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = 'Content-Transfer-Encoding: base64';

        // Normaliza quebras e evita "dot stuffing" acidental no corpo base64
        $body = chunk_split(base64_encode($htmlBody));

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private function encodeHeader(string $text): string
    {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    private function command($socket, string $command, $expectedCode): void
    {
        $this->write($socket, $command);
        $this->expect($socket, $expectedCode);
    }

    private function write($socket, string $data): void
    {
        fwrite($socket, $data . "\r\n");
    }

    /**
     * Lê a resposta do servidor e valida o código esperado.
     *
     * @param int|int[] $expected
     */
    private function expect($socket, $expected): void
    {
        $expected = (array) $expected;
        $response = '';

        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Linhas de continuação começam com "CÓDIGO-"; a final tem "CÓDIGO "
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new \RuntimeException("Resposta SMTP inesperada: " . trim($response));
        }
    }

    /**
     * Testa a configuração enviando um e-mail para um destinatário.
     */
    public function sendTest(string $toEmail): bool
    {
        // Usa o template administrável; se ausente/inativo, faz fallback simples.
        if ($this->sendTemplate('smtp_test', $toEmail, 'Administrador')) {
            return true;
        }
        $siteName = SettingsService::get('site_name', 'Site');
        $html = "<p>Este é um e-mail de teste enviado por <strong>" . e($siteName) . "</strong>.</p>"
              . "<p>Se você recebeu esta mensagem, sua configuração SMTP está funcionando.</p>";
        return $this->send($toEmail, 'Administrador', "Teste de e-mail - {$siteName}", $this->wrap($html));
    }
}
