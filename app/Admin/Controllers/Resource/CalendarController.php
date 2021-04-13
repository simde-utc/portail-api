<?php
/**
 * Manage Calendars as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\{
    Calendar, User, Visibility
};

class CalendarController extends ResourceController
{
    protected $model = Calendar::class;

    protected $name = "Calendrier";

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
            'description' => 'textarea',
            'color' => 'color',
            'visibility' => Visibility::get(['id', 'name']),
            'created_by' => 'display',
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
            'color' => 'Couleur',
            'visibility' => 'Visibilité',
            'created_by' => 'Créé par',
            'owned_by' => 'Possédé par',
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
            'visibility_id' => Visibility::first()->id,
            'created_by_type' => User::class,
            'created_by_id' => \Auth::guard('admin')->user()->id
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
            'visibility', 'created_by', 'owned_by'
        ];
    }
}
