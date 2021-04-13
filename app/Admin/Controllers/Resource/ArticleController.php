<?php
/**
 * Manage Articles as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Models\Article;
use App\Models\Event;
use App\Models\Visibility;
use App\Models\User;

class ArticleController extends ResourceController
{
    protected $model = Article::class;

    protected $name = "Article";

    /**
     * Fields to display definition.
     *
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'id' => 'display',
            'title' => 'text',
            'description' => 'textarea',
            'content' => 'text',
            'image' => 'image',
            'event' => Event::get(['id', 'name']),
            'visibility' => Visibility::get(['id', 'name']),
            'tags' => 'display',
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
            'title' => 'Titre',
            'content' => 'Contenu',
            'event' => 'Évènement',
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
            'user_id' => ($user_id = \Auth::guard('admin')->user()->id),
            'created_by_type' => User::class,
            'created_by_id' => $user_id
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
            'created_by', 'owned_by', 'tags', 'visibility', 'event',
        ];
    }
}
