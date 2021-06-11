<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProjectLog extends Model
{
    use LogsActivity;

    /* Start - Activity Logs */
    protected static $logAttributes =  [
        'project_id',
        'remarks_date',
        'remarks_time',
        'remarks',
        'status'
    ];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'project_logs';
    /* End - Activity Logs */

    protected $fillable = [
        'project_id',
        'remarks_date',
        'remarks_time',
        'remarks',
        'status'
    ];

    
    public function projects()
    {   
        return $this->belongsTo('App\Project', 'project_id','id');
        //                 ( <Model>, <id_of_this_model>, <id_of_specified_Model> )
    }
}
