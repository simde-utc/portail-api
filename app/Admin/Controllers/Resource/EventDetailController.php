<?php
/**
 * Manage EventDetails as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\{
    Event, EventDetail
};

class EventDetailController extends ResourceController
{
    protected $model = EventDetail::class;

    protected $name = "Détail d'évènements";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'event' => Event::get(['id', 'name']),
            'key' => 'text',
            'value' => 'text',
            'type' => 'display',
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
            'event' => 'Évènement',
            'key' => 'Clé',
            'value' => 'Valeur',
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
            'event'
        ];
    }
}
