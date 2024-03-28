<?php

namespace HS;

use HS\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'HS_Person';

    protected $primaryKey = 'xPerson';

    protected $rememberTokenName = 'sRememberToken';

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

    // todo: mutators for password -> sPasswordHash ?

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->sPasswordHash;
    }

    public function getFullNameAttribute()
    {
        return trim($this->sFname.' '.$this->sLname);
    }

    public static function getByEmail($email)
    {
        return self::where('sEmail', $email)->where('fDeleted', 0)->first();
    }

    public function status()
    {
        return $this->hasOne(UserStatus::class, 'xPersonStatus');
    }

    public function permissionGroup()
    {
        return $this->belongsTo(PermissionGroup::class, 'fUserType', 'xGroup');
    }

    public function scopeActive($query)
    {
        return $query->where('fDeleted', 0);
    }

    public function scopeInactive($query)
    {
        return $query->where('fDeleted', 1);
    }

    public function isAdmin()
    {
        return $this->fUserType == 1;
    }
}
