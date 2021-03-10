<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;
use App\Department;
use App\Manager;
use App\Project;
use App\ProjectLog;
use App\User;
use App\RefNoSetting;
use App\Holiday;
use Carbon\Carbon;

class ProjectController extends Controller
{   

    public function index()
    {
        $projects = DB::table('projects')
                    ->join('departments', 'projects.department_id', '=','departments.id')
                    ->join('managers', 'departments.id', '=', 'managers.department_id')
                    ->join(DB::raw('users as programmers'), 'projects.programmer_id', '=', 'programmers.id')
                    ->leftJoin(DB::raw('users as validators'), 'projects.validator_id', '=', 'validators.id')
                    ->select(DB::raw('projects.id as project_id'), 'projects.ref_no', 'projects.report_title', DB::raw('departments.name as department'), 
                             DB::raw('departments.id as department_id'), DB::raw('managers.name as manager'), 
                             DB::raw('programmers.name as programmer'), DB::raw('programmers.id as programmer_id'),
                             DB::raw('validators.name as validator'), DB::raw('validators.id as validator_id'),
                             DB::raw("DATE_FORMAT(projects.created_at, '%m/%d/%Y') as date_logged"),
                             DB::raw("DATE_FORMAT(projects.date_receive, '%m/%d/%Y') as date_received"),
                             DB::raw("DATE_FORMAT(projects.date_approve, '%m/%d/%Y') as date_approved"),
                             DB::raw("DATE_FORMAT(projects.program_date, '%m/%d/%Y') as program_date"),
                             DB::raw("DATE_FORMAT(projects.validation_date, '%m/%d/%Y') as validation_date"),
                             'projects.type', 'projects.ideal', 'projects.template_percent', 'projects.status',
                             'projects.program_percent', 'projects.validation_percent', 'program_hrs', 'validate_hrs')
                    ->where('projects.status', '!=', 'Cancelled')
                    ->orderBy('projects.id', 'Desc')
                    ->get();

        $departments = Department::with('managers')->get();

        $programmers = User::where('type', '=', 'Programmer')->get();

        $validators = User::where('type', '=', 'Validator')->get();


        return response()->json([
            'projects' => $projects, 
            'departments' => $departments,
            'programmers' => $programmers,
            'validators' => $validators,
        ], 200);

    }
    public function programmer_reports(Request $request)
    {   
        $filter_date = Carbon::parse($request->get('filter_date'))->format('Y-m-d');
        $firstOfMonth = Carbon::parse($filter_date)->firstOfMonth()->format('Y-m-d');
        $lastOfMonth = Carbon::parse($filter_date)->lastOfMonth()->format('Y-m-d');

        $projects = Project::where('projects.status', '!=', 'Cancelled')
                           ->select(DB::raw('*'), DB::raw('id as project_id'))
                           ->get();
        
        // calculate programming/validation hrs for all projects
        foreach($projects as $i => $project)
        {
            if($project->status != 'Accepted')
            {   
                $project_logs = ProjectLog::where('project_id', '=', $project->project_id)
                                        ->orderBy('remarks_date', 'Asc')
                                        ->orderBy('remarks_time', 'Asc')
                                        ->get();
                if(count($project_logs))
                {
                    // calculate hours difference per remarks log
                    $this->calculateHours($project_logs); 
                }                          
            }
        }    

        $projects = DB::table('projects')
                    ->join('departments', 'projects.department_id', '=','departments.id')
                    ->join('managers', 'departments.id', '=', 'managers.department_id')
                    ->join(DB::raw('users as programmers'), 'projects.programmer_id', '=', 'programmers.id')
                    ->leftJoin(DB::raw('users as validators'), 'projects.validator_id', '=', 'validators.id')
                    ->select(DB::raw('projects.id as project_id'), 'projects.ref_no', 'projects.report_title', DB::raw('departments.name as department'), 
                             DB::raw('departments.id as department_id'), DB::raw('managers.name as manager'), 
                             DB::raw('programmers.name as programmer'), DB::raw('programmers.id as programmer_id'),
                             DB::raw('validators.name as validator'), DB::raw('validators.id as validator_id'),
                             DB::raw("DATE_FORMAT(projects.created_at, '%m/%d/%Y') as date_logged"),
                             DB::raw("DATE_FORMAT(projects.date_receive, '%m/%d/%Y') as date_received"),
                             DB::raw("DATE_FORMAT(projects.date_approve, '%m/%d/%Y') as date_approved"),
                             DB::raw("DATE_FORMAT(projects.program_date, '%m/%d/%Y') as program_date"),
                             DB::raw("DATE_FORMAT(projects.validation_date, '%m/%d/%Y') as validation_date"),
                             DB::raw("DATE_FORMAT(projects.accepted_date, '%m/%d/%Y') as accepted_date"),
                             'projects.type', 'projects.ideal', 'projects.template_percent', 'projects.status',
                             'projects.program_percent', 'projects.validation_percent', 'program_hrs', 'validate_hrs')
                    ->where('projects.status', '!=', 'Cancelled')
                    ->where(function($query) use ($firstOfMonth, $lastOfMonth) {
                        $query->whereBetween('projects.accepted_date', [$firstOfMonth, $lastOfMonth])
                              ->orWhereNull('projects.accepted_date');
                    })
                    ->orderBy('project_id', 'Desc')
                    ->get();

        $project_logs = Project::with('project_logs')->where('status', '!=', 'Cancelled')->get();
        
        $departments = Department::with('managers')->get();

        $programmers = User::where('type', '=', 'Programmer')->get();

        $validators = User::where('type', '=', 'Validator')->get();

        return response()->json([
            'projects' => $projects, 
            'departments' => $departments,
            'programmers' => $programmers,
            'validators' => $validators,
            'project_logs' => $project_logs,
        ], 200);
    }

