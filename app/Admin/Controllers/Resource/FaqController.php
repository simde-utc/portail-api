<?php
/**
 * Gère en admin les questions FAQs.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2019, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\{
    Faq, FaqCategory, Visibility
};

class FaqController extends ResourceController
{
    protected $model = Faq::class;

    /**
     * Définition des champs à afficher.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'question' => 'text',
            'anwser' => 'textarea',
            'category' => FaqCategory::get(['id', 'name']),
            'visibility' => Visibility::get(['id', 'name']),
            'created_at' => 'display',
            'updated_at' => 'display',
        ];
    }

    /**
     * Définition des valeurs par défaut champs à afficher.
     *
     * @return array
     */
    protected function getDefaults(): array
    {
        return [
            'category_id' => FaqCategory::last()->id,
            'visibility_id' => Visibility::first()->id,
        ];
    }

    /**
     * Retourne les dépendances.
     *
     * @return array
     */
    protected function getWith(): array
    {
        return [
            'category', 'visibility'
        ];
    }
}
