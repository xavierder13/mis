<?php

namespace App\Imports;

use App\Project;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ProjectsImport implements ToModel
{   
    private $rows = 0;

    use Importable;   

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {   
        // insert all rows except first row(header)
        if($this->rows > 0)
        {   
            var_dump($this->rows);
            // return new Project([
            //     'ref_no' => $row[0], 
            //     'report_title' => $row[1], 
            //     'programmer_id' => $row[2], 
            //     'validator_id' => $row[3], 
            //     'date_receive' => $row[4], 
            //     'date_approve' => $row[5], 
            //     'type' => $row[6], 
            //     'department_id' => $row[7], 
            //     'ideal_prog_hrs' => $row[8], 
            //     'ideal_valid_hrs' => $row[9], 
            //     'status' => $row[10], 
            //     'template_percent' => $row[11], 
            //     'program_percent' => $row[12], 
            //     'validation_percent' => $row[13], 
            //     'program_date' => $row[14], 
            //     'validation_date' => $row[15], 
            //     'program_hrs' => $row[16], 
            //     'validate_hrs' => $row[17], 
            //     'accepted_date' => $row[18]
            // ]);
    
        }

        
        ++$this->rows;
    }

    // public function headingRow(): int
    // {
    //     return 19;
    // }

    // public function getRowCount(): int
    // {
    //     return $this->rows;
    // }   

    // public function rules(): array {
    //     return [
    //         // '*.ref_no' => 'required',
    //         // '*.report_title' => 'required',
    //     ];
    // }

}
