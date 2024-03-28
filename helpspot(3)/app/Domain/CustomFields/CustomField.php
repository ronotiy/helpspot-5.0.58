<?php

namespace HS\Domain\CustomFields;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model implements Field
{
    use \HS\Domain\CustomFields\FieldTypes\NullField;

    protected $table = 'HS_CustomFields';

    protected $primaryKey = 'xCustomField';

    protected $fillable = ['fieldType'];

    public $timestamps = false;

    public function getSCustomFieldListAttribute($value)
    {
        return unserialize(trim($value));
    }

    public function setSCustomFieldListAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['sCustomFieldList'] = serialize(trim($value));
        }
    }

    /**
     * Create a new instance of the given model.
     * Overloading parent to generate correct type.
     * @param  array  $attributes
     * @param  bool   $exists
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $attributes = (array) $attributes;
        if (isset($attributes['fieldType'])) {
            $customFieldClassname = '\\HS\Domain\\CustomFields\\'.ucfirst($attributes['fieldType']).'Field';
            $model = new $customFieldClassname((array) $attributes);
        } else {
            // This method just provides a convenient way for us to generate fresh model
            // instances of this current model. It is particularly useful during the
            // hydration of new objects via the Eloquent query builder instances.
            $model = new static($attributes);
        }

        $model->exists = $exists;

        return $model;
    }

    /**
     * Create a new model instance that is existing.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $attributes = (array) $attributes;
        $passArray = (isset($attributes['fieldType'])) ? ['fieldType' => $attributes['fieldType']] : [];

        $model = $this->newInstance($passArray, true);

        $model->setRawAttributes($attributes, true);

        $model->setConnection($connection ?: $this->getConnectionName());

        return $model;
    }
}
