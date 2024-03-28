<?php

namespace HS;

use HS\IncomingMail\FetchMailConcurrency;

use Illuminate\Database\Eloquent\Model;

class Mailbox extends Model
{
    use FetchMailConcurrency;

    protected $table = 'HS_Mailboxes';

    protected $primaryKey = 'xMailbox';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'fArchive' => 'boolean',
    ];

    public static function getActive()
    {
        return self::where('fDeleted', 0)->get();
    }

    /**
     * A human-readable identifier for mailbox
     * @return string
     */
    public function identify()
    {
        return vsprintf("(%s) %s :: %s", [
            $this->getKey(),
            $this->sHostname,
            $this->Username,
        ]);
    }
}
