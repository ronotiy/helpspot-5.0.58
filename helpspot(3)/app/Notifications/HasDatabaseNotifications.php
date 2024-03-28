<?php


namespace HS\Notifications;

use Illuminate\Notifications\HasDatabaseNotifications as BaseDatabaseNotifications;

trait HasDatabaseNotifications
{
    use BaseDatabaseNotifications;

    /**
     * Get the entity's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->orderBy('created_at', 'desc');
    }
}
