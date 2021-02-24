<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Project;
use App\ProjectLog;
use Carbon\Carbon;
use Validator;
use DB;

class ProjectLogController extends Controller
{
    public function index($project_id)
    {
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
                             DB::raw("DATE_FORMAT(projects.program_date, '%m/%d/%Y') as program_date"),
                             DB::raw("DATE_FORMAT(projects.validation_date, '%m/%d/%Y') as validation_date"),
                             'projects.type', 'projects.ideal', 'projects.template_percent', 'projects.status',
                             'projects.program_percent', 'projects.validation_percent')
                    ->where('projects.status', '!=', 'Cancelled')
                    ->where('projects.id', '=', $project_id)
                    ->orderBy('projects.id', 'Desc')
                    ->first();
        
        $project_logs = ProjectLog::select('id', 'project_id',DB::raw("DATE_FORMAT(remarks_date, '%m/%d/%Y') as remarks_date"), 'remarks_time', 'remarks', 'status')
                                  ->where('project_id' , '=', $project_id)
                                  ->orderBy('remarks_date', 'Asc')
                                  ->orderBy('remarks_time', 'Asc')
                                  ->get();

        return response()->json(['project' => $project, 'project_logs' => $project_logs], 200);

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

        Project::where('id', '=', $request->get('project_id'))
               ->update(['status' => $request->get('status')]);

        $project_log = new ProjectLog();
        $project_log->project_id = $request->get('project_id');
        $project_log->remarks_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        $project_log->remarks_time = Carbon::parse($request->get('remarks_time'))->format('H:i');
        $project_log->remarks = $request->get('remarks');
        $project_log->status = $request->get('status');
        $project_log->save();

        $project_log = DB::table('project_logs')
                         ->select('id', 'project_id',DB::raw("DATE_FORMAT(remarks_date, '%m/%d/%Y') as remarks_date"), 'remarks_time', 'remarks', 'status')
                         ->where('id', '=', $project_log->id)
                         ->first();



        return response()->json(['success' => 'Record has successfully added', 'project_log' => $project_log], 200);   
    }

    public function update(Request $request, $project_log_id)
    {   
        // return Carbon::parse($request->get('remarks_time'))->format('H:m');
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

        Project::where('id', '=', $request->get('project_id'))
               ->update(['status' => $request->get('status')]);

        $project_log = ProjectLog::find($project_log_id);
        $project_log->remarks_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        $project_log->remarks_time = Carbon::parse($request->get('remarks_time'))->format('H:i');
        $project_log->remarks = $request->get('remarks');
        $project_log->status = $request->get('status');
        $project_log->save();

        $project_log = DB::table('project_logs')
                         ->select('id', 'project_id',DB::raw("DATE_FORMAT(remarks_date, '%m/%d/%Y') as remarks_date"), 'remarks_time', 'remarks', 'status')
                         ->where('id', '=', $project_log->id)
                         ->first();

        return response()->json(['success' => 'Record has been updated', 'project_log' => $project_log], 200);   
    }

    public function delete(Request $request)
    {
        $project_log = ProjectLog::find($request->get('project_log_id'));

        //if record is empty then display error page
        if(empty($project_log->id))
        {
            return abort(404, 'Not Found');
        }

        $project_log->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }
}
