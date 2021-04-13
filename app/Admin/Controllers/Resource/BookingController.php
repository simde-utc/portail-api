<?php
/**
 * Manage Bookings as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\{
    Booking, Room, BookingType, Event, User
};

class BookingController extends ResourceController
{
    protected $model = Booking::class;

    protected $name = "Réservation";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'room' => Room::with('location')->get(['id', 'location_id']),
            'type' => BookingType::get(['id', 'name']),
            'event' => Event::get(['id', 'name']),
            'description' => 'textarea',
            'created_by' => 'display',
            'owned_by' => 'display',
            'validated_by' => 'display',
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
            'room' => 'Salle',
            'event' => 'Évènement',
            'created_by' => 'Créé par',
            'owned_by' => 'Possédé par',
            'validated_by' => 'Validé par',
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
            'room', 'type', 'created_by', 'owned_by', 'validated_by'
        ];
    }
}