    public function store(Request $request)
    {   
      
        $rules = [
            'report_title.required' => 'Report Title is required',
            'department_id.required' => 'Department is required',
            'department_id.integer' => 'Department must be an integer',
            'programmer_id.required' => 'Programmer is required',
            'programmer_id.integer' => 'Programmer must be an integer',
            // 'validator.required' => 'Validator is required',
            // 'validator.integer' => 'Validator must be an integer',
            // 'date_received.sometimes' => 'Enter a valid date',
            // 'date_approved.sometimes' => 'Enter a valid date',
            'type.required' => 'Report Type is required'
        ];

        $valid_fields = [
            'report_title' => 'required',
            'department_id' => 'required|integer',
            'programmer_id' => 'required|integer',
            // 'validator_id' => 'sometimes|required|integer',
            // 'date_received' => 'sometimes|date',
            // 'date_approved' => 'sometimes|date',
            'type' => 'required',
        ];

        
        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $ref_no = $this->getRefNo();

        $project = new Project();
        $project->ref_no = $ref_no;
        $project->report_title = $request->get('report_title');
        $project->department_id = $request->get('department_id');
        $project->programmer_id = $request->get('programmer_id');
        $project->validator_id = $request->get('validator_id');
        if($request->get('date_received'))
        {
            $project->date_receive = Carbon::parse($request->get('date_received'))->format('Y-m-d');
        }
        if($request->get('date_approved'))
        {
            $project->date_approve = Carbon::parse($request->get('date_approved'))->format('Y-m-d');
        }
        $project->ideal = $request->get('ideal');
        $project->template_percent = $request->get('template_percent');
        $project->type = $request->get('type');
        $project->status = "Pending";
        $project->save();

        if($project->id)
        {
            RefNoSetting::first()->update(['active' => 'N']);
        }

        $project = DB::table('projects')
                    ->join('departments', 'projects.department_id', '=','departments.id')
                    ->join('managers', 'departments.id', '=', 'managers.department_id')
                    ->join(DB::raw('users as programmers'), 'projects.programmer_id', '=', 'programmers.id')
                    ->leftJoin(DB::raw('users as validators'), 'projects.validator_id', '=', 'validators.id')
                    ->select('projects.id', 'projects.ref_no', 'projects.report_title', DB::raw('departments.name as department'), 
                             DB::raw('departments.id as department_id'), DB::raw('managers.name as manager'), 
                             DB::raw('programmers.name as programmer'), DB::raw('programmers.id as programmer_id'),
                             DB::raw('validators.name as validator'), DB::raw('validators.id as validator_id'),
                             DB::raw("DATE_FORMAT(projects.created_at, '%m/%d/%Y') as date_logged"),
                             DB::raw("DATE_FORMAT(projects.date_receive, '%m/%d/%Y') as date_received"),
                             DB::raw("DATE_FORMAT(projects.date_approve, '%m/%d/%Y') as date_approved"),
                             'projects.type', 'projects.ideal', 'projects.template_percent', 'projects.status',
                             'projects.program_percent', 'projects.validation_percent',
                             'projects.program_date', 'projects.validation_date')
                    ->where('projects.id', '=', $project->id)
                    ->first();

        return response()->json(['success' => 'Record has successfully added', 'project' => $project], 200);
    }

