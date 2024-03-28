<?php

namespace HS;

use HS\Notifications\ResetPortalPassword;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class PortalLogin extends Authenticatable
{
    use Notifiable;

    protected $table = 'HS_Portal_Login';

    protected $primaryKey = 'xLogin';

    protected $rememberTokenName = 'sRememberToken';

    protected $guard = 'portal';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'sPasswordHash', 'sRememberToken',
    ];

    /**
     * Mutate "$this->email" to "$this->sEmail" for notifications (e.g. reset password).
     * @return mixed
     */
    public function getEmailAttribute()
    {
        return $this->sEmail;
    }

    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->sEmail;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->sPasswordHash;
    }

    public static function getByEmail($email)
    {
        return self::where('sEmail', $email)->first();
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPortalPassword($token));
    }
}
