<?php
/**
 * Abstract class for generating a form of a given type.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin;

use Illuminate\Support\{
    Arr, HtmlString
};
use Encore\Admin\Widgets\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    Relation, MorphTo
};
use Carbon\Carbon;

abstract class Generator
{
    protected $model;
    protected $generated;
    protected $generatedModel;
    protected $valueMethod;

    public static $simplePrint = false;

    protected static $must = [
        'id', 'type', 'name', 'title', 'pivot'
    ];

    protected const POSITIVE_ICON = '<i class="fa fa-check text-success"></i>' ;
    protected const NEGATIVE_ICON = '<i class="fa fa-times text-danger"></i>' ;
    protected const NULL_ICON = '<span class="text-warning">~</span>' ;

    /**
     * Prepare generation.
     *
     * @param string $generated
     * @param string $model
     */
    public function __construct(string $generated, string $model)
    {
        $this->model = $model;
        $this->generatedModel = new $model;
        $this->generated = new $generated($this->generatedModel);

        if (method_exists($this->generatedModel, 'trashed')) {
            $this->generated->model()->withTrashed();
        }
    }

    /**
     * Return generated form.
     *
     * @return mixed
     */
    public function get()
    {
        return $this->generated;
    }

    /**
     * Convert a PHP array to a Table for admin interface
     *
     * @param  array $data
     * @return mixed
     */
    public static function arrayToTable(array $data)
    {
        $rows = [];

        foreach ($data as $key => $value) {
            $value = Generator::adminValue($value);

            if (Arr::isAssoc($data)) {
                $rows[] = ['<b>'.e($key).'</b>', $value];
            } else {
                $rows[] = [$value];
            }
        }

        return new Table([], $rows);
    }

    /**
     * Reduce the amount of information to what is strictly necessary.
     *
     * @param  array    $value
     * @param  Relation $relation
     * @return array
     */
    public static function reduceModelArray(array $value, Relation $relation=null)
    {
        $must = static::$must;

        if ($relation instanceof MorphTo) {
            $must[] = 'model';
        }

        foreach (array_keys($value) as $key) {
            if (!in_array($key, $must)) {
                unset($value[$key]);
            } else if (is_array($value[$key])) {
                $value[$key] = static::reduceModelArray($value[$key]);
            }
        }

        return $value;
    }

    /**
     * Convert models in Table.
     *
     * @param  array    $value
     * @param  Relation $relation
     * @return mixed
     */
    public static function modelToTable(array $value, Relation $relation=null)
    {
        if (static::$simplePrint) {
            return ($value['shortname'] ?? $value['name']);
        }

        return Generator::arrayToTable(Generator::reduceModelArray($value, $relation));
    }

    /**
     * Convert value for the admin.
     *
     * @param  mixed $value
     * @param  mixed $field
     * @param  mixed $model
     * @return mixed
     */
    public static function adminValue($value, $field=null, $model=null)
    {
        if (is_array($value)) {
            if ($model && method_exists($generatedModel = new $model, $field)) {
                $relation = $generatedModel->$field();

                if ($relation instanceof Relation) {
                    return Generator::modelToTable($value, $relation);
                }
            }

            return Generator::arrayToTable($value);
        } else if (is_bool($value)) {
            return new HtmlString($value ? static::POSITIVE_ICON : static::NEGATIVE_ICON);
        } else if (is_null($value)) {
            return new HtmlString(static::NULL_ICON);
        }

        try {
            $date = Carbon::createFromFormat(Carbon::DEFAULT_TO_STRING_FORMAT, $value);

            return $date->format('d/m/Y à H:i');
        } catch (\Exception $e) {
            // This field is not a date.
        }

        try {
            if (is_array($array = json_decode($value, true))) {
                return Generator::arrayToTable($array);
            }
        } catch (\Exception $e) {
            // This field is not json-formatted.
        }

        if (is_string($value)) {
            // Call to e($value) allows us to render the value without any injection.
            return e($value);
        } else {
            return $value;
        }
    }

    /**
     * Function to retrieve field label based on the field name and an array of labels
     *
     * @param string $field
     * @param array  $labels Default: empty array.
     * @return string
     */
    protected function getLabel(string $field, array $labels=[])
    {
        $label = null;

        switch ($field) {
            case 'created_at':
                $label = "Créé le";
                break;
            case 'updated_at':
                $label = "Mis à jour le";
                break;
            case 'deleted_at':
                $label = "Supprimé le";
                break;
        }

        $label = key_exists($field, $labels) ? $labels[$field] : $label;
        return $label;
    }

    /**
     * Specific definition from the form type.
     *
     * @param mixed $field
     * @return mixed
     */
    abstract protected function callCustomMethods($field);

    /**
     * Allow to add several fields.
     *
     * @param array $fields
     * @param array $labels Default: empty array.
     * @return Generator
     */
    public function addFields(array $fields, array $labels=[])
    {
        foreach ($fields as $field) {
            if (static::$simplePrint) {
                if (in_array($field, ['id', 'updated_at'])) {
                    continue;
                }
            }

            $this->generateField($field, $this->getLabel($field, $labels));
        }

        return $this;
    }

    /**
     * Generate a new field.
     *
     * @param  string $field
     * @param  string $label
     * @return void
     */
    protected function generateField(string $field, string $label=null)
    {
        $model = $this->model;

        $this->callCustomMethods($this->generated->$field($label))
            ->{$this->valueMethod}(function ($value) use ($field, $model) {
                return Generator::adminValue($value, $field, $model);
            });
    }

    /**
     * Return all calls on the form.
     *
     * @param  string $method
     * @param  array  $args
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        return call_user_func([$this->generated, $method], ...$args);
    }
}
