<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Project;
use App\ProjectLog;
use Carbon\Carbon;
use Validator;
use DB;
use App\Holiday;

class ProjectLogController extends Controller
{
    public function index($project_id)
    {   
        
        $project = DB::table('projects')
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
                             'projects.program_percent', 'projects.validation_percent')
                    ->where('projects.status', '!=', 'Cancelled')
                    ->where('projects.id', '=', $project_id)
                    ->orderBy('projects.id', 'Desc')
                    ->first();
        
        if($project->status != 'Accepted')
        {   
            $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                      ->orderBy('remarks_date', 'Asc')
                                      ->orderBy('remarks_time', 'Asc')
                                      ->get();
            // calculate hours difference per remarks log
            if(count($project_logs))
            {
                $this->calculateHours($project_logs); 
            }
            
        }
        
        $project_logs = ProjectLog::select('id', 'project_id',DB::raw("DATE_FORMAT(remarks_date, '%m/%d/%Y') as remarks_date"), 'remarks_time', 'remarks', 'status', 'turnover', 'mins_diff')
                                  ->where('project_id' , '=', $project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->get();

        return response()->json([
            'project' => $project, 
            'project_logs' => $project_logs,
        ], 200);

    }

    public function store(Request $request)
    {   
        // return Carbon::parse($request->get('remarks_date'). ' ' .$request->get('remarks_time'))->format('Y-m-d H:i');

        $rules = [
            'remarks_date.required' => 'Remarks Date is required',
            'remarks_date.date' => 'Please enter a valid date',
            'remarks_time.required' => 'Remarks Time is required',
            'remarks_time.time' => 'Please enter a valid time',
        ];

        $valid_fields = [
            'remarks_date' => 'required|date',
            'remarks_time' => 'required',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return respons()->json($validator->errors(), 200);
        }

        $project_id = $request->get('project_id');
      
        $status = $request->get('status');

        if($request->get('turnover') && $status == 'Ongoing')
        {   
            $status = 'For Validation';
        }
        else if($request->get('turnover') && $status == 'For Validation')
        {   
            $status = 'Ongoing';
        }
        
        $project_log = new ProjectLog();
        $project_log->project_id = $project_id;
        $project_log->remarks_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        $project_log->remarks_time = Carbon::parse($request->get('remarks_time'))->format('H:i');
        $project_log->remarks = $request->get('remarks');
        $project_log->status = $request->get('status');
        $project_log->turnover = $request->get('turnover');
        $project_log->save();


        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->get();

        if(count($project_logs))
        {   
            $first_ongoing_log = null;
            $first_validation_log = null;
            if($project_logs->where('status', '=', 'Ongoing')->first())
            {
                $first_ongoing_log = $project_logs->where('status', '=', 'Ongoing')->first()->remarks_date;
            }
            if($project_logs->where('status', '=', 'For Validation')->first())
            {
                $first_validation_log = $project_logs->where('status', '=', 'For Validation')->first()->remarks_date;
            }

            Project::where('id', '=', $project_id)
                ->update([
                    'status' => $status, 
                    'program_date' => $first_ongoing_log,
                    'validation_date' => $first_validation_log,
                ]);
        }
        
        

        // calculate hours difference per remarks log
        $this->calculateHours($project_logs);

        $project_log = DB::table('project_logs')
                         ->select('id', 'project_id',DB::raw("DATE_FORMAT(remarks_date, '%m/%d/%Y') as remarks_date"), 'remarks_time', 'remarks', 'status', 'turnover')
                         ->where('id', '=', $project_log->id)
                         ->first();

        return response()->json([
            'success' => 'Record has successfully added', 
            'project_log' => $project_log, 
            'status' => $status,
        ], 200);   
    }

