<?php

namespace App\Imports;

use App\ProjectLog;
use Maatwebsite\Excel\Concerns\ToModel;

class ProjectLogsImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // return new ProjectLog([
        //     //
        // ]);
    }
}
