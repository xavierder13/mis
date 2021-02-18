<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ProjectLog;
use Carbon\Carbon;
use Validator;
use DB;

class ProjectLogController extends Controller
{
    public function index()
    {

    }

    public function store(Request $request)
    {   return $request;
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

        $remarks = new ProjectLog();
        $remarks->project_id = $request->get('project_id');
        $remarks->remarks_date = Carbon::parse($request->get('remarks_date'))->format('Y-m-d');
        $remarks->remarks_time = $request->get('remarks_time');
        $remarks->remarks_time = $request->get('status');
        $remarks->save();

        return response()->json(['success' => 'Record has successfully added'], 200);   
    }
}