    public function update(Request $request, $project_log_id)
    {   
        $rules = [
            'remarks_date.required' => 'Remarks Date is required',
            'remarks_date.date' => 'Please enter a valid date',
            'remarks_time.required' => 'Remarks Time is required',
            'remarks_time.time' => 'Please enter a valid time',
        ];

        $valid_fields = [
            'remarks_date' => 'required|date',
            'remarks_time' => 'required',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $project_id = $request->get('project_id');

        

        // project status
        $status = Project::find($project_id)->status;          

        $project_log = ProjectLog::find($project_log_id);
        $project_log->remarks_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        $project_log->remarks_time = Carbon::parse($request->get('remarks_time'))->format('H:i');
        $project_log->remarks = $request->get('remarks');
        $project_log->status = $request->get('status');
        $project_log->turnover = $request->get('turnover');
        $project_log->save();
        
        // last log
        $last_log = ProjectLog::where('project_id', '=', $project_id)
                                  ->orderBy('remarks_date', 'Desc')
                                  ->orderBy('remarks_time', 'Desc')
                                  ->orderBy('id', 'Desc')
                                  ->first();
        // last log turnover
        $last_log_turnover = ProjectLog::where('project_id', '=', $project_id)
                                  ->where('turnover', '=', 'Y')
                                  ->orderBy('remarks_date', 'Desc')
                                  ->orderBy('remarks_time', 'Desc')
                                  ->orderBy('id', 'Desc')
                                  ->first();
                                  
        if($last_log->status == 'For Validation' || $last_log->status == 'Ongoing')
        {
            if($last_log_turnover->status == 'For Validation')
            {
                $status = 'Ongoing';
            }
            elseif($last_log_turnover->status == 'Ongoing')
            {
                $status = 'For Validation';
            }
        }
        
        // update report status if latest log was updated
         Project::where('id', '=', $project_id)
         ->update(['status' => $status]);


        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->get();

        if(count($project_logs))
        {   
            $first_ongoing_log = null;
            $first_validation_log = null;
            
            if($project_logs->where('status', '=', 'Ongoing')->first())
            {
                $first_ongoing_log = $project_logs->where('status', '=', 'Ongoing')->first()->remarks_date;
            }
            if($project_logs->where('status', '=', 'For Validation')->first())
            {
                $first_validation_log = $project_logs->where('status', '=', 'For Validation')->first()->remarks_date;
            }

            Project::where('id', '=', $project_id)
                ->update([
                    'status' => $status, 
                    'program_date' => $first_ongoing_log,
                    'validation_date' => $first_validation_log,
                ]);
        }
                                  
        // calculate hours difference per remarks log
        $this->calculateHours($project_logs);

        $project_log = DB::table('project_logs')
                         ->select('id', 'project_id',DB::raw("DATE_FORMAT(remarks_date, '%m/%d/%Y') as remarks_date"), 'remarks_time', 'remarks', 'status', 'turnover', 'mins_diff')
                         ->where('id', '=', $project_log->id)
                         ->first();

        return response()->json([
            'success' => 'Record has been updated', 
            'project_log' => $project_log,
            'status' => $status,

        ], 200);   
    }

    public function delete(Request $request)
    {
        $project_log = ProjectLog::find($request->get('project_log_id'));
        $project_id = $project_log->project_id;
        
        //if record is empty then display error page
        if(empty($project_log->id))
        {
            return abort(404, 'Not Found');
        }

        $project_log->delete();
        
        // get latest data
        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->orderBy('id', 'Asc')
                                  ->get();

        $last_project_logs = ProjectLog::where('project_id', '=', $project_id)
                                  ->orderBy('remarks_date', 'Desc')
                                  ->orderBy('remarks_time', 'Desc')
                                  ->orderBy('id', 'Desc')
                                  ->first();

        if(count($project_logs) > 0)
        {
            // calculate hours difference per remarks log 
            $this->calculateHours($project_logs);
        }
        
        $status = "";
        if(count($project_logs) > 0)
        {
            if($last_project_logs->turnover)
            {
                if($last_project_logs->status == "Ongoing")
                {
                    $status = "For Validation";
                }
                elseif($last_project_logs->status == "For Validation")
                {
                    $status = "Ongoing";
                }
                else
                {
                    $status = $last_project_logs->status;
                }
            }
            else
            {   
                $status = $last_project_logs->status;   
            }

            $first_ongoing_log = $project_logs->where('status', '=', 'Ongoing')->first()->remarks_date;

            $first_validation_log = $project_logs->where('status', '=', 'For Validation')->first()->remarks_date;

            Project::where('id', '=', $project_id)
                    ->update([
                        'status' => $status, 
                        'program_date' => $first_ongoing_log,
                        'validation_date' => $first_validation_log,
                    ]);
        } 
        else
        {
            $status = "Pending"; 
        }
        
        Project::where('id', '=', $project_id)
               ->update(['status' => $status]);

        return response()->json(['success' => 'Record has been deleted', 'status' => $status,], 200);
    }

    public function project_turnover(Request $request)
    {   
        
        $project_id = $request->get('project_id');
        $project = Project::find($project_id);
        $project_logs = ProjectLog::where('project_id', '=', $project_id)->get();
        $status = $request->get('status');
        $remarks = "";

        if($project->status == 'For Validation')
        {
            $remarks = "Return to Programmer";
        }
        else
        {   
            $remarks = $request->get('status');
        }

        // if the current status has changed then turn over to programmer or validator
        if($project->status != $status && $project->status != 'Pending')
        {
            // create remarks log with turn over status
            $project_log = new ProjectLog();
            $project_log->project_id = $project_id;
            $project_log->remarks_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
            $project_log->remarks_time = Carbon::parse($request->get('remarks_time'))->format('H:i');
            $project_log->remarks = $remarks;
            $project_log->status = $project->status;

            if($project->status == 'For Validation' || $project->status == 'Ongoing')
            {   
                $project_log->turnover = 'Y';
            }
            
            $project_log->save();
        }

        // create new remarks log
        $project_log = new ProjectLog();
        $project_log->project_id = $project_id;
        $project_log->remarks_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        $project_log->remarks_time = Carbon::parse($request->get('remarks_time'))->format('H:i');
        $project_log->remarks = $request->get('remarks');
        $project_log->status = $request->get('status');
        $project_log->save();
        
        // get latest data
        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->orderBy('id', 'Asc')
                                  ->get();

        if(count($project_logs) > 0)
        {
            // calculate hours difference per remarks log 
            $this->calculateHours($project_logs);
        }

        Project::where('id', '=', $project_id)
                ->update(['status' => $request->get('status')]);

        return response()->json([
            'success' => 'Record has successfully added', 
            'project_log' => $project_log, 
            'status' => $status,
        ], 200);   
    }

    public function get_latest_log($project_id)
    {
        $project_log = ProjectLog::where('project_id', '=', $project_id)
                                  ->orderBy('remarks_date', 'Desc')
                                  ->orderBy('remarks_time', 'Desc')
                                  ->orderBy('id', 'Desc')
                                  ->first();
                                  
        //if record is empty then display error page
        if(empty($project_log->id))
        {
            return abort(404, 'Not Found');
        }

        return response()->json(['latest_log' => $project_log], 200);
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

            if($log->turnover == 'Y' || $log->status == 'Pending' || $log->status == 'Accepted')
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

    public function holidays()
    {
        $holidays = Holiday::all();
        $holidays_array = [];

        foreach($holidays as $i => $holiday)
        {
            $holidays_array[] = $holiday->holiday_date;
        }

        return $holidays_array;
    }

}
