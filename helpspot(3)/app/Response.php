<?php

namespace HS;

use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    protected $table = 'HS_Responses';

    protected $primaryKey = 'xResponse';

    public $timestamps = false;

    protected $guarded = [];

    public static function getActive()
    {
        return self::where('fDeleted', 0)->get();
    }

    /**
     * Calculate the next send date based on report options
     *
     * @return string
     */
    public function calculateNextSendDate()
    {
        return calculateNextSend($this->fSendTime, $this->fSendDay, $this->fSendEvery);
    }
}
