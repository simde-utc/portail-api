<?php
/**
 * Manage Applications Authentification as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\AuthApp;
use App\Models\User;

class AuthAppController extends ResourceController
{
    protected $model = AuthApp::class;

    protected $name = "Autentification par application";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'user' => User::get(['id', 'firstname', 'lastname']),
            'app_id' => 'text',
            'password' => 'text',
            'key' => 'text',
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
            'user' => 'Utilisateur',
            'app_id' => 'Id de l\'application',
            'password' => 'Mot de passe',
            'key' => 'Clé',
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
            'user'
        ];
    }
}
