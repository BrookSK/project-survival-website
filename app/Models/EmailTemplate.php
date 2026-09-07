<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de templates de e-mail.
 */
class EmailTemplate extends Model
{
    protected string $table = 'email_templates';
    protected array $fillable = ['key', 'name', 'subject', 'body', 'placeholders', 'is_active'];

    public function findByKey(string $key): ?array
    {
        return $this->findBy('key', $key);
    }

    public function allOrdered(): array
    {
        return $this->db->fetchAll("SELECT * FROM `email_templates` ORDER BY `name` ASC");
    }
}
