<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;
use App\Holiday;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::select('id', 'name', DB::raw("DATE_FORMAT(holiday_date, '%m/%d/%Y') as holiday_date"))->get();

        return response()->json(['holidays' => $holidays], 200);
    }

    public function store(Request $request)
    {   
        $rules = [
            'holiday.required' => 'Holiday Name is required',
            'holiday_date.required' => 'Date is required',
            'holida_date.date' => 'Please enter a valid date',
        ];

        $valid_fields = [
            'holiday' => 'required',
            'holiday_date' => 'required|date',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $holiday = new Holiday();
        $holiday->name = $request->get('holiday');
        $holiday->holiday_date = Carbon::parse($request->get('holiday_date'))->format('Y-m-d');
        $holiday->save();

        $holiday = Holiday::select('id', 'name', DB::raw("DATE_FORMAT(holiday_date, '%m/%d/%Y') as holiday_date")) 
                          ->where('id', '=', $holiday->id)
                          ->first();

        return response()->json(['success' => 'Record has successfully added', 'holiday' => $holiday], 200);
    }

    public function update(Request $request, $holiday_id)
    {   
        $rules = [
            'holiday.required' => 'Holiday Name is required',
            'holiday_date.required' => 'Date is required',
            'holida_date.date' => 'Please enter a valid date',
        ];

        $valid_fields = [
            'holiday' => 'required',
            'holiday_date' => 'required|date',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $holiday = Holiday::find($holiday_id);
        $holiday->name = $request->get('holiday');
        $holiday->holiday_date = Carbon::parse($request->get('holiday_date'))->format('Y-m-d');
        $holiday->save();

        $holiday = Holiday::select('id', 'name', DB::raw("DATE_FORMAT(holiday_date, '%m/%d/%Y') as holiday_date")) 
                          ->where('id', '=', $holiday->id)
                          ->first();

        return response()->json(['success' => 'Record has been updated', 'holiday' => $holiday], 200);
    }

    public function delete(Request $request)
    {   
        $holiday = Holiday::find($request->get('holiday_id'));
        //if record is empty then display error page
        if(empty($holiday->id))
        {
            return abort(404, 'Not Found');
        }
        $holiday->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }
}
