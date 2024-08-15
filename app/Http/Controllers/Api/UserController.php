<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Notifications\EmailNotification;
use App\Models\User;
use Ichtrojan\Otp\Otp as OTP;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private $sourceCodeHash = 'c085645f276fd835042d3730d6a8fc99f6a3f0e8dd3d3ee73f61bbe9db425f13'; // Pre-generated hash

    public function login(Request $request): JsonResponse
    {
        $receivedHash = $request->header('source-code');

        if ($receivedHash !== $this->sourceCodeHash) {
            return response()->json(['errors' => 'Invalid Access'], 422);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');
        $user = User::where('email', strip_tags($credentials['email']))->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid login details'], 422);
        }

        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Email not verified'], 401);
        }

        if (Auth::attempt($credentials)) {
            $token = $user->createToken('api-token')->plainTextToken;
            $user->update(['api_token'=>$token,'last_activity'=>now()]);
            return response()->json(['message' => 'Login successful', 'user'=>$user, 'token' => $token], 200);
        } else {
            return response()->json(['error' => 'Invalid email or password', 'message' => 'Invalid email or password'], 401);
        }
    }


    public function register(Request $request): JsonResponse
    {

        $receivedHash = $request->header('source-code');

        if ($receivedHash !== $this->sourceCodeHash) {
            return response()->json(['errors' => 'Invalid Access'], 422);
        }
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'first_name' => htmlspecialchars(strtolower($request->input('firstName')), ENT_QUOTES, 'UTF-8'),
            'middle_ame' => htmlspecialchars(strtolower($request->input('middleName')), ENT_QUOTES, 'UTF-8'),
            'last_name' => htmlspecialchars(strtolower($request->input('lastName')), ENT_QUOTES, 'UTF-8'),
            'phone' => htmlspecialchars(strtolower($request->input('phone')), ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($request->input('email'), FILTER_SANITIZE_EMAIL),
            'password' => Hash::make($request->input('password')),
            'user_type' => 'user',
            'status' => 'not verified',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        $response = [
            'token' => $token,
            'user' => $user,
            'message' => 'An OTP has been sent to your email: ' . $request->email . '. Kindly use it to verify your account.',
            'success' => true,
        ];

        $user->notify(new EmailNotification());

        return response()->json($response, 201);
    }

    public function confirmEmail(Request $request): JsonResponse
    {
        $receivedHash = $request->header('source-code');

        if ($receivedHash !== $this->sourceCodeHash) {
            return response()->json(['errors' => 'Invalid Access'], 422);
        }
        $otp = new OTP;
        $status = $otp->validate(strip_tags($request->email), strip_tags($request->otp));

        if ($status->status === false) {
            return response()->json($status, 422);
        }
        User::where('email', strip_tags($request->email))->update(['email_verified_at' => now()]);

        return response()->json(['message' => 'Email confirmed successfully'], 200);
    }

    public function generateOtp(Request $request): JsonResponse
    {
        $receivedHash = $request->header('source-code');

        if ($receivedHash !== $this->sourceCodeHash) {
            return response()->json(['errors' => 'Invalid Access'], 422);
        }
        $email = filter_var($request->email, FILTER_SANITIZE_EMAIL);
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->notify(new EmailNotification());
            return response()->json(['message' => 'OTP has been sent to your email.'], 200);
        } else {
            return response()->json(['message' => 'User does not exist.'], 404);
        }
       
    }
    public function changePassword(request $request){
        $receivedHash = $request->header('source-code');

        if ($receivedHash !== $this->sourceCodeHash) {
            return response()->json(['errors' => 'Invalid Access'], 422);
        }
        $otp = new OTP;
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|confirmed',
            'otp' => 'required|digits:6',
        ]);
        

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
       
        $email = filter_var($request->email, FILTER_SANITIZE_EMAIL);
        $user = User::where('email', $email)->first();
        $status = $otp->validate(strip_tags($request->email), strip_tags($request->otp));

        if ($status->status === false) {
            return response()->json($status, 422);
        }
        if ($user){

            User::where('email', strip_tags($request->email))->update(['email_verified_at' => now(), 'password'=>Hash::make($request->input('password'))]);
            return response()->json(['message' => 'Password changed successfully'], 201);
        }else{
            return response()->json(['message' => 'User does not exist.'], 404);
        }

    }
    public function logout(request $request){

       $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);

    }
    public function registerAdmin(request $request){
        $receivedHash = $request->header('source-code');

        if ($receivedHash !== $this->sourceCodeHash) {
            return response()->json(['errors' => 'Invalid Access'], 422);
        }
    
        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/',
            'password' => 'required|min:6|confirmed',
    
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        if (User::where('user_type', 'system_admin')->exists()) {
            return response()->json(['errors' => ['user' => ['A system admin already exists.']]], 422);
        }

        $user = User::create([
            'first_name' => htmlspecialchars(strtolower($request->input('firstName')), ENT_QUOTES, 'UTF-8'),
            'middle_name' => htmlspecialchars(strtolower($request->input('middleName')), ENT_QUOTES, 'UTF-8'),
            'last_name' => htmlspecialchars(strtolower($request->input('lastName')), ENT_QUOTES, 'UTF-8'),
            'phone' => htmlspecialchars(strtolower($request->input('phone')), ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($request->input('email'), FILTER_SANITIZE_EMAIL),
            'password' => Hash::make($request->input('password')),
            'user_type' => 'admin',
            'status' => 'not verified',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;
$this->createInitialPerm($user);
        $response = [
            'token' => $token,
            'user' => $user,
            'message' => 'An OTP has been sent to your email: ' . $request->email . '. Kindly use it to verify your account.',
            'success' => true,
        ];

        $user->notify(new EmailNotification());

        return response()->json($response, 201);

    }


    private function createInitialPerm($user)
{

// Use transaction to ensure atomic operations
return \DB::transaction(function () use ($user) {
    $permissions = ['create role','create user','create permission', 'assign permission',
    'create vehicle','edit vehicle','delete vehicle','create device','edit device',
    'delete device','create subscription','delete subscription','create driver', 'update driver','delete driver'];
    $defaultGuard = 'sanctum';
    //Auth::getDefaultDriver();

    // Create role and assign to user
    $role = Role::create([
        'name' => 'super admin',
        'guard_name' => $defaultGuard,
        'created_by_user_id' => $user->id,
    ]);

    // Create permissions and assign to role and user in one go
    foreach ($permissions as $permissionName) {
        $permission = Permission::create([
            'name' => $permissionName,
            'guard_name' => $defaultGuard,
            'created_by_user_id' => $user->id,
        ]);
        $role->givePermissionTo($permission);
        $user->givePermissionTo($permission);
    }

    $user->assignRole($role);

    // Update system admin status
    $user->update(['status' => true, 'user_type' => 'system_admin']);

    return response()->json(['success' => true, 'message' => 'Permissions set'], 201);
});
}

}
