<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Manager extends Model
{   
    use LogsActivity;
    
    /* Start - Activity Logs */
    protected static $logAttributes = ['name', 'department_id', 'active'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'managers';
    /* End - Activity Logs */

    protected $fillable = ['name', 'department_id', 'active'];
    
    public function departments()
    {
        return $this->hasOne('App\Department', 'id', 'department_id');
        //                 ( <Model>, <id_of_specified_Model>, <id_of_this_model> )
    }
}
