<?php

namespace HS;

use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    protected $table = 'HS_Failed_Jobs';

    public $timestamps = false;

    protected $guarded = [];

    protected $baseJob;

    protected $dates = [
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function getNameAttribute()
    {
        return $this->getJob()->jobName();
    }

    public function getMetaDataAttribute()
    {
        return $this->getJob()->visibleMetaData();
    }

    public function getJob()
    {
        if ($this->baseJob) {
            return $this->baseJob;
        }

        return $this->baseJob = unserialize($this->payload['data']['command']);
    }

    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'name' => $this->name,
            'failed_at_human' => $this->failed_at->format('Y-m-d H:i:s (T)'),
            'meta_data' => $this->meta_data,
            'meta_data_json' => json_encode($this->meta_data, JSON_PRETTY_PRINT),
        ]);
    }
}
