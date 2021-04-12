<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Manager;
use App\Department;
use Validator;
use DB;

class ManagerController extends Controller
{
    public function index()
    {
        $managers = DB::table('managers')
                      ->leftJoin('departments', 'managers.department_id', '=', 'departments.id')
                      ->select('managers.id', 'managers.name', 'managers.department_id', DB::raw('departments.name as department'), 'managers.active')
                      ->get();    
        $departments = Department::all();

        return response()->json(['managers' => $managers, 'departments' => $departments], 200);
    }

    public function store(Request $request)
    {
        $rules = [
            'name.required' => 'Name is required',
            'department_id.required' => 'Department is required',
            'department_id.integer' => 'Department must be an integer',
        ];

        $valid_fields = [
            'name' => 'required',
            'department_id' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $manager = new Manager();
        $manager->name = $request->get('name');
        $manager->department_id = $request->get('department_id');
        $manager->active = $request->get('active');
        $manager->save();

        $manager = DB::table('managers')
                      ->join('departments', 'managers.department_id', '=', 'departments.id')
                      ->select('managers.id', 'managers.name', 'managers.department_id', DB::raw('departments.name as department'), 'managers.active')
                      ->where('managers.id', '=', $manager->id)
                      ->first(); 

        return response()->json(['success' => 'Record has successfully added', 'manager' => $manager], 200);


    }

    public function update(Request $request, $manager_id)
    {
        $rules = [
            'name.required' => 'Name is required',
            'department.required' => 'Department is required',
        ];

        $valid_fields = [
            'name' => 'required',
            'department' => 'required',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $manager = Manager::find($manager_id);
        $manager->name = $request->get('name');
        $manager->department_id = $request->get('department_id');
        $manager->active = $request->get('active');
        $manager->save();

        $manager = DB::table('managers')
                      ->join('departments', 'managers.department_id', '=', 'departments.id')
                      ->select('managers.id', 'managers.name', 'managers.department_id', DB::raw('departments.name as department'), 'managers.active')
                      ->where('managers.id', '=', $manager->id)
                      ->first(); 

        return response()->json(['success' => 'Record has successfully added', 'manager' => $manager], 200);


    }

    public function delete(Request $request)
    {
        $manager = Manager::find($request->get('manager_id'));
        if(empty($manager->id))
        {
            return abort(404, 'Not Found');
        }
        $manager->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }




}
