<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EndorseProject extends Model
{
    protected $fillable  = [
        'project_id',
        'programmer_id',
        'endorse_date',
        'endorsed',
        'date_receive',
        'program_date',
        'validation_date'
    ];
}
