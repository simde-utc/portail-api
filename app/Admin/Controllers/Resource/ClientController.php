<?php
/**
 * Manage Clients as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\Client;
use App\Models\User;
use App\Models\Asso;

class ClientController extends ResourceController
{
    protected $model = Client::class;

    protected $name = "Client OAuth";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'user' => User::get(['id', 'lastname', 'firstname']),
            'asso' => Asso::get(['id', 'name']),
            'name' => 'text',
            'secret' => 'text',
            'redirect' => 'text',
            'targeted_types' => 'text',
            'policy_url' => 'text',
            'personal_access_client' => 'switch',
            'password_client' => 'switch',
            'restricted' => 'switch',
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
        // Keep fields names as label.
        return [];
    }

    /**
     * Default values definition of the fields to display.
     *
     * @return array
     */
    protected function getDefaults(): array
    {
        return [
            'user_id' => \Auth::guard('admin')->id(),
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
            'user', 'asso'
        ];
    }
}
