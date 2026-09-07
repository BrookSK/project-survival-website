<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\ContactMessage;
use App\Services\EmailService;
use App\Services\SeoService;
use App\Validators\Validator;

/**
 * Formulário de contato público.
 *
 * Valida entrada, protege contra CSRF e spam (honeypot + tempo mínimo),
 * armazena a mensagem e notifica o e-mail configurado.
 */
class ContactController extends Controller
{
    public function index(Request $request): void
    {
        // Timestamp para checagem de tempo mínimo de preenchimento (anti-bot)
        Session::set('__contact_ts', time());

        $this->viewSite('site.contact', [
            'seo'    => SeoService::build(['title' => 'Contato']),
            'errors' => errors(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->verifyCsrf($request);

        // Honeypot: campo oculto que humanos não preenchem
        if (trim((string) $request->post('website', '')) !== '') {
            Session::flash('success', 'Mensagem enviada com sucesso.');
            $this->redirect('/contato');
            return;
        }

        // Tempo mínimo de preenchimento (bots costumam submeter instantaneamente)
        $ts = (int) Session::get('__contact_ts', 0);
        if ($ts > 0 && (time() - $ts) < 3) {
            Session::flash('error', 'Envio muito rápido. Tente novamente.');
            $this->redirect('/contato');
            return;
        }

        // Rate limiting por IP: máximo de 5 mensagens a cada 10 minutos.
        $rlKey = 'contact:' . $request->ip();
        if (\App\Services\RateLimiter::tooManyAttempts($rlKey, 5)) {
            $mins = ceil(\App\Services\RateLimiter::availableIn($rlKey) / 60);
            \App\Core\Logger::warning('Rate limit de contato atingido: ' . $request->ip());
            Session::flash('error', "Muitas mensagens enviadas. Tente novamente em {$mins} minuto(s).");
            $this->redirect('/contato');
            return;
        }
        \App\Services\RateLimiter::hit($rlKey, 600);

        $data = $request->only(['name', 'email', 'subject', 'message']);
        $validator = new Validator($data, [
            'name'    => 'required|string|min:2|max:150',
            'email'   => 'required|email|max:190',
            'subject' => 'required|string|min:3|max:200',
            'message' => 'required|string|min:10',
        ], [
            'name'    => 'nome',
            'email'   => 'e-mail',
            'subject' => 'assunto',
            'message' => 'mensagem',
        ]);

        if ($validator->fails()) {
            $this->redirectWithErrors($validator->errors(), $data, '/contato');
            return;
        }

        // Armazena a mensagem
        $messageModel = new ContactMessage();
        $msgId = $messageModel->create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'subject'    => $data['subject'],
            'message'    => $data['message'],
            'status'     => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Notificação administrativa (aparece no painel)
        \App\Services\NotificationService::push(
            'message',
            'Nova mensagem de contato',
            $data['name'] . ': ' . $data['subject'],
            '/admin/mensagens/' . $msgId
        );

        // Notifica o administrador (falha de e-mail não impede o registro)
        $this->notify($data);

        Session::remove('__contact_ts');
        Session::flash('success', 'Mensagem enviada com sucesso! Responderemos em breve.');
        $this->redirect('/contato');
    }

    private function notify(array $data): void
    {
        $to = setting('contact_email', '');
        if (!$to) {
            return;
        }

        $siteName = setting('site_name', 'Site');

        try {
            $service = new EmailService();
            // Usa o template administrável 'contact_received'; fallback simples se ausente.
            $sent = $service->sendTemplate('contact_received', $to, $siteName, [
                'name'    => $data['name'],
                'email'   => $data['email'],
                'subject' => $data['subject'],
                'message' => $data['message'],
            ]);

            if (!$sent) {
                $html = '<h2>Nova mensagem de contato</h2>'
                      . '<p><strong>Nome:</strong> ' . e($data['name']) . '</p>'
                      . '<p><strong>E-mail:</strong> ' . e($data['email']) . '</p>'
                      . '<p><strong>Assunto:</strong> ' . e($data['subject']) . '</p>'
                      . '<p><strong>Mensagem:</strong></p>'
                      . '<p style="white-space:pre-wrap;">' . e($data['message']) . '</p>';
                $service->send($to, $siteName, "[Contato] {$data['subject']}", $service->wrap($html));
            }
        } catch (\Throwable $e) {
            \App\Core\Logger::error('Falha ao notificar contato: ' . $e->getMessage());
        }
    }
}