    public function update(Request $request, $project_id)
    {   
        
        $rules = [
            'report_title.required' => 'Report Title is required',
            'department_id.required' => 'Department is required',
            'department_id.integer' => 'Department must be an integer',
            'programmer_id.required' => 'Programmer is required',
            'programmer_id.integer' => 'Programmer must be an integer',
            // 'validator.required' => 'Validator is required',
            // 'validator.integer' => 'Validator must be an integer',
            // 'date_received.sometimes' => 'Enter a valid date',
            // 'date_approved.sometimes' => 'Enter a valid date',
            'type.required' => 'Report Type is required'
        ];

        $valid_fields = [
            'report_title' => 'required',
            'department_id' => 'required|integer',
            'programmer_id' => 'required|integer',
            // 'validator_id' => 'sometimes|required|integer',
            // 'date_received' => 'sometimes|date',
            // 'date_approved' => 'sometimes|date',
            'type' => 'required',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $project = Project::find($project_id);
        $project->report_title = $request->get('report_title');
        $project->department_id = $request->get('department_id');
        $project->programmer_id = $request->get('programmer_id');
        $project->validator_id = $request->get('validator_id');
        if($request->get('date_received'))
        {
            $project->date_receive = Carbon::parse($request->get('date_received'))->format('Y-m-d');
        }
        if($request->get('date_approved'))
        {
            $project->date_approve = Carbon::parse($request->get('date_approved'))->format('Y-m-d');
        }
        $project->ideal = $request->get('ideal');
        $project->template_percent = $request->get('template_percent');
        $project->type = $request->get('type');
        $project->save();


        return response()->json(['success' => 'Record has been updated', 'project' => $project], 200);
    }

    public function update_status(Request $request)
    {   
        // return $request;
        $project = Project::find($request->get('project_id'));

        $project->template_percent = $request->get('template_percent');
        if($request->get('program_date'))
        {
            $project->program_date = Carbon::parse($request->get('program_date'))->format('Y-m-d');
        }
        $project->program_percent = $request->get('program_percent');
        if($request->get('validation_date'))
        {
            $project->validation_date = Carbon::parse($request->get('validation_date'))->format('Y-m-d');
        }
        $project->validation_percent = $request->get('validation_percent');
        $project->save();
        
        return response()->json(['success' => 'Record has been updated', 'project' => $project], 200);

    }

    public function delete(Request $request)
    {   
        $project = Project::find($request->get('project_id'));

        //if record is empty then display error page
        if(empty($project->id))
        {
            return abort(404, 'Not Found');
        }

        $project->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }

    public function getRefNo()
    {
        $setting = RefNoSetting::first();
        $ref_no = 1;
        if($setting->active == 'Y')
        {   
            $ref_no = $setting->start;
        }
        else
        {
            // $project = Project::first();
            $project = DB::table('projects')->orderBy('id', 'Desc')->first();
            if($project)
            {
                $ref_no = $project->ref_no + 1;
            }
            
        }

        return $ref_no;
    }

    public function calculateHours($project_logs)
    {   

        $project_id = $project_logs->first()->project_id;
        $datetime_now = Carbon::now();
        $date_now = Carbon::now()->format('Y-m-d');
        $time_now = Carbon::now()->format('H:i');
        $hr_now = explode(':', $time_now)[0];
        $start_now = Carbon::parse($date_now . ' 08:00');
        $end_now = Carbon::parse($date_now . ' 17:00');
        
        // if time now is between noon time then set into 1:00 pm
        if($hr_now == 12)
        {
            $datetime_now =  $new_remarks_datetime = Carbon::parse($date_now . ' 13:00');
        }

        $holidays = Holiday::all();
        $holidays_array = [];

        foreach($holidays as $i => $holiday)
        {
            $holidays_array[] = $holiday->holiday_date;
        }

        foreach($project_logs as $i => $log)
        {
            $num_rows = count($project_logs);

            $next_remarks_date = Carbon::parse($date_now)->format('Y-m-d');
            $next_remarks_day = Carbon::parse($date_now)->format('D');
            $next_remarks_time = $time_now;
            
            // if last index
            if($i < ($num_rows - 1))
            {
                $next_remarks_date = Carbon::parse($project_logs[$i+1]->remarks_date)->format('Y-m-d');
                $next_remarks_day = Carbon::parse($project_logs[$i+1]->remarks_date)->format('D');
                $next_remarks_time = $project_logs[$i+1]->remarks_time;
                $next_status = $project_logs[$i+1]->status;
            }

            
            $next_remarks_hr = explode(':', $next_remarks_time)[0];
            $next_remarks_datetime = Carbon::parse($next_remarks_date . ' ' . $next_remarks_time);
            $next_start_datetime = Carbon::parse($next_remarks_date . ' 08:00');
            $next_end_datetime = Carbon::parse($next_remarks_date . ' 17:00');

            $curr_remarks_date = Carbon::parse($log->remarks_date)->format('Y-m-d');
            $curr_remarks_day = Carbon::parse($log->remarks_date)->format('D');
            $curr_remarks_time = $log->remarks_time;
            $curr_remarks_hr = explode(':', $curr_remarks_time)[0];
            $curr_remarks_datetime = Carbon::parse($curr_remarks_date . ' ' . $curr_remarks_time);
            $curr_start_datetime = Carbon::parse($curr_remarks_date . ' 08:00');
            $curr_end_datetime = Carbon::parse($curr_remarks_date . ' 17:00');
            $curr_status = $log->status;
            $curr_days_diff = Carbon::parse($curr_remarks_date . ' 00:00')->diffInDays(Carbon::parse($next_remarks_date . ' 00:00'));

            $curr_mins = 0;

            // if new remarks time is between noon time then set into 1:00 pm
            if($next_remarks_hr == 12)
            {
                $next_remarks_datetime = Carbon::parse($next_remarks_date . ' 13:00');
            }

            // if last remarks time is between noon time then set into 1:00 pm
            if($curr_remarks_hr == 12)
            {
                $curr_remarks_datetime = Carbon::parse($curr_remarks_date . ' 12:00');
            }
 
            // calculate programming hours for every remarks log
            if($curr_days_diff == 0)
            {   
                // exclude sunday
                if($curr_remarks_day != 'Sun' && in_array($curr_remarks_date, $holidays_array) == false)
                {
                    $curr_mins = $curr_remarks_datetime->diffInMinutes($next_remarks_datetime);
                
                    // less 1 hour(noon break)
                    if($next_remarks_hr >= 12 && $curr_remarks_hr <= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }
                }
                
            }
            else
            {   
                // exclude sunday and holidays
                if($curr_remarks_day != 'Sun' && in_array($curr_remarks_date, $holidays_array) == false)
                {   
                    $curr_mins = $curr_remarks_datetime->diffInMinutes($curr_end_datetime);
                    // less 1 hour(noon break)
                    if($curr_remarks_hr <= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }

                }
                
                // if($curr_days_diff > 1)
                // {
                //     $curr_mins = $curr_mins + (($curr_days_diff - 1) * 480);    
                // }
                if($curr_days_diff > 1)
                {
                    for($x = 1; $curr_days_diff > $x; $x++)
                    {   
                        $date = Carbon::parse($curr_remarks_datetime->addDays($x))->format('Y-m-d');
                        $day = Carbon::parse($curr_remarks_datetime->addDays($x))->format('D');

                        // exclude sunday// exclude sunday and holidays
                        if($day != 'Sun' && in_array($date, $holidays_array) == false)
                        {
                            $curr_mins = $curr_mins + 480;
                        }
                    }  
                }
                
                // exclude sunday and holidays
                if($next_remarks_day != 'Sun' && in_array($next_remarks_date, $holidays_array) == false)
                {
                    $curr_mins = $curr_mins + $next_start_datetime->diffInMinutes($next_remarks_datetime); 

                    // less 1 hour(noon break)
                    if($next_remarks_hr >= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }
                }


                // $curr_mins = $curr_remarks_datetime->diffInMinutes($curr_end_datetime);
                // $curr_mins = $curr_mins + $next_start_datetime->diffInMinutes($next_remarks_datetime); 

                // if($curr_days_diff > 1)
                // {
                //     $curr_mins = $curr_mins + (($curr_days_diff - 1) * 480);    
                // }

                // // less 1 hour(noon break)
                // if($curr_remarks_hr <= 12)
                // {
                //     $curr_mins = $curr_mins - 60;
                // }

                // // less 1 hour(noon break)
                // if($next_remarks_hr >= 12)
                // {
                //     $curr_mins = $curr_mins - 60;
                // }
                
            }

            if($log->turnover == 'Y' || $log->status == 'Pending' || $log->status == 'Accepted')
            {
                $curr_mins = 0;
            }   

            ProjectLog::where('id', '=', $log->id)
                        ->update(['mins_diff' => $curr_mins]);

        }        

        $ongoing_mins =  ProjectLog::where('project_id', '=', $project_id)
                                   ->where('status', '=', 'Ongoing')
                                   ->sum('mins_diff');

        $validation_mins = ProjectLog::where('project_id', '=', $project_id)
                                     ->where('status', '=', 'For Validation')
                                     ->sum('mins_diff');

        
        $program_remainder = $ongoing_mins % 60;
        $program_hrs = intval($ongoing_mins / 60) + ($program_remainder / 100);
        
        $validation_remainder = $validation_mins % 60;
        $validate_hrs = intval($validation_mins / 60) + ($validation_remainder / 100);

        Project::where('id', '=', $project_id)
               ->update(['program_hrs' => $program_hrs, 'validate_hrs' => $validate_hrs]);
   
    }
    
}
