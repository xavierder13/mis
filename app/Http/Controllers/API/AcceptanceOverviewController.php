<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\AcceptanceOverview;

class AcceptanceOverviewController extends Controller
{   

    public function index($project_id)
    {
        $acceptance_overview = AcceptanceOverview::find($project_id);

        return response()->json(['acceptance_overview' => $acceptance_overview], 200);
    }

    public function create(Request $request) 
    {   
        $validator = Validator::make(
            $request->all(), 
            ['overview' => 'required'],
            ['overview.required' => 'Overview is required']
        );

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $acceptance_overview = AcceptanceOverview::find($project_id);

        // if acceptance_overview not exist
        if(!$acceptance_overview)
        {
            $acceptance_overview = new AcceptanceOverview();             
        }

        $acceptance_overview->project_id = $request->get('project_id');
        $acceptance_overview->for_delete = $request->get('for_delete');
        $acceptance_overview->intended_users = $request->get('intended_users');
        $acceptance_overview->location1 = $request->get('location1');
        $acceptance_overview->location2 = $request->get('location2');
        $acceptance_overview->overview = $request->get('overview');
        $acceptance_overview->validator_note = $request->get('validator_note');
        $acceptance_overview->manager_note = $request->get('manager_note');
        $acceptance_overview->save();

        return response()->json(['success' => 'Record has been saved'], 200);
        
    }

    public function delete(Request $request)
    {
        $acceptance_overview = AcceptanceOverview::find($request->get('project_id'));
        // $acceptance_overview->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }


}
