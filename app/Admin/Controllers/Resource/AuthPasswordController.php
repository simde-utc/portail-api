<?php
/**
 * Manage Password Authentifications as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\AuthPassword;
use App\Models\User;

class AuthPasswordController extends ResourceController
{
    protected $model = AuthPassword::class;

    protected $name = "Autentification par Mot de passe";

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
            'password' => 'text',
            'last_login_at' => 'display',
            'created_at' => 'datetime',
            'updated_at' => 'datetime'
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
            'user' => "Utilisateur",
            'password' => 'Mot de passe',
            'last_login_at' => 'Dernière connexion le',
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

    /**
     * Generate the password's hash before save.
     *
     * @return mixed
     */
    protected function form()
    {
        $form = parent::form();

        $form->saving(function ($form) {
            $form->password = \Hash::make($form->password);
        });

        return $form;
    }
}
