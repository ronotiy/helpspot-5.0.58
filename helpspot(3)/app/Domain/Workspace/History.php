<?php

namespace HS\Domain\Workspace;

use HS\User;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasHistoryLog;

    protected $table = 'HS_Request_History';

    protected $primaryKey = 'xRequestHistory';

    protected $guarded = [];

    public $timestamps = false;

    public function scopePinnedFirst($query)
    {
        return $query->orderBy('fPinned', 'DESC')
            ->orderBy('xRequestHistory', 'DESC');
    }

    public function request()
    {
        return $this->belongsTo(Request::class, 'xRequest');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'xRequestHistory');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'xRequestHistory');
    }

    /**
     * Get the sender ("from") name of the request history if email headers exist
     * Currently only called for rows where fPublic=1
     * @param Request|null $request
     * @return string|null
     */
    public function fromName(Request $request=null)
    {
        $name = null;

        // Attempt to use email headers
        if (! empty(trim($this->tEmailHeaders))) {
            $headers = hs_unserialize($this->tEmailHeaders);
            $from = hs_parse_email_header( ($headers['fromaddress'] ?? $headers['from']) );
            $name = (! empty($from['personal']) ? $from['personal'].' - ' : '').$from['mailbox'].'@'.$from['host'];
        }

        // Else fall back to some defaults
        if(empty($name)) {
            if($this->xPerson == -1) {
                $name = lg_systemname;
            }

            if($this->xPerson == 0) {
                $name = ($request)
                    ? empty(trim($request->customerFullName())) ? lg_request_customer : $request->customerFullName()
                    : lg_request_customer;
            }

            if( $this->xPerson > 0) {
                $user = User::where('fDeleted', 0)->where('xPerson', $this->xPerson)->first();
                if($user) {
                    $name = $user->sFname;
                }
            }
        }

        return $name;
    }
}
