<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de tokens de recuperação de senha.
 * O token em texto puro nunca é armazenado; guardamos apenas o hash.
 */
class PasswordReset extends Model
{
    protected string $table = 'password_resets';
    protected array $fillable = ['email', 'token_hash', 'expires_at', 'used_at'];

    /**
     * Cria um novo token para o e-mail, invalidando os anteriores.
     */
    public function createFor(string $email, string $tokenHash, int $expiresMinutes): void
    {
        $this->db->execute("DELETE FROM `password_resets` WHERE `email` = :e", ['e' => $email]);
        $this->db->execute(
            "INSERT INTO `password_resets` (`email`, `token_hash`, `expires_at`)
             VALUES (:e, :h, DATE_ADD(NOW(), INTERVAL :m MINUTE))",
            ['e' => $email, 'h' => $tokenHash, 'm' => $expiresMinutes]
        );
    }

    /**
     * Busca um token válido (não usado e não expirado) pelo hash.
     */
    public function findValidByHash(string $tokenHash): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM `password_resets`
             WHERE `token_hash` = :h AND `used_at` IS NULL AND `expires_at` > NOW()
             LIMIT 1",
            ['h' => $tokenHash]
        );
    }

    public function markUsed(int $id): void
    {
        $this->db->execute("UPDATE `password_resets` SET `used_at` = NOW() WHERE `id` = :id", ['id' => $id]);
    }
}
