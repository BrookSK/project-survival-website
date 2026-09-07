<?php

namespace App\Models;

use App\Core\Model;

/**
 * Model de categorias de FAQ.
 */
class FaqCategory extends Model
{
    protected string $table = 'faq_categories';
    protected array $fillable = ['name', 'slug', 'sort_order'];

    public function allOrdered(): array
    {
        return $this->db->fetchAll("SELECT * FROM `faq_categories` ORDER BY `sort_order` ASC, `name` ASC");
    }
}
