<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;
use DB;
use Hash;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Events\WebsocketEvent;
use Illuminate\Support\Facades\Artisan;

class UserController extends Controller
{
    public function index()
    {   
        event(new WebsocketEvent('some data'));
        $users = User::with('roles')->with('roles.permissions')->get();
        // $users = User::all();

        return response()->json(['users' => $users], 200);
    }

    public function store(Request $request)
    {   
        // return $request;
        
        $rules = [
            'name.required' => 'Please enter name',
            'email.email' => 'Please enter a valid email',
            'username.required' => 'Please enter username',
            'username.unique' => 'Username already exists',
            'password.min' => 'Password must be atleast 8 characters',
            'password.same' => 'Password and Confirm Password did not match',
            'type.required' => 'User type is required',
        ];

        $valid_fields = [
            'name' => 'required|string|max:255',
            // 'email' => 'string|email|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8|same:confirm_password',
            'type' => 'required',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {   
            return response()->json($validator->errors(), 200);
        }

        $user = new User();
        $user->name = $request->get('name');
        $user->email = $request->get('email');
        $user->password = Hash::make($request->get('password'));
        $user->type = $request->get('type');
        $user->active = $request->get('active');
        $user->save();

        // $user->assignRole($request->get('roles'));

        return response()->json(['success' => 'Record has successfully added', 'user' => $user], 200);
    }

    public function edit($user_id)
    {
        $user = User::find($user_id);
        $service_signatories = DB::table('service_signatories')
                                ->join('services', 'service_signatories.serviceid', '=', 'services.id')
                                ->select(DB::raw('services.*'))
                                ->where('userid', $user_id)
                                ->get();
        //if record is empty then display error page
        if(empty($user->id))
        {
            return abort(404, 'Not Found');
        }
        $roles = Role::pluck('name','name')->all();
        $userRole = $user->roles->pluck('name','name')->all();
        
        return response()->json([
            'user' => $user,
            'service_signatories' => $service_signatories
        ], 200);

    }

    public function update(Request $request, $user_id)
    {   
        // return $request;

        $rules = [
            'name.required' => 'Please enter name',
        ];

        $valid_fields = [
            'name' => 'required|string|max:255',
        ];

        if(!empty($request->get('password')))
        {
            $valid_fields['password'] = 'string|min:8|same:confirm_password';
        }


        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {   
            return response()->json($validator->errors(), 200);
        }

        $user = User::find($user_id);

        //if record is empty then display error page
        if(empty($user->id))
        {
            return abort(404, 'Not Found');
        }

        $user->name = $request->get('name');
        if(!empty($request->get('password')))
        {
            $user->password = Hash::make($request->get('password'));
        }
        $user->type = $request->get('type');
        $user->active = $request->get('active');
        $user->save();
        
        DB::table('model_has_roles')->where('model_id',$user_id)->delete();
        
        $user->assignRole($request->get('roles'));
        
        $user_roles = $user->roles->pluck('name')->all();

        $user_permissions = $user->getAllPermissions()->pluck('name');

        return response()->json([
            'success' => 'Record has been updated', 
            'user_roles' => $user_roles, 
            'user_permissions' => $user_permissions,
            'user' => User::with('roles')->where('id', '=', $user_id)->first()
        ], 200);
    }

    public function delete(Request $request)
    {
        $user = User::find($request->get('user_id'));

        //if record is empty then display error page
        if(empty($user->id))
        {
            return abort(404, 'Not Found');
        }

        if($user->email == 'admin@mis.ac')
        {
            return abort(403, 'Forbidden');
        }

        $user->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }

    public function userRolesPermissions()
    {
        $user_roles = Auth::user()->roles->pluck('name')->all();

        $user_permissions = Auth::user()->getAllPermissions()->pluck('name');

        return response()->json([
            'user_roles' => $user_roles, 
            'user_permissions' => $user_permissions,
        ], 200);
    }
    
}
