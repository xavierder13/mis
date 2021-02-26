<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{   
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
