<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RefNoSetting extends Model
{

    use LogsActivity;

    protected $fillable = ['start', 'active'];

    /* Start - Activity Logs */
    protected static $logAttributes = ['start', 'active'];
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'ref_no_settings';
    /* End - Activity Logs */
}
