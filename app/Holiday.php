<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Holiday extends Model
{   
    use LogsActivity;
    
    /* Start - Activity Logs */
    protected static $logAttributes = ['name', 'holiday_date'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'holidays';
    /* End - Activity Logs */

    protected $fillable = ['name', 'holiday_date'];
}
