<?php

namespace HS;

use Illuminate\Database\Eloquent\Model;

class MultiPortal extends Model
{
    protected $table = 'HS_Multi_Portal';

    protected $primaryKey = 'xPortal';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'fIsPrimaryPortal' => 'boolean',
        'fRequireAuth' => 'boolean',
    ];

    /**
     * Trim trailing path separator's from file paths
     * @param $value
     */
    public function setSPortalPathAttribute($value) {
        $this->attributes['sPortalPath'] = rtrim(trim($value), '/\\');
    }

    public function scopeAsPrimary($query)
    {
        return $query->where('fIsPrimaryPortal', 1);
    }

    public function scopeActive($query)
    {
        return $query->where('fDeleted', 0);
    }

    public function scopeShowDeleted($query)
    {
        return $query->where('fDeleted', 1);
    }

    public function toLogArray() {
        return [
            'xPortal' => $this->getKey(),
            'fDeleted' => $this->fDeleted,
            'sHost' => $this->sHost,
            'sPortalPath' => $this->sPortalPath,
            'sPortalName' => $this->sPortalName,
            'fIsPrimaryPortal' => $this->fIsPrimaryPortal,
            'fRequireAuth' => $this->fRequireAuth,
        ];
    }

    public static function getHostIfOnlyOne() {
        $portals = self::active()->get();
        // If we only have a primary just redirect to it.
        if (count($portals) == 0) {
            return cHOST;
        }
        // If we only have one secondary that acts like a primary just redirect to it.
        if (count($portals) == 1 && $portals->first()->fIsPrimaryPortal) {
            return $portals->first()->sHost;
        }
        return false;
    }
}
