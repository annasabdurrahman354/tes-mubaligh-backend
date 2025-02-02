<?php

namespace App\Models;

use BezhanSalleh\FilamentExceptions\Models\Exception as BaseException;

class Exception extends BaseException
{
    protected $table = 'tes_filament_exceptions_table';
}
