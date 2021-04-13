<?php
/**
 * Manage Partners as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\Partner;

class PartnerController extends ResourceController
{
    protected $model = Partner::class;

    protected $name = "Partenaire";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'name' => 'text',
            'website' => 'url',
            'address' => 'text',
            'postal_code' => 'text',
            'city' => 'text',
            'description' => 'textarea',
            'image' => 'image',
            'created_at' => 'date',
            'updated_at' => 'date'
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
            'website' => 'Site web',
            'address' => 'Adresse',
            'postal_code' => 'Code Postal',
            'city' => 'Ville',
        ];
    }
}
