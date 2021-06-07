<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use DB;
use App\Project;
use App\ProjectLog;
use App\User;
use App\Holiday;
use App\AcceptanceOveriew;
use Carbon\Carbon;

class ReportsPreviewController extends Controller
{   
    public function preview(Request $request)
    {   
     
        $programmer_id = $request->get('programmer_id');
        $filter_date = Carbon::parse($request->get('filter_date'))->format('Y-m-d');
        $firstOfMonth = Carbon::parse($filter_date)->firstOfMonth()->format('Y-m-d');
        $lastOfMonth = Carbon::parse($filter_date)->lastOfMonth()->format('Y-m-d');

        $projects = Project::where('status', '!=', 'Cancelled')
                           ->where('programmer_id', '=', $programmer_id)
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

        // string only - used to join using DB::raw
        $endorseProjects = '(SELECT MAX(a.id) as id, 
                                   a.project_id,
                                   (SELECT t1.Name 
                                    FROM endorse_projects t0 
                                    INNER JOIN (SELECT id, name FROM users WHERE type = "programmer") t1 ON t0.programmer_id = t1.id 
                                    WHERE t0.id = MAX(a.id)) as programmer,
                                   (SELECT t0.programmer_id FROM endorse_projects t0 WHERE t0.id = MAX(a.id)) as programmer_id,
                                   (SELECT date_receive FROM endorse_projects t0 WHERE t0.id = MAX(a.id)) as date_receive,
                                   (SELECT program_date FROM endorse_projects t0 WHERE t0.id = MAX(a.id)) as program_date,
                                   (SELECT validation_date FROM endorse_projects t0 WHERE t0.id = MAX(a.id)) as validation_date,
                                   (SELECT DATE_FORMAT(t0.created_at, "%Y-%m-%d") FROM endorse_projects t0 WHERE t0.id = MAX(a.id)) as endorse_date
                                    FROM endorse_projects a 
                                    WHERE DATE_FORMAT(a.created_at, "%Y-%m-%d") <= "'.$filter_date.'"
                                    GROUP BY a.project_id
                                   ) as endorse_projects';
                                   
