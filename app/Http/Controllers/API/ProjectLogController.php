<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Project;
use App\ProjectLog;
use App\EndorseProject;
use Carbon\Carbon;
use Validator;
use DB;
use App\Holiday;
use Excel;
use App\Imports\ProjectLogsImport;
use App\Events\WebsocketEvent;

class ProjectLogController extends Controller
{
    public function index(Request $request, $project_id)
    {   
        $endorse_project_id = $request->get('endorse_project_id');
        
        $project = DB::table('projects')
                    ->leftJoin('departments', 'projects.department_id', '=','departments.id')
                    ->leftJoin('managers', 'departments.id', '=', 'managers.department_id')
                    ->leftJoin(DB::raw('users as programmers'), 'projects.programmer_id', '=', 'programmers.id')
                    ->leftJoin(DB::raw('users as validators'), 'projects.validator_id', '=', 'validators.id')
                    ->select(DB::raw('projects.id as project_id'), 'projects.ref_no', 'projects.report_title', DB::raw('departments.name as department'), 
                             DB::raw('departments.id as department_id'), DB::raw('managers.name as manager'), 
                             DB::raw('programmers.name as programmer'), DB::raw('programmers.id as programmer_id'),
                             DB::raw('validators.name as validator'), DB::raw('validators.id as validator_id'),
                             DB::raw("DATE_FORMAT(projects.created_at, '%m/%d/%Y') as date_logged"),
                             DB::raw("DATE_FORMAT(projects.date_receive, '%m/%d/%Y') as date_receive"),
                             DB::raw("DATE_FORMAT(projects.date_approve, '%m/%d/%Y') as date_approve"),
                             DB::raw("DATE_FORMAT(projects.program_date, '%m/%d/%Y') as program_date"),
                             DB::raw("DATE_FORMAT(projects.validation_date, '%m/%d/%Y') as validation_date"),
                             'projects.type', 'projects.ideal_prog_hrs', 'projects.ideal_valid_hrs', 'projects.template_percent', 'projects.status',
                             'projects.program_percent', 'projects.validation_percent', 'projects.program_hrs', 'projects.validate_hrs')
                    ->where('projects.status', '!=', 'Cancelled')
                    ->where('projects.id', '=', $project_id)
                    ->orderBy('projects.id', 'Desc')
                    ->first();
        
        if($project->status != 'Accepted')
        {   
            $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                      ->where('endorse_project_id', '=', $endorse_project_id)
                                      ->orderBy('remarks_date', 'Asc')
                                      ->orderBy('remarks_time', 'Asc')
                                      ->get();
            // calculate hours difference per remarks log
            if(count($project_logs))
            {
                $this->calculateHours($project_logs); 
            }   
        }
        
        $project_logs = ProjectLog::select('id', 'project_id',DB::raw("DATE_FORMAT(remarks_date, '%m/%d/%Y') as remarks_date"), DB::raw("TIME_FORMAT(remarks_time, '%H:%i')remarks_time"), 'remarks', 'status', 'turnover', 'mins_diff')
                                  ->where('project_id' , '=', $project_id)
                                  ->where('endorse_project_id', '=', $endorse_project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->get();
        
        return response()->json([
            'project' => $project, 
            'project_logs' => $project_logs,
            'endorse_project_id' => $endorse_project_id,
        ], 200);

    }

    public function store(Request $request)
    {   
        // return Carbon::parse($request->get('remarks_date'). ' ' .$request->get('remarks_time'))->format('Y-m-d H:i');

        $rules = [
            'programmer_id.required' => 'Programmer ID is required',
            'programmer_id.integer' => 'Programmer ID must be an integer',
            'remarks_date.required' => 'Remarks date is required',
            'remarks_date.date_format' => 'Invalid date. Format: (YYYY-MM-DD)',
            'remarks_time.required' => 'Remarks time is required',
            'remarks_time.date_format' => 'Invalid time. Format: (H:i)',
        ];

        $valid_fields = [
            'project_id' => 'required|integer',
            'remarks_date' => 'required|date_format:Y-m-d',
            'remarks_time' => 'required|date_format:H:i',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $project_id = $request->get('project_id');
        $project = Project::find($project_id);
        $endorse_project_id = $request->get('endorse_project_id');

        $status = $request->get('status');
        
        $project_log = new ProjectLog();
        $project_log->project_id = $project_id;
        $project_log->endorse_project_id = $endorse_project_id;
        $project_log->remarks_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        $project_log->remarks_time = Carbon::parse($request->get('remarks_time'))->format('H:i');
        $project_log->remarks = $request->get('remarks');
        $project_log->status = $request->get('status');
        $project_log->turnover = $request->get('turnover');
        $project_log->save();

        // last log
        $last_log = ProjectLog::where('project_id', '=', $project_id)
                              ->where('endorse_project_id', '=', $endorse_project_id)
                              ->orderBy('remarks_date', 'Desc')
                              ->orderBy('remarks_time', 'Desc')
                              ->orderBy('id', 'Desc')
                              ->first();
        // last log turnover
        $last_log_turnover = ProjectLog::where('project_id', '=', $project_id)
                                       ->where('endorse_project_id', '=', $endorse_project_id)
                                       ->where('turnover', '=', 'Y')
                                       ->orderBy('remarks_date', 'Desc')
                                       ->orderBy('remarks_time', 'Desc')
                                       ->orderBy('id', 'Desc')
                                       ->first();

                                   
        $last_log_status = null;

        if($last_log)
        {
            $last_log_status = $last_log->status;
        }
        
        // get the last log turnover status  
        if($last_log_status == 'For Validation' || $last_log_status == 'Ongoing')
        {  
            if($last_log_turnover)
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
            
        }

        
        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                  ->where('endorse_project_id', '=', $endorse_project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->orderBy('id', 'Asc')
                                  ->get();

        $first_ongoing_log = null;
        $first_validation_log = null;

        // get the first programming and validation log to update programming date and validation date field   
        if(count($project_logs))
        {   
            if($project_logs->where('status', '=', 'Ongoing')->first())
            {
                $first_ongoing_log = $project_logs->where('status', '=', 'Ongoing')->first()->remarks_date;
            }
            if($project_logs->where('status', '=', 'For Validation')->first())
            {
                $first_validation_log = $project_logs->where('status', '=', 'For Validation')->first()->remarks_date;
            }
    
        }

        $accepted_date = null;
        $hasChanges = false;   

        // check if status, program date or validation date were changed
        if($status != $project->status || $first_ongoing_log != $project->program_date || $first_validation_log != $project->validation_date)
        {
            $hasChanges = true;   
        }

        // if project status is Accepted then assign accepted_date value
        if($request->get('status') == 'Accepted')
        {   
            $accepted_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        }

        // if endorse_project_id has value
        if($endorse_project_id)
        {   
            // start of programming/validation date
            $endorse_project = EndorseProject::find($endorse_project_id);
            $endorse_project->program_date = $first_ongoing_log;
            $endorse_project->validation_date = $first_validation_log;
            $endorse_project->save();

            // update status
            $project = Project::find($project_id);
            $project->status = $status;
            $project->accepted_date = $accepted_date;
            $project->save();
        }
        else
        {   
            // start of programming/validation date
            $project = Project::find($project_id);
            $project->status = $status;
            $project->program_date = $first_ongoing_log;
            $project->validation_date = $first_validation_log;
            $project->accepted_date = $accepted_date;
            $project->save();
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
            'hasChanges' => $hasChanges
        ], 200);   
    }

    public function update(Request $request, $project_log_id)
    {   
        $rules = [
            'programmer_id.required' => 'Programmer ID is required',
            'programmer_id.integer' => 'Programmer ID must be an integer',
            'remarks_date.required' => 'Remarks date is required',
            'remarks_date.date_format' => 'Invalid date. Format: (YYYY-MM-DD)',
            'remarks_time.required' => 'Remarks time is required',
            'remarks_time.date_format' => 'Invalid time. Format: (H:i)',
        ];

        $valid_fields = [
            'project_id' => 'required|integer',
            'remarks_date' => 'required|date_format:Y-m-d',
            'remarks_time' => 'required|date_format:H:i',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $project_id = $request->get('project_id');
        $project = Project::find($project_id);
        // project status
        $status = $project->status;          

        $project_log = ProjectLog::find($project_log_id);
        $project_log->remarks_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        $project_log->remarks_time = Carbon::parse($request->get('remarks_time'))->format('H:i');
        $project_log->remarks = $request->get('remarks');
        $project_log->status = $request->get('status');
        $project_log->turnover = $request->get('turnover');
        $project_log->save();
        
        $endorse_project_id = $project_log->endorse_project_id;

        // last log
        $last_log = ProjectLog::where('project_id', '=', $project_id)
                              ->where('endorse_project_id', '=', $endorse_project_id)
                              ->orderBy('remarks_date', 'Desc')
                              ->orderBy('remarks_time', 'Desc')
                              ->orderBy('id', 'Desc')
                              ->first();
        // last log turnover
        $last_log_turnover = ProjectLog::where('project_id', '=', $project_id)
                                       ->where('endorse_project_id', '=', $endorse_project_id)
                                       ->where('turnover', '=', 'Y')
                                       ->orderBy('remarks_date', 'Desc')
                                       ->orderBy('remarks_time', 'Desc')
                                       ->orderBy('id', 'Desc')
                                       ->first();

        $last_log_status = null;

        if($last_log)
        {
            $last_log_status = $last_log->status;
        }
        
        // get the last log turnover status  
        if($last_log_status == 'For Validation' || $last_log_status == 'Ongoing')
        {  
            if($last_log_turnover)
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
            
        }

        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                  ->where('endorse_project_id', '=', $endorse_project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->orderBy('id', 'Asc')
                                  ->get();
        
        $first_ongoing_log = null;
        $first_validation_log = null;

        // get the first programming and validation log to update programming date and validation date field   
        if(count($project_logs))
        {   
            if($project_logs->where('status', '=', 'Ongoing')->first())
            {
                $first_ongoing_log = $project_logs->where('status', '=', 'Ongoing')->first()->remarks_date;
            }
            if($project_logs->where('status', '=', 'For Validation')->first())
            {
                $first_validation_log = $project_logs->where('status', '=', 'For Validation')->first()->remarks_date;
            }
    
        }

        $accepted_date = null;
        $hasChanges = false;   

        // check if status, program date or validation date were changed
        if($status != $project->status || $first_ongoing_log != $project->program_date || $first_validation_log != $project->validation_date)
        {
            $hasChanges = true;   
        }

        // if project status is Accepted then assign accepted_date value
        if($request->get('status') == 'Accepted')
        {   
            $accepted_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        }

        // if endorse_project_id has value
        if($endorse_project_id)
        {  
            $endorse_project = EndorseProject::find($endorse_project_id);
            $endorse_project->program_date = $first_ongoing_log;
            $endorse_project->validation_date = $first_validation_log;
            $endorse_project->save();

            // update status
            $project = Project::find($project_id);
            $project->status = $status;
            $project->accepted_date = $accepted_date;
            $project->save();
        }
        else
        {
            $project = Project::find($project_id);
            $project->status = $status;
            $project->program_date = $first_ongoing_log;
            $project->validation_date = $first_validation_log;
            $project->accepted_date = $accepted_date;
            $project->save();
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
            'hasChanges' => $hasChanges,

        ], 200);   
    }

    public function delete(Request $request)
    {   
        
        $project_log = ProjectLog::find($request->get('project_log_id'));
        $project_id = $project_log->project_id;
        $project = Project::find($project_id);
        $endorse_project_id = $project_log->endorse_project_id;
        
        // project status
        $status = $project->status;   

        //if record is empty then display error page
        if(empty($project_log->id))
        {
            return abort(404, 'Not Found');
        }

        $project_log->delete();
        
        // last log
        $last_log = ProjectLog::where('project_id', '=', $project_id)
                              ->where('endorse_project_id', '=', $endorse_project_id)
                              ->orderBy('remarks_date', 'Desc')
                              ->orderBy('remarks_time', 'Desc')
                              ->orderBy('id', 'Desc')
                              ->first();
        // last log turnover
        $last_log_turnover = ProjectLog::where('project_id', '=', $project_id)
                                       ->where('endorse_project_id', '=', $endorse_project_id)
                                       ->where('turnover', '=', 'Y')
                                       ->orderBy('remarks_date', 'Desc')
                                       ->orderBy('remarks_time', 'Desc')
                                       ->orderBy('id', 'Desc')
                                       ->first();

        $last_log_status = null;

        if($last_log)
        {
            $last_log_status = $last_log->status;
        }
        
        // get the last log turnover status  
        if($last_log_status == 'For Validation' || $last_log_status == 'Ongoing')
        {   
            if($last_log_turnover)
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
            
        }
        
        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                  ->where('endorse_project_id', '=', $endorse_project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->orderBy('id', 'Asc')
                                  ->get();
        
        $first_ongoing_log = null;
        $first_validation_log = null;

        // get the first programming and validation log to update programming date and validation date field   
        if(count($project_logs))
        {   
            if($project_logs->where('status', '=', 'Ongoing')->first())
            {
                $first_ongoing_log = $project_logs->where('status', '=', 'Ongoing')->first()->remarks_date;
            }
            if($project_logs->where('status', '=', 'For Validation')->first())
            {
                $first_validation_log = $project_logs->where('status', '=', 'For Validation')->first()->remarks_date;
            }
    
        }
        else
        {
            $status = 'Pending';
        }

        $hasChanges = false;   

        // check if status, program date or validation date were changed
        if($status != $project->status || $first_ongoing_log != $project->program_date || $first_validation_log != $project->validation_date)
        {
            $hasChanges = true;   
        }

        if(count($project_logs) > 0)
        {
            // calculate hours difference per remarks log 
            $this->calculateHours($project_logs);
        }
        
        // if endorse_project_id has value
        if($endorse_project_id)
        {  
            $endorse_project = EndorseProject::find($endorse_project_id);
            $endorse_project->program_date = $first_ongoing_log;
            $endorse_project->validation_date = $first_validation_log;
            $endorse_project->save();

            // update status
            $project = Project::find($project_id);
            $project->status = $status;
            $project->save();
        }
        else
        {
            $project = Project::find($project_id);
            $project->status = $status;
            $project->program_date = $first_ongoing_log;
            $project->validation_date = $first_validation_log;
            $project->save();
        }

        return response()->json([
            'success' => 'Record has been deleted', 
            'status' => $status,
            'hasChanges' => $hasChanges,
        ], 200);
    }

    public function project_turnover(Request $request)
    {   
        
        $project_id = $request->get('project_id');
        $endorse_project_id = $request->get('endorse_project_id');
        $project = Project::find($project_id);
        $status = $request->get('status');
        $remarks = "";
        $hasChanges = false;
        
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

            if($status == 'For Validation' || $status == 'Ongoing')
            {   
                $project_log->turnover = 'Y';
            }
            $project_log->endorse_project_id = $endorse_project_id;
            $project_log->save();
    
            $hasChanges = true;
        }

        // create new remarks log
        $project_log = new ProjectLog();
        $project_log->project_id = $project_id;
        $project_log->remarks_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        $project_log->remarks_time = Carbon::parse($request->get('remarks_time'))->format('H:i');
        $project_log->remarks = $request->get('remarks');
        $project_log->status = $request->get('status');
        $project_log->endorse_project_id = $endorse_project_id;
        $project_log->save();
        
        // last log
        $last_log = ProjectLog::where('project_id', '=', $project_id)
                              ->where('endorse_project_id', '=', $endorse_project_id)
                              ->orderBy('remarks_date', 'Desc')
                              ->orderBy('remarks_time', 'Desc')
                              ->orderBy('id', 'Desc')
                              ->first();
        // last log turnover
        $last_log_turnover = ProjectLog::where('project_id', '=', $project_id)
                                       ->where('endorse_project_id', '=', $endorse_project_id)
                                       ->where('turnover', '=', 'Y')
                                       ->orderBy('remarks_date', 'Desc')
                                       ->orderBy('remarks_time', 'Desc')
                                       ->orderBy('id', 'Desc')
                                       ->first();

        $last_log_status = null;

        if($last_log)
        {
            $last_log_status = $last_log->status;
        }
        
        // get the last log turnover status  
        if($last_log_status == 'For Validation' || $last_log_status == 'Ongoing')
        { 
            if($last_log_turnover)
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
            
        }

        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                  ->where('endorse_project_id', '=', $endorse_project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->orderBy('id', 'Asc')
                                  ->get();
        
        $first_ongoing_log = null;
        $first_validation_log = null;

        // get the first programming and validation log to update programming date and validation date field   
        if(count($project_logs))
        {   
            if($project_logs->where('status', '=', 'Ongoing')->first())
            {
                $first_ongoing_log = $project_logs->where('status', '=', 'Ongoing')->first()->remarks_date;
            }
            if($project_logs->where('status', '=', 'For Validation')->first())
            {
                $first_validation_log = $project_logs->where('status', '=', 'For Validation')->first()->remarks_date;
            }
    
        }

        $hasChanges = false;   

        // check if program date or validation date were changed
        if($first_ongoing_log != $project->program_date || $first_validation_log != $project->validation_date)
        {
            $hasChanges = true;   
        }

        if(count($project_logs) > 0)
        {
            // calculate hours difference per remarks log 
            $this->calculateHours($project_logs);
        }

        $accepted_date = null;

        // if project status is Accepted then assign accepted_date value
        if($request->get('status') == 'Accepted')
        {   
            $accepted_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        }

        // if endorse_project_id has value
        if($endorse_project_id)
        {  
            $endorse_project = EndorseProject::find($endorse_project_id);
            $endorse_project->program_date = $first_ongoing_log;
            $endorse_project->validation_date = $first_validation_log;
            $endorse_project->save();

            // update status
            $project = Project::find($project_id);
            $project->status = $status;
            $project->accepted_date = $accepted_date;
            $project->save();
        }
        else
        {
            $project = Project::find($project_id);
            $project->status = $status;
            $project->program_date = $first_ongoing_log;
            $project->validation_date = $first_validation_log;
            $project->accepted_date = $accepted_date;
            $project->save();
        }

        if(count($project_logs) > 0)
        {
            // calculate hours difference per remarks log 
            $this->calculateHours($project_logs);
        }

        return response()->json([
            'success' => 'Record has successfully added', 
            'project_log' => $project_log, 
            'status' => $status,
            'hasChanges' => $hasChanges,
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
        // if(empty($project_log->id))
        // {
        //     return abort(404, 'Not Found');
        // }

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

            if($log->turnover == 'Y' || $log->status == 'Pending' || $log->status == 'Accepted' || $log->status == 'Pending')
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

    public function import_project_log(Request $request) 
    {   
        
        try {
            $file_extension = '';
            $path = '';
            if($request->file('file'))
            {   $path = $request->file('file')->getRealPath();
                $file_extension = $request->file('file')->getClientOriginalExtension();
            }

            $validator = Validator::make(
                [
                    'file' => strtolower($file_extension),
                    'project_id' => $request->get('project_id')
                ],
                [
                    'file' => 'required|in:xlsx,xls,ods',
                    'project_id' => 'required|integer'
                ], 
                [
                    'file.required' => 'File is required',
                    'file.in' => 'File type must be xlsx, xls or ods',
                    'project_id.required' => 'Project ID is required',
                    'project_id.integer' => 'Project ID must be an integer'
                ]
            );  
            
            if($validator->fails())
            {
                return response()->json($validator->errors(), 200);
            }
    
            if ($request->file('file')) {
                    
                // $array = Excel::toArray(new ProjectsImport, $request->file('file'));
                $collection = Excel::toCollection(new ProjectLogsImport, $request->file('file'))[0];
                $ctr_collection = count($collection);
                $columns = [
                    // 'project_id', 
                    'remarks_date', 
                    'remarks_time', 
                    'status',
                    'remarks',
                    'mins_diff',
                    'turnover'
                    
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
                        // '*.project_id.required' => 'Project ID is required',
                        // '*.project_id.integer' => 'Project ID must be an integer',
                        '*.remarks_date.required' => 'Remarks date is required',
                        '*.remarks_date.date_format' => 'Invalid date. Format: (YYYY-MM-DD)',
                        '*.remarks_time.required' => 'Remarks time is required',
                        '*.remarks_time.date_format' => 'Invalid time. Format: (H:i)',
                        '*.status.required' => 'Status is required',
                        '*.status.in' => 'Value is invalid, must be on the ff. values ("Ongoing", "For Validation", "Pending", "Accepted", "Cancelled")',
                        '*.remarks.required' => 'Remarks is required',
                        '*.mins_diff.integer' => 'Minutes difference must be an integer',
                        '*.mins_diff.between' => 'Minutes difference must be 0 or above',
                        '*.ideal_prog_hrs.numeric' => 'Ideal Prog. Hrs must be numeric',
                        '*.ideal_prog_hrs.between' => 'Ideal Prog. Hrs must be 0 or above',
                        '*.turnover.in' => 'Value is invalid, must be "Y" or Null',
                    ];
            
                    $valid_fields = [
                        // '*.project_id' => 'required|integer',
                        '*.remarks_date' => 'required|date_format:Y-m-d',
                        '*.remarks_time' => 'required|date_format:H:i',
                        '*.status' => 'required|in:"Ongoing", "For Validation", "Pending", "Accepted", "Cancelled"',
                        '*.remarks' => 'required',
                        '*.mins_diff' => 'nullable|integer|between:0, 999999999',
                        '*.turnover' => 'nullable|in:"Y"',
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
                    // Excel::import(new ProjectLogsImport, $path);
                    
                    foreach($fields as $data)
                    {
                        $project_log = new ProjectLog();
                        $project_log->project_id = $request->get('project_id');
                        $project_log->remarks_date = $data['remarks_date'];
                        $project_log->remarks_time = $data['remarks_time'];
                        $project_log->status = $data['status'];
                        $project_log->remarks = $data['remarks'];
                        $project_log->turnover = $data['turnover'];
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
                    }

                    event(new WebsocketEvent(['action' => 'import-project-log']));
                }
                    
                return response()->json(['success' => 'Record has successfully imported', $request], 200);
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
