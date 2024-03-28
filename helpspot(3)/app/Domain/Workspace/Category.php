<?php

namespace HS\Domain\Workspace;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'HS_Category';

    protected $primaryKey = 'xCategory';

    public $timestamps = false;

    protected $indexed;

    public function requests()
    {
        return $this->hasMany(\HS\Domain\Workspace\Request::class, 'xCategory');
    }

    /**
     * Get custom field list as array.
     * @param $value
     * @return mixed
     */
    public function getSCustomFieldListAttribute($value)
    {
        return unserialize(trim($value));
    }

    /**
     * Set array custom field list as serialized string.
     * @param $value
     */
    public function setSCustomFieldListAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['sCustomFieldList'] = serialize($value);
        } else {
            $this->attributes['sCustomFieldList'] = $value;
        }
    }
}
