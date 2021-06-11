<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EndorseProject extends Model
{   
    use LogsActivity;
    
    /* Start - Activity Logs */
    protected static $logAttributes = [
        'project_id',
        'programmer_id',
        'endorse_date',
        'endorsed',
        'date_receive',
        'program_date',
        'validation_date'
    ];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'endorse_projects';
    /* End - Activity Logs */

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
