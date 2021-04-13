<?php
/**
 * Manage Rooms as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\{
    Room, Location, Visibility, Calendar, User
};

class RoomController extends ResourceController
{
    protected $model = Room::class;

    protected $name = "Salle";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'location' => Location::get(['id', 'name']),
            'visibility' => Visibility::get(['id', 'name']),
            'calendar' => Calendar::get(['id', 'name']),
            'created_by' => 'display',
            'owned_by' => 'display',
            'capacity' => 'number',
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
            'location' => 'Lieu',
            'visibility' => 'Visibilité',
            'calendar' => 'Calendrier',
            'created_by' => 'Créé par',
            'owned_by' => 'Possédé par',
            'capacity' => 'Capacité',
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
            'location', 'calendar', 'created_by', 'owned_by', 'visibility',
        ];
    }
}
