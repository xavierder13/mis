<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AcceptanceOverview extends Model
{   
    use LogsActivity;
    
    /* Start - Activity Logs */
    protected static $logAttributes = [
        'project_id',
        'intender_users',
        'location1',
        'location2',
        'overview',
        'validator_note',
        'manager_note',
    ];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'acceptance_overviews';
    /* End - Activity Logs */

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
