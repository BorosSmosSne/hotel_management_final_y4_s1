<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row per setting (key / value). Read and written through App\Support\HotelSettings. */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];
}
