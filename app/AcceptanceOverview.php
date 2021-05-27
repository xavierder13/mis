<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcceptanceOverview extends Model
{
    protected $fillable = [
        'project_id',
        'intender_users',
        'location1',
        'location2',
        'overview',
        'validator_note',
        'manager_note',
    ];
}
