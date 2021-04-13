<?php
/**
 * Manage FAQ questions categories as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2019, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\{
    FaqCategory, Visibility
};

class FaqCategoryController extends ResourceController
{
    protected $model = FaqCategory::class;

    protected $name = "Catégorie de FAQ";

    /**
     * Field to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'name' => 'text',
            'description' => 'textarea',
            'lang' => 'text',
            'parent' => FaqCategory::get(['id', 'name']),
            'visibility' => Visibility::get(['id', 'name']),
            'created_at' => 'date',
            'updated_at' => 'date',
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
            'name' => 'Nom',
            'lang' => 'Langue',
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
        $category = FaqCategory::orderBy('created_at', 'DESC')->first();

        return [
            'lang' => 'fr',
            'parent_id' => is_null($category) ? null : $category->id,
            'visibility_id' => Visibility::first()->id,
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
            'parent', 'visibility'
        ];
    }
}