        // string only - used to join using DB::raw
        $projectStatus = '(SELECT a.project_id,
                                  (SELECT CASE WHEN x.turnover = "Y" and x.status = "Ongoing" THEN "For Validation" 
                                               WHEN x.turnover = "Y" and x.status = "For Validation" THEN "Ongoing"
                                          ELSE x.status END as status
                                   FROM project_logs x 
                                   WHERE x.id = (SELECT MAX(y.id) 
                                                 FROM project_logs y 
                                                 WHERE y.remarks_time = (SELECT MAX(z.remarks_time) 
                                                                         FROM project_logs z 
                                                                         WHERE z.remarks_date = (SELECT MAX(i.remarks_date) FROM project_logs i WHERE i.remarks_date <= "'.$filter_date.'" and i.project_id = a.project_id)
                                                                         AND z.project_id = a.project_id) 
                                                 AND Y.remarks_date = (SELECT MAX(i.remarks_date) FROM project_logs i WHERE i.remarks_date <= "'.$filter_date.'" and i.project_id = a.project_id)
                                                 AND y.project_id = a.project_id)
                                   ) AS status
                            FROM project_logs a 
                            WHERE a.remarks_date <= "'.$filter_date.'"  and a.endorse_project_id IS NULL
                            GROUP BY a.project_id
                           ) as project_status';
        
        $endorse_projects = DB::table('projects')
                    ->leftJoin(DB::raw($endorseProjects), 'projects.id', '=', 'endorse_projects.project_id')
                    ->leftJoin(DB::raw($projectStatus), 'projects.id', '=', 'project_status.project_id')
                    ->leftJoin('departments', 'projects.department_id', '=','departments.id')
                    ->leftJoin('managers', 'departments.id', '=', 'managers.department_id')
                    ->leftJoin(DB::raw('users as programmers'), 'endorse_projects.programmer_id', '=', 'programmers.id')
                    ->leftJoin(DB::raw('users as validators'), 'projects.validator_id', '=', 'validators.id')
                    ->select(DB::raw('CASE WHEN IFNULL(project_status.status, "Pending") = "For Validation" THEN "01" 
                                           WHEN IFNULL(project_status.status, "Pending") = "Ongoing" THEN "02"
                                           WHEN IFNULL(project_status.status, "Pending") = "Accepted" THEN "04"
                                      ELSE "03" END as report_grp'),
                             DB::raw('projects.id as project_id'), DB::raw('endorse_projects.id as endorse_project_id'), 
                             DB::raw('departments.name as department'), 'projects.ref_no', 'projects.report_title', 
                             DB::raw('departments.id as department_id'), DB::raw('managers.name as manager'), 
                             'endorse_projects.programmer', 'endorse_projects.programmer_id',
                             DB::raw('validators.name as validator'), DB::raw('validators.id as validator_id'),
                             DB::raw("DATE_FORMAT(projects.created_at, '%m/%d/%Y') as date_logged"),
                             DB::raw("DATE_FORMAT(endorse_projects.date_receive, '%m/%d/%Y') as date_receive"),
                             DB::raw("DATE_FORMAT(projects.date_approve, '%m/%d/%Y') as date_approve"),
                             DB::raw("DATE_FORMAT(endorse_projects.program_date, '%m/%d/%Y') as program_date"),
                             DB::raw("DATE_FORMAT(endorse_projects.validation_date, '%m/%d/%Y') as validation_date"),
                             DB::raw("DATE_FORMAT(projects.accepted_date, '%m/%d/%Y') as accepted_date"),
                             DB::raw("DATE_FORMAT(endorse_projects.endorse_date, '%m/%d/%Y') as endorse_date"),
                             DB::raw('IFNULL(project_status.status, "Pending") as status'),
                             'projects.type', 'projects.ideal_prog_hrs', 'projects.ideal_valid_hrs', 'projects.template_percent',
                             'projects.program_percent', 'projects.validation_percent', 'projects.program_hrs', 'projects.validate_hrs')
                    ->where('projects.status', '!=', 'Cancelled')
                    // ->where('projects.ref_no', '=', '1')
                    ->where(function($query) use ($firstOfMonth, $lastOfMonth, $filter_date) {
                        $query->whereBetween('projects.accepted_date', [$firstOfMonth, $lastOfMonth])
                              ->orWhere('projects.accepted_date', null)
                              ->where(function($query2) use ($firstOfMonth, $lastOfMonth, $filter_date) {
                                    $query2->whereDate('endorse_projects.endorse_date', '<=', $filter_date)
                                           ->orWhereDate('endorse_projects.date_receive', '<=', $filter_date)   
                                           ->orWhereDate('endorse_projects.program_date', '<=', $filter_date);
                              });        
                    })
                    ->where('endorse_projects.programmer_id', '=', $programmer_id);

        $projects = DB::table('projects')
                    ->leftJoin('departments', 'projects.department_id', '=','departments.id')
                    ->leftJoin('managers', 'departments.id', '=', 'managers.department_id')
                    ->leftJoin(DB::raw('users as programmers'), 'projects.programmer_id', '=', 'programmers.id')
                    ->leftJoin(DB::raw('users as validators'), 'projects.validator_id', '=', 'validators.id')
                    ->leftJoin(DB::raw($projectStatus), 'projects.id', '=', 'project_status.project_id')
                    ->select(DB::raw('CASE WHEN IFNULL(project_status.status, "Pending") = "For Validation" THEN "01" 
                                           WHEN IFNULL(project_status.status, "Pending") = "Ongoing" THEN "02"
                                           WHEN IFNULL(project_status.status, "Pending") = "Accepted" THEN "04"
                                      ELSE "03" END as report_grp'),
                             DB::raw('projects.id as project_id'), DB::raw('null as endorse_project_id'), 
                             DB::raw('departments.name as department'), 'projects.ref_no', 'projects.report_title', 
                             DB::raw('departments.id as department_id'), DB::raw('managers.name as manager'), 
                             DB::raw('programmers.name as programmer'), DB::raw('programmers.id as programmer_id'),
                             DB::raw('validators.name as validator'), DB::raw('validators.id as validator_id'),
                             DB::raw("DATE_FORMAT(projects.created_at, '%m/%d/%Y') as date_logged"),
                             DB::raw("DATE_FORMAT(projects.date_receive, '%m/%d/%Y') as date_receive"),
                             DB::raw("DATE_FORMAT(projects.date_approve, '%m/%d/%Y') as date_approve"),
                             DB::raw("DATE_FORMAT(projects.program_date, '%m/%d/%Y') as program_date"),
                             DB::raw("DATE_FORMAT(projects.validation_date, '%m/%d/%Y') as validation_date"),
                             DB::raw("DATE_FORMAT(projects.accepted_date, '%m/%d/%Y') as accepted_date"),
                             DB::raw("null as endorse_date"), DB::raw('IFNULL(project_status.status, "Pending") as status'),
                             'projects.type', 'projects.ideal_prog_hrs', 'projects.ideal_valid_hrs', 'projects.template_percent', 
                             'projects.program_percent', 'projects.validation_percent', 'projects.program_hrs', 'projects.validate_hrs')
                    ->where('projects.status', '!=', 'Cancelled')
                    // ->where('projects.ref_no', '=', '1')
                    ->where(function($query) use ($firstOfMonth, $lastOfMonth, $filter_date) {
                        $query->whereBetween('projects.accepted_date', [$firstOfMonth, $lastOfMonth])
                              ->orWhere('projects.accepted_date', null)
                              ->where(function($query2) use ($firstOfMonth, $lastOfMonth, $filter_date) {
                                    $query2->whereDate(DB::raw('DATE_FORMAT(projects.created_at, "%Y-%m-%d")'), '<=', $filter_date)
                                           ->orWhereDate('projects.date_receive', '<=', $filter_date)   
                                           ->orWhereDate('projects.program_date', '<=', $filter_date);
                              });       
                    })
                    ->where(function($query) use ($firstOfMonth, $filter_date) {
                        $query->whereDate('projects.endorse_date', '>', $filter_date)
                              ->orWhereNull('projects.endorse_date');       
                    })
                    ->where('projects.programmer_id', '=', $programmer_id)
                    ->union($endorse_projects)
                    ->orderBy('report_grp', 'Desc')
                    ->orderBy('project_id', 'Desc')
                    ->get();

        $programmer = count($projects) ? $projects->first()->programmer : '';
    
        return view('reports_preview', compact(
            'projects', 
            'project_execution_hrs',
            'filter_date',
            'programmer')
        );

    }

    public function project_acceptance()
    {
        return view('acceptance_preview');
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

        // select statement to check if project was endorsed based on filter_date parameter
        $endorse_projects = DB::table('projects')
                                ->join(DB::raw('(SELECT max(a.id) as id, 
                                                        a.project_id,
                                                        (SELECT t1.Name 
                                                        FROM endorse_projects t0 
                                                        INNER JOIN (SELECT id, name FROM users WHERE type = "programmer") t1 ON t0.programmer_id = t1.id 
                                                        WHERE t0.id = MAX(a.id)) as programmer,
                                                        (SELECT t0.programmer_id FROM endorse_projects t0 WHERE t0.id = MAX(a.id)) as programmer_id
                                                FROM endorse_projects a 
                                                WHERE a.endorse_date <= "'.$filter_date.'" OR a.endorse_date IS NULL
                                                group by a.project_id
                                                ) as endorse_projects'
                                        ), 'projects.id', '=', 'endorse_projects.project_id')
                                ->select()
                                ->where('projects.id', '=', $project_id)
                                ->whereDate('projects.endorse_date', '<=', $filter_date)
                                ->first();
              
        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                ->where('remarks_date', '<=', $filter_date)
                                ->orderBy('remarks_date', 'desc')
                                ->orderBy('remarks_time', 'desc')
                                ->orderBy('id', 'desc')
                                ->get(); 

        // if this project was endorsed then get the project_logs with endorse_project_id
        if($endorse_projects)
        {
            $project_logs = ProjectLog::where('endorse_project_id', '=', $endorse_projects->id)
                                ->orderBy('remarks_date', 'Asc')
                                ->orderBy('remarks_time', 'Asc')
                                ->get();
        }

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

        // select statement to check if project was endorsed based on filter_date parameter
        $endorse_projects = DB::table('projects')
                                ->join(DB::raw('(SELECT max(a.id) as id, 
                                                        a.project_id,
                                                        (SELECT t1.Name 
                                                        FROM endorse_projects t0 
                                                        INNER JOIN (SELECT id, name FROM users WHERE type = "programmer") t1 ON t0.programmer_id = t1.id 
                                                        WHERE t0.id = MAX(a.id)) as programmer,
                                                        (SELECT t0.programmer_id FROM endorse_projects t0 WHERE t0.id = MAX(a.id)) as programmer_id
                                                FROM endorse_projects a 
                                                WHERE a.endorse_date <= "'.$filter_date.'" OR a.endorse_date IS NULL
                                                group by a.project_id
                                                ) as endorse_projects'
                                        ), 'projects.id', '=', 'endorse_projects.project_id')
                                ->select()
                                ->where('projects.id', '=', $project_id)
                                ->whereDate('projects.endorse_date', '<=', $filter_date)
                                ->first();
              
        $project_logs = ProjectLog::where('project_id', '=', $project_id)
                                ->orderBy('remarks_date', 'Asc')
                                ->orderBy('remarks_time', 'Asc')
                                ->get();

        // if this project was endorsed then get the project_logs with endorse_project_id
        if($endorse_projects)
        {
            $project_logs = ProjectLog::where('endorse_project_id', '=', $endorse_projects->id)
                                ->orderBy('remarks_date', 'Asc')
                                ->orderBy('remarks_time', 'Asc')
                                ->get();
        }

        
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
        
        // if this project was endorsed then get the project_logs with endorse_project_id
        if($endorse_projects)
        {
            // logs as of previous month
            $project_logs_pm = ProjectLog::where('endorse_project_id', '=', $endorse_projects->id)
                                        ->where('remarks_date', '<=', $lastPrevMonth)
                                        ->orderBy('remarks_date', 'asc')
                                        ->orderBy('remarks_time', 'asc')
                                        ->orderBy('id', 'asc')
                                        ->get();

            // logs this month
            $project_logs_tm = ProjectLog::where('endorse_project_id', '=', $endorse_projects->id)
                                        ->where('remarks_date', '<=', $filter_date)
                                        ->where('remarks_date', '>=', $firstOfMonth)
                                        ->orderBy('remarks_date', 'asc')
                                        ->orderBy('remarks_time', 'asc')
                                        ->orderBy('id', 'asc')
                                        ->get();
        }

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


}
