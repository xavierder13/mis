<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Department extends Model
{   
    use LogsActivity;
    
    /* Start - Activity Logs */
    protected static $logAttributes = ['name', 'active'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'departments';
    /* End - Activity Logs */

    protected $fillable = ['name', 'active'];

    public function managers()
    {
        return $this->hasOne('App\Manager', 'department_id', 'id');
        //                 ( <Model>, <id_of_specified_Model>, <id_of_this_model> )
    }
}
