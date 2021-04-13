<?php
/**
 * Manage ContactTypes as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\ContactType;

class ContactTypeController extends ResourceController
{
    protected $model = ContactType::class;

    protected $name = "Type de contact";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'type' => 'display',
            'name' => 'text',
            'pattern' => 'text',
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
            'pattern' => 'Structure',
        ];
    }
}
