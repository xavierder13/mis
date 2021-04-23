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
use App\Events\WebsocketEvent;
use Excel;
use App\Imports\ProjectsImport;

class ProjectController extends Controller
{   

    public function index()
    {  

        $projects = DB::table('projects')
                    ->leftJoin('departments', 'projects.department_id', '=','departments.id')
                    ->leftJoin('managers', 'departments.id', '=', 'managers.department_id')
                    ->leftJoin(DB::raw('users as programmers'), 'projects.programmer_id', '=', 'programmers.id')
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
                             'projects.type', 'projects.ideal_prog_hrs', 'projects.ideal_valid_hrs', 'projects.template_percent', 'projects.status',
                             'projects.program_percent', 'projects.validation_percent', 'program_hrs', 'validate_hrs')
                    // ->where('projects.status', '!=', 'Cancelled')
                    ->orderBy('project_id', 'Desc')
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

        $project_execution_hrs = [];
                           
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

            $project_execution_hrs[] = [
                'project_id' => $project->project_id,
                'execution_hrs' => $this->calculateHrsPerParamDate($project->project_id, $filter_date),
                'execution_hrs_tm' => $this->calculateHrsThisMonth($project->project_id, $filter_date),
            ];

        }    

        $projects = DB::table('projects')
                    ->leftJoin('departments', 'projects.department_id', '=','departments.id')
                    ->leftJoin('managers', 'departments.id', '=', 'managers.department_id')
                    ->leftJoin(DB::raw('users as programmers'), 'projects.programmer_id', '=', 'programmers.id')
                    ->leftJoin(DB::raw('users as validators'), 'projects.validator_id', '=', 'validators.id')
                    ->select(DB::raw('CASE WHEN projects.status = "For Validation" THEN "01" 
                                           WHEN projects.status = "Ongoing" THEN "02"
                                           WHEN projects.status = "Pending" THEN "03"
                                           WHEN projects.status = "Accepted" THEN "04"
                                      END as report_grp'),
                    DB::raw('projects.id as project_id'), 'projects.ref_no', 'projects.report_title', DB::raw('departments.name as department'), 
                             DB::raw('departments.id as department_id'), DB::raw('managers.name as manager'), 
                             DB::raw('programmers.name as programmer'), DB::raw('programmers.id as programmer_id'),
                             DB::raw('validators.name as validator'), DB::raw('validators.id as validator_id'),
                             DB::raw("DATE_FORMAT(projects.created_at, '%m/%d/%Y') as date_logged"),
                             DB::raw("DATE_FORMAT(projects.date_receive, '%m/%d/%Y') as date_received"),
                             DB::raw("DATE_FORMAT(projects.date_approve, '%m/%d/%Y') as date_approved"),
                             DB::raw("DATE_FORMAT(projects.program_date, '%m/%d/%Y') as program_date"),
                             DB::raw("DATE_FORMAT(projects.validation_date, '%m/%d/%Y') as validation_date"),
                             DB::raw("DATE_FORMAT(projects.accepted_date, '%m/%d/%Y') as accepted_date"),
                             'projects.type', 'projects.ideal_prog_hrs', 'projects.ideal_valid_hrs', 'projects.template_percent', 'projects.status',
                             'projects.program_percent', 'projects.validation_percent', 'program_hrs', 'validate_hrs')
                    ->where('projects.status', '!=', 'Cancelled')
                    // ->where('projects.ref_no', '=', '4')
                    ->where(function($query) use ($firstOfMonth, $filter_date) {
                        $query->whereBetween('projects.accepted_date', [$firstOfMonth, $filter_date])
                              ->orWhereNull('projects.accepted_date');
                    })
                    ->orderBy('report_grp', 'Desc')
                    ->orderBy('project_id', 'Desc')
                    ->get();
        
        $project_logs = DB::table('projects')
                    ->join('project_logs', 'projects.id', '=', 'project_logs.project_id')
                    ->select(DB::raw('projects.status as project_status, project_logs.*'))
                    ->where('projects.status', '!=', 'Cancelled')
                    ->where(function($query) use ($firstOfMonth, $filter_date) {
                              $query->whereBetween('accepted_date', [$firstOfMonth, $filter_date])
                                  ->orWhereNull('accepted_date');
                      })
                    ->orderBy('project_logs.remarks_date', 'Asc')
                    ->orderBy('project_logs.remarks_time', 'Asc')
                    ->orderBy('project_logs.id', 'Asc')
                    ->get();
        
        $departments = Department::with('managers')->get();

        $programmers = User::where('type', '=', 'Programmer')->get();

        $validators = User::where('type', '=', 'Validator')->get();

        return response()->json([
            'projects' => $projects, 
            'project_logs' => $project_logs,
            'departments' => $departments,
            'programmers' => $programmers,
            'validators' => $validators,
            'project_execution_hrs' => $project_execution_hrs,
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
        $project->ideal_prog_hrs = $request->get('ideal_prog_hrs');
        $project->ideal_valid_hrs = $request->get('ideal_valid_hrs');
        $project->template_percent = $request->get('template_percent');
        $project->type = $request->get('type');
        $project->status = "Pending";
        $project->save();

        if($project->id)
        {
            RefNoSetting::first()->update(['active' => 'N']);
        }

        $project = DB::table('projects')
                    ->leftJoin('departments', 'projects.department_id', '=','departments.id')
                    ->leftJoin('managers', 'departments.id', '=', 'managers.department_id')
                    ->leftJoin(DB::raw('users as programmers'), 'projects.programmer_id', '=', 'programmers.id')
                    ->leftJoin(DB::raw('users as validators'), 'projects.validator_id', '=', 'validators.id')
                    ->select('projects.id', 'projects.ref_no', 'projects.report_title', DB::raw('departments.name as department'), 
                             DB::raw('departments.id as department_id'), DB::raw('managers.name as manager'), 
                             DB::raw('programmers.name as programmer'), DB::raw('programmers.id as programmer_id'),
                             DB::raw('validators.name as validator'), DB::raw('validators.id as validator_id'),
                             DB::raw("DATE_FORMAT(projects.created_at, '%m/%d/%Y') as date_logged"),
                             DB::raw("DATE_FORMAT(projects.date_receive, '%m/%d/%Y') as date_received"),
                             DB::raw("DATE_FORMAT(projects.date_approve, '%m/%d/%Y') as date_approved"),
                             'projects.type', 'projects.ideal_prog_hrs', 'projects.ideal_valid_hrs', 'projects.template_percent', 'projects.status',
                             'projects.program_percent', 'projects.validation_percent',
                             'projects.program_date', 'projects.validation_date')
                    ->where('projects.id', '=', $project->id)
                    ->first();

        event(new WebsocketEvent(['action' => 'project-create']));

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
        $project->ideal_prog_hrs = $request->get('ideal_prog_hrs');
        $project->ideal_valid_hrs = $request->get('ideal_valid_hrs');
        $project->template_percent = $request->get('template_percent');
        $project->type = $request->get('type');
        $project->save();

        event(new WebsocketEvent(['action' => 'project-edit']));

        return response()->json(['success' => 'Record has been updated', 'project' => $project], 200);
    }

    public function update_status(Request $request)
    {   
        // return $request;
        $project = Project::find($request->get('project_id'));

        $project_logs = ProjectLog::where('project_id', '=', $project->id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->orderBy('id', 'Asc')
                                  ->get();

        $project->template_percent = $request->get('template_percent');

        // get the first log remarks of programming and validation date then update the program date and validation date
        if(count($project_logs))
        {   $first_ongoing_log = null;
            $first_validation_log = null;
            
            if($project_logs->where('status', '=', 'Ongoing')->first())
            {
                $project->program_date = $project_logs->where('status', '=', 'Ongoing')->first()->remarks_date;
            }
            if($project_logs->where('status', '=', 'For Validation')->first())
            {
                $project->validation_date = $project_logs->where('status', '=', 'For Validation')->first()->remarks_date;
            }     
        }
        $project->program_percent = $request->get('program_percent');
        $project->validation_percent = $request->get('validation_percent');
        $project->save();

        event(new WebsocketEvent(['action' => 'project-edit']));
        
        return response()->json(['success' => 'Record has been updated', 'project' => $project], 200);

    }

    public function endorse_project(Request $request)
    {   
        $rules = [
            'programmer_id.required' => 'Programmer ID is required',
            'programmer_id.integer' => 'Programmer ID must be an integer',
            'project_id.required' => 'Project ID is required',
            'project_id.integer' => 'Project ID must be an integer',
            'date.date' => 'Enter a valid date',
        ];

        $valid_fields = [
            'programmer_id' => 'required|integer',
            'project_id' => 'required|integer',
            'date' => 'date_format:Y-m-d',
        ];
        
        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        return response()->json(['success' => 'Record has been updated'], 200);
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

        event(new WebsocketEvent(['action' => 'project-delete']));

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
        $holidays = $this->holidays();
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

            // if new remarks time is between noon time then set into 8:00 am
            if($next_remarks_hr < 8)
            {
                $next_remarks_datetime = Carbon::parse($next_remarks_date . ' 08:00');
            }

            // if new remarks time is between noon time then set into 1:00 pm
            if($next_remarks_hr == 12)
            {
                $next_remarks_datetime = Carbon::parse($next_remarks_date . ' 13:00');
            }
            
            // if last remarks time is 5pm and beyond then set into 5:00 pm
            if($next_remarks_hr >= 17)
            {
                $next_remarks_datetime = Carbon::parse($next_remarks_date . ' 17:00');
            }

            // if last remarks time is between noon time then set into 1:00 pm
            if($curr_remarks_hr == 12)
            {
                $curr_remarks_datetime = Carbon::parse($curr_remarks_date . ' 12:00');
            }
            
            // if last remarks time is 5pm and beyond then set into 5:00 pm
            if($curr_remarks_hr >= 17)
            {
                $curr_remarks_datetime = Carbon::parse($curr_remarks_date . ' 17:00');
            }
 
            // calculate programming hours for every remarks log
            if($curr_days_diff == 0)
            {   
                // exclude sunday
                if($curr_remarks_day != 'Sun' && in_array($curr_remarks_date, $holidays) == false && $next_remarks_datetime > $curr_remarks_datetime)
                {
                    $curr_mins = $curr_remarks_datetime->diffInMinutes($next_remarks_datetime);
                
                    // less 1 hour(noon break)
                    if($next_remarks_hr >= 12 && $curr_remarks_hr <= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }
                }
                
            }
            else if($curr_days_diff > 0)
            {   
                // exclude sunday and holidays
                if($curr_remarks_day != 'Sun' && in_array($curr_remarks_date, $holidays) == false)
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
                        $date = Carbon::parse($curr_remarks_date)->addDays($x)->format('Y-m-d');
                        $day = Carbon::parse($date)->format('D');

                        // exclude sunday// exclude sunday and holidays
                        if($day != 'Sun' && in_array($date, $holidays) == false)
                        {
                            $curr_mins = $curr_mins + 480;
                        }
                    }  
                }
                
                // exclude sunday and holidays
                if($next_remarks_day != 'Sun' && in_array($next_remarks_date, $holidays) == false)
                {
                    $curr_mins = $curr_mins + $next_start_datetime->diffInMinutes($next_remarks_datetime); 

                    // less 1 hour(noon break)
                    if($next_remarks_hr >= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }
                }
                
            }

            if($log->turnover == 'Y' || $log->status == 'Pending' || $log->status == 'Accepted' || $log->status == 'Cancelled')
            {
                $curr_mins = 0;
            }   

            ProjectLog::where('id', '=', $log->id)
                      ->update(['mins_diff' => $curr_mins]);            

        }        

        $ongoing_mins = $project_logs->where('status', '=', 'Ongoing')
                                     ->sum('mins_diff');

        $validation_mins = $project_logs->where('status', '=', 'For Validation')
                                        ->sum('mins_diff');

        
        $program_remainder = $ongoing_mins % 60;
        $program_hrs = intval($ongoing_mins / 60) + ($program_remainder / 100);
        
        $validation_remainder = $validation_mins % 60;
        $validate_hrs = intval($validation_mins / 60) + ($validation_remainder / 100);

        Project::where('id', '=', $project_id)
               ->update(['program_hrs' => $program_hrs, 'validate_hrs' => $validate_hrs]);
   
    }

    public function calculateHrsPerParamDate($project_id, $filter_date)
    {   
        $holidays = $this->holidays();
        $system_date = Carbon::now()->format('Y-m-d');
        $time_now = Carbon::now()->format('H:i');

        // if filter_date is previous date then set time to 5:00 pm
        if(Carbon::parse($system_date . ' 00:00') > Carbon::parse($filter_date . ' 00:00'))
        {
            $time_now = '17:00';
        }

        $date_now = Carbon::parse($filter_date)->format('Y-m-d');
        $datetime_now = Carbon::parse($filter_date . ' ' . $time_now);
        $hr_now = explode(':', $time_now)[0];
        $start_now = Carbon::parse($date_now . ' 08:00');
        $end_now = Carbon::parse($date_now . ' 17:00');

        $curr_mins = 0;

        // if time now is between noon time then set into 1:00 pm
        if($hr_now == 12)
        {
            $datetime_now =  $new_remarks_datetime = Carbon::parse($date_now . ' 13:00');
        }

        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                ->where('remarks_date', '<=', $filter_date)
                                ->orderBy('remarks_date', 'desc')
                                ->orderBy('remarks_time', 'desc')
                                ->orderBy('id', 'desc')
                                ->get(); 

        $log = $project_logs->first();
                         
        // if logs has no data
        if(!$log)
        {
            return [
                'program_hrs' => 0, 
                'validate_hrs' => 0,
                'program_last_log_hrs' => 0, 
                'validate_last_log_hrs' => 0,
                'ongoing_last_log_mins' => 0,
                'validation_last_log_mins' => 0,
                'log' => $log,
            ];
        }

        $next_remarks_time = $time_now;
        $next_remarks_date = Carbon::parse($date_now)->format('Y-m-d');
        $next_remarks_datetime = Carbon::parse($next_remarks_date . ' ' . $next_remarks_time);
        $next_remarks_day = Carbon::parse($date_now)->format('D');
        $next_remarks_hr = explode(':', $next_remarks_time)[0];
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

        // if new remarks time is between noon time then set into 8:00 am
        if($next_remarks_hr < 8)
        {
            $next_remarks_datetime = Carbon::parse($next_remarks_date . ' 08:00');
        }

        // if new remarks time is between noon time then set into 1:00 pm
        if($next_remarks_hr == 12)
        {
            $next_remarks_datetime = Carbon::parse($next_remarks_date . ' 13:00');
        }
        
        // if last remarks time is 5pm and beyond then set into 5:00 pm
        if($next_remarks_hr >= 17)
        {
            $next_remarks_datetime = Carbon::parse($next_remarks_date . ' 17:00');
        }

        // if last remarks time is between noon time then set into 1:00 pm
        if($curr_remarks_hr == 12)
        {
            $curr_remarks_datetime = Carbon::parse($curr_remarks_date . ' 12:00');
        }
        
        // if last remarks time is 5pm and beyond then set into 5:00 pm
        if($curr_remarks_hr >= 17)
        {
            $curr_remarks_datetime = Carbon::parse($curr_remarks_date . ' 17:00');
        }

        // if the last remarks log is not turnover
        if(!$log->turnover || $log->status == 'Ongoing' || $log->status == 'For Validation')
        {
            // calculate programming hours for every remarks log
            if($curr_days_diff == 0)
            {   
                // exclude sunday
                if($curr_remarks_day != 'Sun' && in_array($curr_remarks_date, $holidays) == false && $next_remarks_datetime > $curr_remarks_datetime)
                {
                    $curr_mins = $curr_remarks_datetime->diffInMinutes($next_remarks_datetime);
                
                    // less 1 hour(noon break)
                    if($next_remarks_hr >= 12 && $curr_remarks_hr <= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }
                }
                
            }
            else if($curr_days_diff > 0)
            {   
                
                // exclude sunday and holidays
                if($curr_remarks_day != 'Sun' && in_array($curr_remarks_date, $holidays) == false)
                {   
                    $curr_mins = $curr_remarks_datetime->diffInMinutes($curr_end_datetime);
                    // less 1 hour(noon break)
                    if($curr_remarks_hr <= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }

                }
                
                if($curr_days_diff > 1)
                {   
                    for($x = 1; $curr_days_diff > $x; $x++)
                    {   
                        $date = Carbon::parse($curr_remarks_date)->addDays($x)->format('Y-m-d');
                        $day = Carbon::parse($date)->format('D');
                        // exclude sunday// exclude sunday and holidays
                        if($day != 'Sun' && in_array($date, $holidays) == false)
                        {   
                            
                            $curr_mins = $curr_mins + 480;
                        }
                    }  
                }
                
                // exclude sunday and holidays
                if($next_remarks_day != 'Sun' && in_array($next_remarks_date, $holidays) == false)
                {
                    
                    $curr_mins = $curr_mins + $next_start_datetime->diffInMinutes($next_remarks_datetime); 

                    // less 1 hour(noon break)
                    if($next_remarks_hr >= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }
                }  
            }

            if($log->turnover == 'Y' || $log->status == 'Pending' || $log->status == 'Accepted')
            {
                $curr_mins = 0;
            } 

        }

        $program_hrs = 0;
        $validate_hrs = 0;
        $program_last_log_hrs = 0;
        $validate_last_log_hrs = 0;

        $ongoing_last_log_mins = 0;
        $validation_last_log_mins = 0;

        $ongoing_mins =  $project_logs->where('status', '=', 'Ongoing')
                                      ->sum('mins_diff');

        $validation_mins =  $project_logs->where('status', '=', 'For Validation')
                                         ->sum('mins_diff');

        if($log->status == 'Ongoing')
        {   
            $ongoing_mins = $project_logs->where('id', '!=', $log->id)
                                         ->where('status', '=', 'Ongoing')
                                         ->sum('mins_diff');
            
            $ongoing_mins = $ongoing_mins + $curr_mins;
            $ongoing_last_log_mins = $curr_mins;


        }
        else if($log->status == 'For Validation')
        {   
            $validation_mins =  $project_logs->where('id', '!=', $log->id)
                                             ->where('status', '=', 'For Validation')
                                             ->sum('mins_diff');

            $validation_mins = $validation_mins + $curr_mins;
            $validation_last_log_mins = $curr_mins;
        }
        
        $program_remainder = $ongoing_mins % 60;
        $program_hrs = intval($ongoing_mins / 60) + ($program_remainder / 100);

        $program_last_log_remainder = $ongoing_last_log_mins % 60;
        $program_last_log_hrs = intval($ongoing_last_log_mins / 60) + ($program_last_log_remainder / 100);
        
        $validation_remainder = $validation_mins % 60;
        $validate_hrs = intval($validation_mins / 60) + ($validation_remainder / 100);

        $validation_last_log_remainder = $validation_last_log_mins % 60;
        $validate_last_log_hrs = intval($validation_last_log_mins / 60) + ($validation_last_log_remainder / 100);

        return [
            'program_hrs' => $program_hrs, 
            'validate_hrs' => $validate_hrs,
            'program_last_log_hrs' => $program_last_log_hrs, 
            'validate_last_log_hrs' => $validate_last_log_hrs,
            'ongoing_last_log_mins' => $ongoing_last_log_mins,
            'validation_last_log_mins' => $validation_last_log_mins,
            'log' => $log,
            $time_now
        ];

    }

    public function calculateHrsThisMonth($project_id, $filter_date)
    {   
        $holidays = $this->holidays();

        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->get();

        $program_hrs = 0;
        $validate_hrs = 0;
        $ongoing_last_log_mins = 0;
        $validation_last_log_mins = 0;
        $last_log_id = null;

        $last_log = $this->calculateHrsPerParamDate($project_id, $filter_date);

        $ongoing_last_log_mins = $last_log['ongoing_last_log_mins'];
        $validation_last_log_mins = $last_log['validation_last_log_mins'];
        $last_log_id = $last_log['log'] ? $last_log['log']['id'] : null;
        $last_log_status = $last_log['log'] ? $last_log['log']['status'] : null;
     
        $log_rows = count($project_logs);

        $system_date = Carbon::now()->format('Y-m-d');
        $firstOfMonth = Carbon::parse($filter_date)->firstOfMonth()->format('Y-m-d');
        $lastOfMonth = Carbon::parse($filter_date)->lastOfMonth()->format('Y-m-d');
        $firstPrevMonth = Carbon::parse($firstOfMonth)->addDays(-1)->firstOfMonth()->format('Y-m-d');
        $lastPrevMonth = Carbon::parse($firstOfMonth)->addDays(-1)->format('Y-m-d');
        $time_now = Carbon::now()->format('H:i');

        // if filter_date is previous date then set time to 5:00 pm
        if(Carbon::parse($system_date . ' 00:00') > Carbon::parse($filter_date . ' 00:00'))
        {
            $time_now = '17:00';
        }

        $date_now = Carbon::parse($filter_date)->format('Y-m-d');
        $datetime_now = Carbon::parse($filter_date . ' ' . $time_now);
        $hr_now = explode(':', $time_now)[0];
        $start_now = Carbon::parse($date_now . ' 08:00');
        $end_now = Carbon::parse($date_now . ' 17:00');

        $curr_mins = 0;
            
        // if time now is between noon time then set into 1:00 pm
        if($hr_now == 12)
        {
            $datetime_now =  $new_remarks_datetime = Carbon::parse($date_now . ' 13:00');
        }

        // logs as of previous month
        $project_logs_pm = ProjectLog::where('project_id', '=', $project_id)
                                   ->where('remarks_date', '<=', $lastPrevMonth)
                                   ->orderBy('remarks_date', 'asc')
                                   ->orderBy('remarks_time', 'asc')
                                   ->orderBy('id', 'asc')
                                   ->get();

        // logs this month
        $project_logs_tm = ProjectLog::where('project_id', '=', $project_id)
                                   ->where('remarks_date', '<=', $filter_date)
                                   ->where('remarks_date', '>=', $firstOfMonth)
                                   ->orderBy('remarks_date', 'asc')
                                   ->orderBy('remarks_time', 'asc')
                                   ->orderBy('id', 'asc')
                                   ->get();

        $log_rows = count($project_logs);

        // count logs previous month
        $log_rows_pm = count($project_logs_pm); 

        // count logs this month
        $log_rows_tm = count($project_logs_tm);   

        if(!$log_rows)
        {
            return ['program_hrs' => 0, 'validate_hrs' => 0];
        }

        $first_log = $project_logs->first();
        $first_log_tm = $project_logs_tm->first();

        $first_log_days_diff = 0;
        $curr_remarks_time = "08:00";
        $curr_remarks_date = $firstOfMonth;
        $first_log_tm_status = $last_log_status;

        if($first_log_tm)
        {
            // get the days difference of first log and first log this month
            $first_log_days_diff = Carbon::parse($first_log->remarks_date . ' 00:00')->diffInDays(Carbon::parse($first_log_tm->remarks_date . ' 00:00'));
            $curr_remarks_time = $first_log_tm->remarks_time;
            $curr_remarks_date = Carbon::parse($first_log_tm->remarks_date)->format('Y-m-d');
            $first_log_tm_status = $first_log_tm->status;
        }
        
        $next_remarks_date = Carbon::parse($date_now)->format('Y-m-d');
        $next_remarks_time = $time_now;
        
        // if first log and first log of the month
        if($first_log_days_diff > 0)
        {
            $curr_remarks_time = "08:00";
            $curr_remarks_date = Carbon::parse($firstOfMonth)->format('Y-m-d');
            $next_remarks_date = Carbon::parse($first_log_tm->remarks_date)->format('Y-m-d');
            $next_remarks_time = $first_log_tm->remarks_time;

            // if previous months has data
            if($log_rows_pm)
            {
                // last log previous month
                $last_log_pm = $project_logs_pm[$log_rows_pm - 1];

                if($last_log_pm->turnover == 'Y')
                {
                    $curr_remarks_time = $first_log_tm->remarks_time;
                    $curr_remarks_date = Carbon::parse($first_log_tm->remarks_date)->format('Y-m-d');
                    $next_remarks_date = Carbon::parse($first_log_tm->remarks_date)->format('Y-m-d');
                    $next_remarks_time = $first_log_tm->remarks_time;
                }
            }

        }

        // if logs this month has no data
        if(!$log_rows_tm)
        {
            $curr_remarks_time = "08:00";
            $curr_remarks_date = Carbon::parse($firstOfMonth)->format('Y-m-d');
            $next_remarks_date = Carbon::parse($filter_date)->format('Y-m-d');
            $next_remarks_time = $time_now;
        }

        $next_remarks_datetime = Carbon::parse($next_remarks_date . ' ' . $next_remarks_time);
        $next_remarks_day = Carbon::parse($next_remarks_date)->format('D');
        $next_remarks_hr = explode(':', $next_remarks_time)[0];
        $next_start_datetime = Carbon::parse($next_remarks_date . ' 08:00');
        $next_end_datetime = Carbon::parse($next_remarks_date . ' 17:00');

        $curr_remarks_day = Carbon::parse($curr_remarks_date)->format('D');
        $curr_remarks_hr = explode(':', $curr_remarks_time)[0];
        $curr_remarks_datetime = Carbon::parse($curr_remarks_date . ' ' . $curr_remarks_time);
        $curr_start_datetime = Carbon::parse($curr_remarks_date . ' 08:00');
        $curr_end_datetime = Carbon::parse($curr_remarks_date . ' 17:00');
        $curr_status = $first_log->status;
        $curr_days_diff = Carbon::parse($curr_remarks_date . ' 00:00')->diffInDays(Carbon::parse($next_remarks_date . ' 00:00'));
        
        // if new remarks time is between noon time then set into 8:00 am
        if($next_remarks_hr < 8)
        {
            $next_remarks_datetime = Carbon::parse($next_remarks_date . ' 08:00');
        }

        // if new remarks time is between noon time then set into 1:00 pm
        if($next_remarks_hr == 12)
        {
            $next_remarks_datetime = Carbon::parse($next_remarks_date . ' 13:00');
        }
        
        // if last remarks time is 5pm and beyond then set into 5:00 pm
        if($next_remarks_hr >= 17)
        {
            $next_remarks_datetime = Carbon::parse($next_remarks_date . ' 17:00');
        }

        // if last remarks time is between noon time then set into 1:00 pm
        if($curr_remarks_hr == 12)
        {
            $curr_remarks_datetime = Carbon::parse($curr_remarks_date . ' 12:00');
        }
        
        // if last remarks time is 5pm and beyond then set into 5:00 pm
        if($curr_remarks_hr == 12)
        {
            $curr_remarks_datetime = Carbon::parse($curr_remarks_date . ' 17:00');
        }

        if($first_log_tm_status == 'Ongoing' || $first_log_tm_status == 'For Validation')
        {
            // calculate programming hours for every remarks log
            if($curr_days_diff == 0)
            {   
                // exclude sunday
                if($curr_remarks_day != 'Sun' && in_array($curr_remarks_date, $holidays) == false && $next_remarks_datetime > $curr_remarks_datetime)
                {
                    $curr_mins = $curr_remarks_datetime->diffInMinutes($next_remarks_datetime);
                
                    // less 1 hour(noon break)
                    if($next_remarks_hr >= 12 && $curr_remarks_hr <= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }
                }    
            }
            else if($curr_days_diff > 0)
            {     
                // exclude sunday and holidays
                if($curr_remarks_day != 'Sun' && in_array($curr_remarks_date, $holidays) == false)
                {   
                    $curr_mins = $curr_remarks_datetime->diffInMinutes($curr_end_datetime);
                    // less 1 hour(noon break)
                    if($curr_remarks_hr <= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }
                }
                
                if($curr_days_diff > 1)
                {   
                    for($x = 1; $curr_days_diff > $x; $x++)
                    {   
                        $date = Carbon::parse($curr_remarks_date)->addDays($x)->format('Y-m-d');
                        $day = Carbon::parse($date)->format('D');
                        // exclude sunday// exclude sunday and holidays
                        if($day != 'Sun' && in_array($date, $holidays) == false)
                        {   
                            $curr_mins = $curr_mins + 480;
                        }
                    }  
                }
                
                // exclude sunday and holidays
                if($next_remarks_day != 'Sun' && in_array($next_remarks_date, $holidays) == false)
                {
                    
                    $curr_mins = $curr_mins + $next_start_datetime->diffInMinutes($next_remarks_datetime); 

                    // less 1 hour(noon break)
                    if($next_remarks_hr >= 12)
                    {
                        $curr_mins = $curr_mins - 60;
                    }
                }

            }

            if($first_log_tm_status == 'Pending' || $first_log_tm_status == 'Accepted')
            {
                $curr_mins = 0;
            } 

        }
        
        // sum mins_diff except last log of the month
        $ongoing_mins = $project_logs_tm->where('id', '!=', $last_log_id)
                                        ->where('status', '=', 'Ongoing')
                                        ->sum('mins_diff');
        // sum mins_diff except last log of the month
        $validation_mins =  $project_logs_tm->where('id', '!=', $last_log_id)
                                            ->where('status', '=', 'For Validation')
                                            ->sum('mins_diff');

        if($first_log_tm_status == 'Ongoing')
        {   
            $ongoing_mins = $ongoing_mins + $curr_mins;
        }
        else if($first_log_tm_status == 'For Validation')
        {   
            $validation_mins = $validation_mins + $curr_mins;
        } 
        
        // if this month has logs then get the sum of all mins_diff field
        if($log_rows_tm)
        {
            $ongoing_mins = $ongoing_mins + $ongoing_last_log_mins;
            $validation_mins = $validation_mins + $validation_last_log_mins;
        }
        
        $program_remainder = $ongoing_mins % 60;
        $program_hrs = intval($ongoing_mins / 60) + ($program_remainder / 100);
        
        $validation_remainder = $validation_mins % 60;
        $validate_hrs = intval($validation_mins / 60) + ($validation_remainder / 100);
        
        return [
            'program_hrs' => $program_hrs, 
            'validate_hrs' => $validate_hrs, 
            'curr_mins' => $curr_mins,
            $time_now
        ];
                                  
    }

    public function holidays()
    {
        $holidays = Holiday::all();
        $holidays = [];

        foreach($holidays as $i => $holiday)
        {
            $holidays[] = $holiday->holiday_date;
        }

        return $holidays;
    }

    public function import_project(Request $request) 
    {   

        try {
            $file_extension = '';
            $path = '';
            if($request->file('file'))
            {   
                $path = $request->file('file')->getRealPath();
                $file_extension = $request->file('file')->getClientOriginalExtension();
            }

            $validator = Validator::make(
                [
                    'file' => strtolower($file_extension),
                ],
                [
                    'file' => 'required|in:xlsx,xls,',
                ], 
                [
                    'file.required' => 'File is required',
                    'file.in' => 'File type must be xlsx, xls or ods',
                ]
            );  
            
            if($validator->fails())
            {
                return response()->json($validator->errors(), 200);
            }
    
            if ($request->file('file')) {
                    
                // $array = Excel::toArray(new ProjectsImport, $request->file('file'));
                $collection = Excel::toCollection(new ProjectsImport, $request->file('file'))[0];
                $ctr_collection = count($collection);
                $columns = [
                    'ref_no', 
                    'report_title', 
                    'programmer_id', 
                    'validator_id', 
                    'date_receive', 
                    'date_approve', 
                    'type', 
                    'department_id', 
                    'ideal_prog_hrs', 
                    'ideal_valid_hrs', 
                    'status', 
                    'template_percent', 
                    'program_percent', 
                    'validation_percent', 
                    'program_date', 
                    'validation_date', 
                    'program_hrs', 
                    'validate_hrs', 
                    'accepted_date'
                ]; 

                $collection_errors = [];
                $collection_column_errors = [];
                $fields = [];    

                if($ctr_collection > 1)
                {   
                    for($x=0; $ctr_collection > $x; $x++)
                    {   
                        for($y=0; count($collection[$x]) > $y; $y++)
                        {
                            if($x == 0)
                            {
                               if($collection[$x][$y] != $columns[$y])
                               {
                                    $collection_column_errors[] =  'Invalid column name "'. $collection[$x][$y]. '"';
                               } 
                            }  
                            else
                            {   
                                $fields[$x - 1][$columns[$y]] = $collection[$x][$y];
                            }
                        }
                        
                        // if column names did not match
                        if(count($collection_column_errors))
                        {
                            return response()->json(['error_column_name' => $collection_column_errors], 200);
                        }

                    } 

                    $rules = [
                        '*.ref_no.required' => 'Reference No. is required',
                        '*.report_title.required' => 'Report Title is required',
                        '*.programmer_id.required' => 'Programmer is required',
                        '*.programmer_id.integer' => 'Programmer must be an integer',
                        '*.department_id.required' => 'Department is required',
                        '*.department_id.integer' => 'Department must be an integer',
                        '*.programmer_id.required' => 'Programmer is required',
                        '*.programmer_id.integer' => 'Programmer must be an integer',
                        '*.validator_id.integer' => 'Validator must be an integer',
                        '*.date_receive.date_format' => 'Invalid date. Format: (YYYY-MM-DD)',
                        '*.date_approve.date_format' => 'Invalid date. Format: (YYYY-MM-DD)',
                        '*.type.required' => 'Report Type is required',
                        '*.type.in' => 'Value is invalid, must be on the ff. values ("New", "Change Order")',
                        '*.department_id.integer' => 'Department must be an integer',
                        '*.ideal_prog_hrs.numeric' => 'Ideal Prog. Hrs must be numeric',
                        '*.ideal_prog_hrs.between' => 'Ideal Prog. Hrs must be 0 or above',
                        '*.ideal_valid_hrs.numeric' => 'Ideal Valid. Hrs. must be numeric',
                        '*.ideal_valid_hrs.between' => 'Ideal Valid. Hrs. must be 0 or above',
                        '*.status.required' => 'Status is required',
                        '*.status.in' => 'Value is invalid, must be on the ff. values ("Ongoing", "For Validation", "Accepted", "Pending", "Cancelled")',
                        '*.template_percent.numeric' => 'Template Percentage must be numeric',
                        '*.template_percent.between' => 'Template Percentage must be 0 or above',
                        '*.program_percent.numeric' => 'Programming Percentage must be numeric',
                        '*.program_percent.between' => 'Programming Percentage must be 0 or above',
                        '*.validation_percent.numeric' => 'Validation Percentage must be numeric',
                        '*.validation_percent.between' => 'Validation Percentage must be 0 or above',
                        '*.program_date.date_format' => 'Invalid date. Format: (YYYY-MM-DD)',
                        '*.validation_date.date_format' => 'Invalid date. Format: (YYYY-MM-DD)',
                        '*.program_hrs.numeric' => 'Programming Hrs must be numeric',
                        '*.program_hrs.between' => 'Programming Hrs must be 0 or above',
                        '*.validate_hrs.numeric' => 'Validation Hrs must be numeric',
                        '*.validate_hrs.between' => 'Validation Hrs must be 0 or above',
                        '*.accepted_date.date_format' => 'Invalid date. Format: (YYYY-MM-DD)',
                    ];
            
                    $valid_fields = [
                        '*.ref_no' => 'required',
                        '*.report_title' => 'required',
                        '*.programmer_id' => 'required|integer',
                        '*.department_id' => 'required|integer',
                        '*.programmer_id' => 'required|integer',
                        '*.validator_id' => 'nullable|integer',
                        '*.date_receive' => 'nullable|date_format:Y-m-d',
                        '*.date_approve' => 'nullable|date_format:Y-m-d',
                        '*.type' => 'required|in:"New", "Change Order"',
                        '*.department_id' => 'nullable|integer',
                        '*.ideal_prog_hrs' => 'nullable|numeric|between:0,9999999.99',
                        '*.ideal_valid_hrs' => 'nullable|numeric|between:0,9999999.99',
                        '*.status' => 'required|in:"Ongoing", "For Validation", "Accepted", "Pending", "Cancelled"',
                        '*.template_percent' => 'nullable|numeric|between:0,9999999.99',
                        '*.program_percent' => 'nullable|numeric|between:0,9999999.99',
                        '*.validation_percent' => 'nullable|numeric|between:0,9999999.99',
                        '*.program_date' => 'nullable|date_format:Y-m-d',
                        '*.validation_date' => 'nullable|date_format:Y-m-d',
                        '*.program_hrs' => 'nullable|numeric|between:0,9999999.99',
                        '*.validate_hrs' => 'nullable|numeric|between:0,9999999.99',
                        '*.accepted_date' => 'nullable|date_format:Y-m-d',
                    ];
                    
                    $validator = Validator::make($fields, $valid_fields, $rules);  
            
                    if($validator->fails())
                    {
                        $collection_errors =  $validator->errors();
                    }
                    
                }
                else
                {   
                    // if file has no row data
                    return response()->json(['error_empty' => 'File is Empty'], 200);
                }
                
                // if row data has errors
                if(count($collection_errors))
                {
                    return response()->json(['error_row_data' => $collection_errors, 'field_values' => $fields], 200);
                }
                else
                {   
                    // import excel file
                    Excel::import(new ProjectsImport, $path);
                    event(new WebsocketEvent(['action' => 'import-project']));
                }
                    
                return response()->json(['success' => 'Record has successfully imported'], 200);
            }
            else
            {
                return response()->json(['error_empty' => 'File is empty'], 200);
            }
          
          } catch (\Exception $e) {
          
              return response()->json(['error' => $e->getMessage()], 200);
          }
        
    }
    
}
