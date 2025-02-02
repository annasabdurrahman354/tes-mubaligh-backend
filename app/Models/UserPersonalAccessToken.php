<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class UserPersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $table = 'tes_personal_access_tokens';
}
