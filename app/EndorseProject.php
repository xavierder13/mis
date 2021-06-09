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

    public function programmer()
    {
        return $this->hasOne('App\User', 'id', 'programmer_id');
        //                 ( <Model>, <id_of_specified_Model>, <id_of_this_model> )
    }
}
