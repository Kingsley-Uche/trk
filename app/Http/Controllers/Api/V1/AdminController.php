<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    //
  public function RegisterUser(request $request){
    $user = Auth::guard('sanctum')->user();
    

        if (!$user || !in_array($user->user_type, ['admin', 'system_admin']) || !$user->can('create user')) {
            return response()->json(['error' => 'Invalid Access'], 422);
        }
        $rules = [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => ['required', 'regex:/^([0-9\s\-\+\(\)]*)$/'],
            'user_type' => 'required|in:admin,individual,',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Sanitize and prepare user data
        $userData = [
            'first_name' => filter_var($request->input('firstName'), FILTER_SANITIZE_STRING),
            'last_name' => filter_var($request->input('lastName'), FILTER_SANITIZE_STRING),
            'email' => filter_var($request->input('email'), FILTER_SANITIZE_EMAIL),
            'phone' => preg_replace('/[^0-9\s\-\+\(\)]/', '', $request->input('phone')),
            'password' => bcrypt(123456), // Consider using a more secure method for generating passwords
            'user_type' => filter_var($request->input('user_type'), FILTER_SANITIZE_STRING),
            'created_by_user_id'=>$user->id,
            'status'=>true,
        ];


        $user = $this->createUserByAdmin($userData);
        return response()->json(['success' => true, 'message' => 'User created successfully'], 201);
        

    
  }

  private function createUserByAdmin($userData)
    {  
        $user = User::create($userData);
        return $user;
    }
    public function GetAll()
    {
        $user = Auth::guard('sanctum')->user();
    
        if (!in_array($user->user_type, ['admin', 'system_admin'])) {
            $users = User::where('created_by_user_id', $user->id)->paginate(10);
        } else {
            $users = User::paginate(10);
        }
    
        return response()->json($users, 200);
    }
    Public function SaveTrackerDevice(request $request){
        
    }
    
}
