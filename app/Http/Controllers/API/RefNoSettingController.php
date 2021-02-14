<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\RefNoSetting;
use Validator;

class RefNoSettingController extends Controller
{
    public function index() 
    {
        $settings = RefNoSetting::first();

        return response()->json(['settings' => $settings], 200);
    }

    public function update(Request $request, $settings_id)
    {
        $rules = [
            'start.required' => 'Start is required',
        ];

        $validator = Validator::make($request->all(),[
            'start' => 'required'
        ], $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $setting = RefNoSetting::find($settings_id);
        $setting->start = $request->get('start');
        $setting->active = $request->get('active');
        $setting->save();

        return response()->json(['success' => 'Record has been updated', 'setting' => $setting], 200);
    }
}
