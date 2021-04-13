<?php
/**
 * Manage Roles as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\Asso;
use App\Models\Role;

class RoleController extends ResourceController
{
    protected $model = Role::class;

    protected $name = "Rôle";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'type' => 'display',
            'name' => 'text',
            'description' => 'textarea',
            'limited_at' => 'number',
            'owned_by' => 'display',
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
            'limited_at' => 'Limité à',
            'owned_by' => 'Possédé par',
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
            'owned_by'
        ];
    }
}
