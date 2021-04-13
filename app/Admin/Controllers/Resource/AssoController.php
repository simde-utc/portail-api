<?php
/**
 * Manage Associations as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\Asso;
use App\Models\AssoType;

class AssoController extends ResourceController
{
    protected $model = Asso::class;

    protected $name = "Association";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'name' => 'text',
            'shortname' => 'text',
            'login' => 'text',
            'image' => 'image',
            'description' => 'textarea',
            'type' => AssoType::get(['id', 'name']),
            'parent' => Asso::get(['id', 'name']),
            'in_cemetery_at' => 'date',
            'created_at' => 'date',
            'updated_at' => 'date',
            'deleted_at' => 'date',
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
            'name' => 'Nom Officiel',
            'shortname' => 'Nom D\'usage',
            'in_cemetery_at' => 'Mis au cimetière le',
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
            'type', 'parent'
        ];
    }
}
