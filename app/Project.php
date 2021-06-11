<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Project extends Model
{   
    use LogsActivity;
    
    /* Start - Activity Logs */
    protected static $logAttributes = [
        'ref_no',
        'report_title',
        'programmer_id',
        'validator_id',
        'date_receive',
        'date_approve',
        'type',
        'department_id',
        'ideal',
        'template_percent',
        'status',
    ];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'projects';
    /* End - Activity Logs */

    protected $fillable = [
        'ref_no',
        'report_title',
        'programmer_id',
        'validator_id',
        'date_receive',
        'date_approve',
        'type',
        'department_id',
        'ideal',
        'template_percent',
        'status',
    ];

    public function programmer()
    {
        return $this->hasOne('App\User', 'id', 'programmer_id');
        //                 ( <Model>, <id_of_specified_Model>, <id_of_this_model> )
    }

    public function departments()
    {
        return $this->hasOne('App\Department', 'id', 'department_id');
        //                 ( <Model>, <id_of_specified_Model>, <id_of_this_model> )
    }

    public function project_logs()
    {
        return $this->hasMany('App\ProjectLog', 'project_id', 'id');
        //                 ( <Model>, <id_of_specified_Model>, <id_of_this_model> )
    }
}
