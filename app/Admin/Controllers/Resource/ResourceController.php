<?php
/**
 * Generate a management of an admin resource.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;
use App\Admin\{
    GridGenerator, ShowGenerator, FormGenerator
};

abstract class ResourceController extends Controller
{
    use HasResourceActions;

    /**
     * Resource model.
     *
     * @var string
     */
    protected $model;

    /**
     * Resource display name
     *
     * @var string
     */
    protected $name = null;

    /**
     * Give access only if the user hs the right permission.
     */
    public function __construct()
    {
        $this->middleware('permission:'.\ModelResolver::getName($this->model, '-'));
    }

    /**
     * Fields to display definition.
     *
     * @return array
     */
    abstract protected function getFields(): array;

    /**
     * Fields to display definition.
     *
     * @return array
     */
    abstract protected function getLabels(): array;

    /**
     * Default values definition of the fields to display.
     *
     * @return array
     */
    protected function getDefaults(): array
    {
        return [];
    }

    /**
     * Return dependencies.
     *
     * @return array
     */
    protected function getWith(): array
    {
        return [];
    }

    /**
     * Return the model name.
     *
     * @return string
     */
    protected function getName(): string
    {
        return $this->name ? $this->name : ucfirst(\ModelResolver::getName($this->model));
    }

    /**
     * Global display interface
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $grid = new GridGenerator($this->model);

        $grid->addFields($this->getFields(), $this->getLabels());

        return $content
            ->header($this->getName())
            ->description('Affichage global')
            ->body($grid->get());
    }

    /**
     * Show an instance.
     *
     * @param mixed   $model_id
     * @param Content $content
     * @return Content
     */
    public function show($model_id, Content $content)
    {
        $model = $this->model::with($this->getWith());

        if (method_exists(new $this->model, 'trashed')) {
            $model = $model->withTrashed();
        }

        $show = new ShowGenerator($model->findOrFail($model_id));

        $show->addFields(array_keys($this->getFields()), $this->getLabels());

        return $content
            ->header($this->getName())
            ->description('Affichage détaillé')
            ->body($show->get());
    }

    /**
     * Modify an instance.
     *
     * @param mixed   $model_id
     * @param Content $content
     * @return Content
     */
    public function edit($model_id, Content $content)
    {
        return $content
            ->header($this->getName())
            ->description('Modification')
            ->body($this->form()->get()->edit($model_id));
    }

    /**
     * Create a new instance.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header($this->getName())
            ->description('Création')
            ->body($this->form()->get());
    }

    /**
     * Create base form.
     *
     * @return FormGenerator
     */
    protected function form()
    {
        $form = new FormGenerator($this->model);
        $defaults = $this->getDefaults();

        $form->addFields($this->getFields(), $this->getLabels(), $defaults);
        $form->saving(function (Form $form) use ($defaults) {
            foreach ($defaults as $key => $default) {
                $form->$key = $form->$key ?: $default;
            }
        });

        return $form;
    }
}
