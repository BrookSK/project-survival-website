<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Validators\Validator;
use PDO;

/**
 * Instalador do sistema.
 *
 * Fluxo em página única com etapas:
 *  1. Verificação de requisitos (PHP, extensões, permissões).
 *  2. Formulário com dados do banco + administrador inicial.
 *  3. Execução: grava config/local.php, roda migrations + seeds,
 *     cria o administrador, associa ao perfil super-admin e grava o lock.
 *
 * Após concluído, o acesso é bloqueado enquanto config/installed.lock existir.
 */
class InstallController extends Controller
{
    /** Requisitos mínimos. */
    private const PHP_MIN = '8.0.0';
    private const REQUIRED_EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'json'];

    /**
     * Guarda: bloqueia o instalador se o sistema já estiver instalado.
     */
    private function ensureNotInstalled(): void
    {
        if (is_file(INSTALL_LOCK_FILE) && is_file(LOCAL_CONFIG_FILE)) {
            Response::html(View::render('install.locked', [], null));
            exit;
        }
    }

    public function index(Request $request): void
    {
        $this->ensureNotInstalled();

        Response::html(View::render('install.index', [
            'checks'      => $this->runChecks(),
            'canProceed'  => $this->allChecksPass(),
            'errors'      => errors(),
        ], null));
    }

    public function process(Request $request): void
    {
        $this->ensureNotInstalled();

        if (!Csrf_safe($request)) {
            Session::flash('error', 'Sessão inválida. Recarregue a página.');
            Response::redirect('/install');
            return;
        }

        if (!$this->allChecksPass()) {
            Session::flash('error', 'Corrija os requisitos pendentes antes de instalar.');
            Response::redirect('/install');
            return;
        }

        $data = $request->only([
            'db_host', 'db_port', 'db_name', 'db_user', 'db_pass',
            'admin_name', 'admin_email', 'admin_password', 'admin_password_confirmation',
            'site_name', 'site_url',
        ]);

        // Validação
        $validator = new Validator($data, [
            'db_host'        => 'required|string',
            'db_port'        => 'required|integer',
            'db_name'        => 'required|string',
            'db_user'        => 'required|string',
            'admin_name'     => 'required|string|min:2|max:150',
            'admin_email'    => 'required|email|max:190',
            'admin_password' => 'required|min:8|confirmed',
            'site_name'      => 'required|string|max:150',
        ], [
            'db_host' => 'host do banco', 'db_port' => 'porta', 'db_name' => 'nome do banco',
            'db_user' => 'usuário do banco', 'admin_name' => 'nome', 'admin_email' => 'e-mail',
            'admin_password' => 'senha', 'site_name' => 'nome do site',
        ]);

        if ($validator->fails()) {
            $this->redirectWithErrors($validator->errors(), $data, '/install');
            return;
        }

        // Testa a conexão ao banco
        try {
            $pdo = $this->connect($data);
        } catch (\Throwable $e) {
            Session::flash('error', 'Não foi possível conectar ao banco de dados: ' . $e->getMessage());
            Session::flashInput($data);
            Response::redirect('/install');
            return;
        }

        try {
            // 1. Grava config/local.php (credenciais + app_key)
            $this->writeLocalConfig($data);

            // 2. Executa migrations e seeds usando a conexão recém-criada
            $this->runMigrationsAndSeeds($pdo);

            // 3. Cria o administrador e associa ao super-admin
            $this->createAdmin($pdo, $data);

            // 4. Ajusta configurações iniciais informadas
            $this->applyInitialSettings($pdo, $data);

            // 5. Grava o lock de instalação
            $this->writeLock();
        } catch (\Throwable $e) {
            \App\Core\Logger::exception($e);
            // Reverte o config para permitir nova tentativa
            @unlink(LOCAL_CONFIG_FILE);
            Session::flash('error', 'Falha durante a instalação: ' . $e->getMessage());
            Session::flashInput($data);
            Response::redirect('/install');
            return;
        }

        Response::html(View::render('install.success', [
            'adminEmail' => $data['admin_email'],
        ], null));
    }

    // ---------------------------------------------------------------

    private function connect(array $data): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $data['db_host'],
            (int) $data['db_port'],
            $data['db_name']
        );
        return new PDO($dsn, $data['db_user'], $data['db_pass'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    private function writeLocalConfig(array $data): void
    {
        $appKey = bin2hex(random_bytes(32));

        $config = [
            'environment' => 'production',
            'app_key'     => $appKey,
            'database'    => [
                'driver'    => 'mysql',
                'host'      => $data['db_host'],
                'port'      => (int) $data['db_port'],
                'name'      => $data['db_name'],
                'user'      => $data['db_user'],
                'password'  => $data['db_pass'] ?? '',
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
        ];

        $export = "<?php\n\n// Arquivo gerado automaticamente pelo instalador.\n"
                . "// NÃO versione este arquivo (contém credenciais).\n\n"
                . 'return ' . var_export($config, true) . ";\n";

        if (@file_put_contents(LOCAL_CONFIG_FILE, $export) === false) {
            throw new \RuntimeException('Não foi possível gravar config/local.php. Verifique as permissões da pasta config.');
        }
        @chmod(LOCAL_CONFIG_FILE, 0640);
    }

    /**
     * Executa migrations e seeds diretamente com a conexão fornecida.
     */
    private function runMigrationsAndSeeds(PDO $pdo): void
    {
        // Migrations (em ordem), registrando na tabela migrations
        $files = glob(MIGRATIONS_PATH . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            if ($sql !== false && trim($sql) !== '') {
                $pdo->exec($sql);
                $name = basename($file);
                // Registra (a tabela migrations já existe após a 001)
                $stmt = $pdo->prepare(
                    "INSERT INTO `migrations` (`migration`, `batch`, `status`)
                     VALUES (:m, 1, 'success')
                     ON DUPLICATE KEY UPDATE `batch` = `batch`"
                );
                $stmt->execute(['m' => $name]);
            }
        }

        // Seeds
        $seeds = glob(SEEDS_PATH . '/*.sql') ?: [];
        sort($seeds, SORT_STRING);
        foreach ($seeds as $seed) {
            $sql = file_get_contents($seed);
            if ($sql !== false && trim($sql) !== '') {
                $pdo->exec($sql);
            }
        }
    }

    private function createAdmin(PDO $pdo, array $data): void
    {
        $hash = password_hash($data['admin_password'], PASSWORD_BCRYPT, ['cost' => 12]);

        // Cria/atualiza o usuário administrador
        $stmt = $pdo->prepare(
            "INSERT INTO `users` (`name`, `email`, `password`, `is_active`)
             VALUES (:name, :email, :password, 1)
             ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `password` = VALUES(`password`), `is_active` = 1"
        );
        $stmt->execute([
            'name'     => $data['admin_name'],
            'email'    => $data['admin_email'],
            'password' => $hash,
        ]);

        // Recupera o ID do usuário
        $stmt = $pdo->prepare("SELECT `id` FROM `users` WHERE `email` = :email LIMIT 1");
        $stmt->execute(['email' => $data['admin_email']]);
        $userId = (int) $stmt->fetchColumn();

        // Recupera a role super-admin (criada pelo seed 002)
        $roleId = (int) $pdo->query("SELECT `id` FROM `roles` WHERE `slug` = 'super-admin' LIMIT 1")->fetchColumn();

        if ($userId > 0 && $roleId > 0) {
            $stmt = $pdo->prepare(
                "INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES (:u, :r)
                 ON DUPLICATE KEY UPDATE `user_id` = `user_id`"
            );
            $stmt->execute(['u' => $userId, 'r' => $roleId]);
        }
    }

    private function applyInitialSettings(PDO $pdo, array $data): void
    {
        $settings = [
            'site_name' => $data['site_name'] ?? 'Nome do Jogo',
            'site_url'  => rtrim($data['site_url'] ?? '', '/'),
        ];
        $stmt = $pdo->prepare("UPDATE `settings` SET `value` = :v WHERE `key` = :k");
        foreach ($settings as $key => $value) {
            if ($value !== '') {
                $stmt->execute(['v' => $value, 'k' => $key]);
            }
        }
    }

    private function writeLock(): void
    {
        $content = "Instalado em " . date('Y-m-d H:i:s') . " (v" . APP_VERSION . ")\n";
        if (@file_put_contents(INSTALL_LOCK_FILE, $content) === false) {
            throw new \RuntimeException('Não foi possível gravar o arquivo de bloqueio da instalação.');
        }
    }

    // ---------- Verificações de requisitos ----------

    private function runChecks(): array
    {
        $checks = [];

        $checks[] = [
            'label'  => 'PHP ' . self::PHP_MIN . ' ou superior',
            'pass'   => version_compare(PHP_VERSION, self::PHP_MIN, '>='),
            'detail' => 'Versão atual: ' . PHP_VERSION,
        ];

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $checks[] = [
                'label'  => 'Extensão: ' . $ext,
                'pass'   => extension_loaded($ext),
                'detail' => extension_loaded($ext) ? 'Carregada' : 'Ausente',
            ];
        }

        $checks[] = [
            'label'  => 'Pasta config/ gravável',
            'pass'   => is_writable(CONFIG_PATH),
            'detail' => is_writable(CONFIG_PATH) ? 'OK' : 'Sem permissão de escrita',
        ];
        $checks[] = [
            'label'  => 'Pasta storage/ gravável',
            'pass'   => is_writable(STORAGE_PATH),
            'detail' => is_writable(STORAGE_PATH) ? 'OK' : 'Sem permissão de escrita',
        ];
        $checks[] = [
            'label'  => 'Pasta public/uploads gravável',
            'pass'   => is_writable(PUBLIC_UPLOADS_PATH),
            'detail' => is_writable(PUBLIC_UPLOADS_PATH) ? 'OK' : 'Sem permissão de escrita',
        ];

        return $checks;
    }

    private function allChecksPass(): bool
    {
        foreach ($this->runChecks() as $check) {
            if (!$check['pass']) {
                return false;
            }
        }
        return true;
    }
}

/**
 * Verifica o CSRF de forma isolada do Controller base (que faz redirect back).
 */
function Csrf_safe(Request $request): bool
{
    return \App\Core\Csrf::validate($request->csrfToken());
}
