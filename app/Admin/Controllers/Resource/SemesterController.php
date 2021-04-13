<?php
/**
 * Manage Semesters as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\Semester;

class SemesterController extends ResourceController
{
    protected $model = Semester::class;

    protected $name = "Semestre";

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
            'year' => 'text',
            'is_spring' => 'switch',
            'begin_at' => 'datetime',
            'end_at' => 'datetime',
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
}
