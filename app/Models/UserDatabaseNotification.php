<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class UserDatabaseNotification extends DatabaseNotification
{
    protected $table = 'tes_notifications';
}
