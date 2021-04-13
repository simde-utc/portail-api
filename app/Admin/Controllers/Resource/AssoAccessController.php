<?php
/**
 * Manage AssosAccesses as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\AssoAccess;
use App\Models\Asso;
use App\Models\User;
use App\Models\Access;
use App\Models\Semester;

class AssoAccessController extends ResourceController
{
    protected $model = AssoAccess::class;

    protected $name = "Accès par association";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'asso' => Asso::get(['id', 'name']),
            'access' => Access::get(['id', 'name']),
            'member' => User::get(['id', 'firstname', 'lastname']),
            'semester' => Semester::get(['id', 'name']),
            'confirmed_by' => User::get(['id', 'firstname', 'lastname']),
            'validated_by' => User::get(['id', 'firstname', 'lastname']),
            'description' => 'textarea',
            'comment' => 'text',
            'validated_at' => 'date',
            'validated' => 'switch',
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
            'asso' => 'Association',
            'access' => 'Accès',
            'member' => 'Membre',
            'semester' => 'Semestre',
            'confirmed_by' => 'Confirmé par',
            'validated_by' => 'Validé par',
            'comment' => 'Commentaire',
            'validated_at' => 'Validé le',
            'validated' => 'Validé',
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
            'semester_id' => Semester::getThisSemester()->id,
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
            'asso', 'access', 'member', 'semester', 'confirmed_by', 'validated_by'
        ];
    }
}
