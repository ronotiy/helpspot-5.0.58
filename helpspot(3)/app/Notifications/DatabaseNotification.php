<?php

namespace HS\Notifications;

use Illuminate\Notifications\DatabaseNotification as BaseNotification;

class DatabaseNotification extends BaseNotification
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'HS_Notifications';
}
