<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProjectLog extends Model
{
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
