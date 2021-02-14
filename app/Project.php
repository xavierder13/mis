<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    public function departments()
    {
        return $this->hasOne('App\Department', 'id', 'department_id');
        //                 ( <Model>, <id_of_specified_Model>, <id_of_this_model> )
    }
}
