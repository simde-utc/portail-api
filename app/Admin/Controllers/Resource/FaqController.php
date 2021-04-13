<?php
/**
 * Manage FAQ questions as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
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

    protected $name = "FAQ";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'question' => 'text',
            'answer' => 'textarea',
            'category' => FaqCategory::get(['id', 'name']),
            'visibility' => Visibility::get(['id', 'name']),
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Fields to display labels definition.
     *
     * @return array
     */
    protected function getLabels(): array
    {
        return [
            'answer' => 'Réponse',
            'category' => "Catégorie",
            'visibility' => "Visibilité",
        ];
    }

    /**
     * Default values definition of the fields to display.
     *
     * @return array
     */
    protected function getDefaults(): array
    {
        return [
            // We'll uncomment this once FaqCategories will be filled.
            // 'category_id' => FaqCategory::orderBy('created_at', 'DESC')->first()->id.
            'visibility_id' => Visibility::where('type', "public")->first()->id,
        ];
    }

    /**
     * Return dependencies.
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
