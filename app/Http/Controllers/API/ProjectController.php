<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;
use App\Department;
use App\Manager;
use App\Project;
use App\User;
use App\RefNoSetting;
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
                    ->select('projects.id', 'projects.ref_no', 'projects.report_title', DB::raw('departments.name as department'), 
                             DB::raw('departments.id as department_id'), DB::raw('managers.name as manager'), 
                             DB::raw('programmers.name as programmer'), DB::raw('validators.name as validator'),
                             DB::raw("DATE_FORMAT(projects.created_at, '%m/%d/%Y') as date_logged"),
                             DB::raw("DATE_FORMAT(projects.date_receive, '%m/%d/%Y') as date_received"),
                             DB::raw("DATE_FORMAT(projects.date_approve, '%m/%d/%Y') as date_approved"),
                             'projects.type', 'projects.ideal', 'projects.status')
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
        $project->type = $request->get('type');
        $project->status = "pending";
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
                             DB::raw('programmers.name as programmer'), DB::raw('validators.name as validator'),
                             DB::raw("DATE_FORMAT(projects.created_at, '%m/%d/%Y') as date_logged"),
                             DB::raw("DATE_FORMAT(projects.date_receive, '%m/%d/%Y') as date_received"),
                             DB::raw("DATE_FORMAT(projects.date_approve, '%m/%d/%Y') as date_approved"),
                             'projects.type', 'projects.ideal', 'projects.status')
                    ->where('projects.id', '=', $project->id)
                    ->first();

        return response()->json(['success' => 'Record has successfully added', 'project' => $project], 200);
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
        $ref_no = 0;
        if($setting->active == 'Y')
        {   
            $ref_no = $setting->start;
        }
        else
        {
            // $project = Project::first();
            $project = DB::table('projects')->orderBy('id', 'Desc')->first();
            $ref_no = $project->ref_no + 1;
        }

        return $ref_no;
    }
    
}
