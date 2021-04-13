<?php
/**
 * Generate an admin presentation.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin;

use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\Relation;

class ShowGenerator extends Generator
{
    /**
     * Method corresponding to the admin (which changes according to the type of form...).
     *
     * @var string
     */
    protected $valueMethod = 'as';

    /**
     * Create the presentation according to a model
     *
     * @param mixed $model Model of the resource to handle.
     */
    public function __construct($model)
    {
        $this->model = get_class($model);
        $this->generatedModel = $model;
        $this->generated = new Show($model);
    }

    /**
     * Indicate that html rendering is possible.
     *
     * @param  mixed $field
     * @return mixed
     */
    protected function callCustomMethods($field)
    {
        return $field->unescape();
    }

    /**
     * Generate a new field and linked field.
     *
     * @param  string $field
     * @param  string $label
     * @return void
     */
    protected function generateField(string $field, string $label=null)
    {
        if (method_exists($this->generatedModel, $field)
            && (($relation = $this->generatedModel->$field()) instanceof Relation)) {
            $must = $relation->getModel()->getMustFields();
            $resource = Str::plural($relation->getModel());

            $this->generated->$field($label, function ($value) use ($must, $resource) {
                $value->setResource('/admin/'.$resource);

                foreach ($must as $key) {
                    $this->callCustomMethods($value->$key())
                        ->{$this->valueMethod}(function ($value) {
                            return ShowGenerator::adminValue($value);
                        });
                }
            });
        } else {
            parent::generateField($field, $label);
        }
    }
}
