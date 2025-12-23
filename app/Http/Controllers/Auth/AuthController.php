<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyOTPMail;
use App\Models\Contact;
use App\Models\Follower;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewUserCreationNotification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use PhpParser\JsonDecoder;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // create otp
        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        // Send OTP Email
        $email_otp = [
            'userName' => explode('@', $request->email)[0],
            'otp' => $otp,
            'validity' => '10 minute'
        ];

        // validation roles
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'user_name' => 'sometimes|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'contact_number' => 'required',
            'country_code' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => ucfirst($request->full_name),
            // 'user_name' => $request->user_name ? '@' . ucfirst($request->user_name) . '_' . rand(0, 9) : '@' . explode(' ', trim($request->full_name))[0] . '_' . rand(0, 9),
            'user_name' => $request->user_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'country_code' => $request->country_code,
            'contact_number_with_code' => $request->country_code . $request->contact_number,
            'password' => Hash::make($request->password),
            'otp' => $otp,
            'otp_expires_at' => $otp_expires_at,
        ]);

        try {
            Mail::to($user->email)->send(new VerifyOTPMail($email_otp));
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        $notifyUser = User::where('role', 'ADMIN')->first();

        $notifyUser->notify(new NewUserCreationNotification($user));

        return response()->json([
            'status' => true,
            'message' => 'Register successfully, OTP send you email, please verify your account'
        ], 201);
    }
    public function searchUserName(Request $request)
    {
        $userName = User::where('user_name', $request->search_user_name)
            ->exists();

        $message = $userName ? 'already exist' : 'not found';
        return response()->json([
            'status' => true,
            'message' => 'User' . ' ' . $message,
            'exist' => $userName ? true : false,
        ]);
    }
    public function searchUserEmail(Request $request)
    {
        $userEmail = User::where('email', $request->search_user_email)
            ->exists();
        $message = $userEmail ? 'already exist' : 'not found';

        return response()->json([
            'status' => true,
            'message' => 'Email' . ' ' . $message,
            'exist' => $userEmail ? true : false,
        ]);
    }
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::where('otp', $request->otp)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP'
            ], 401);
        }

        // check otp
        if ($user->otp_expires_at > Carbon::now()) {

            // active
            $user->last_login_at = Carbon::now();
            $user->user_status = 'active';

            // user status update
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->otp_verified_at = Carbon::now();
            $user->verified_status = 'verified';
            $user->save();

            // custom token time
            $tokenExpiry = Carbon::now()->addDays(7);
            $customClaims = ['exp' => $tokenExpiry->timestamp];
            $token = JWTAuth::customClaims($customClaims)->fromUser($user);

            // Generate JWT Token
            // $token = JWTAuth::fromUser($user);

            // json response
            return response()->json([
                'status' => true,
                'message' => 'Email verified successfully',
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $tokenExpiry,
                // 'expires_in' => $tokenExpiry->diffInSeconds(Carbon::now()),
                // 'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ], 200);
        } else {

            return response()->json([
                'status' => false,
                'message' => 'OTP expired time out'
            ], 401);
        }
    }
    public function resendOtp(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // Check if User Exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        // email user check
        if ($user->verified_status == 'unverified') {

            // update otp and otp expired at
            $user->otp = $otp;
            $user->otp_expires_at = $otp_expires_at;
            $user->otp_verified_at = null;
            $user->save();
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User already verified.'
            ], 200);
        }

        // Send OTP Email
        $data = [
            'userName' => explode('@', $request->email)[0],
            'otp' => $otp,
            'validity' => '10 minute'
        ];

        try {
            Mail::to($user->email)->send(new VerifyOTPMail($data));
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP resend to your email'
        ], 200);
    }
    public function login(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'remember_me' => 'sometimes|boolean'
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // Check if User Exists
        $user = User::where('email', $request->email)->first();

        // User Not Found
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Check Account Status
        if ($user->verified_status != 'verified') {
            return response()->json([
                'status' => false,
                'un_verified' => true,
                'message' => 'Your account is unverified. Please contact support.',
            ], 403);
        }

        // Verify Password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid password',
            ], 401);
        }

        // Generate JWT Token with remember me
        $tokenExpiry = $request->remember_me == '1' ? Carbon::now()->addDays(30) : Carbon::now()->addDays(7);
        $customClaims = ['exp' => $tokenExpiry->timestamp];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);

        // Generate JWT Token
        // $token = JWTAuth::fromUser($user);

        $user->last_login_at = Carbon::now();
        $user->user_status = 'active';
        $user->save();

        // Return Success Response
        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $tokenExpiry,
            // 'expires_in' => $tokenExpiry->diffInSeconds(Carbon::now()),
            'user' => $user,
        ], 200);
    }
    public function logout(Request $request)
    {
        try {
            // $user = User::where('id', Auth::id())->first();
            // $user->last_login_at = null;
            // $user->user_status   = 'inactive';
            // $user->save();

            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status' => true,
                'message' => 'Successfully logged out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to logout, please try again'
            ], 500);
        }
    }
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        $user->otp_verified_at = null;
        $user->otp = $otp;
        $user->otp_expires_at = $otp_expires_at;
        $user->save();

        $data = [
            'userName' => explode('@', $request->email)[0],
            'otp' => $otp,
            'validity' => '10 minutes'
        ];

        try {
            Mail::to($request->email)->queue(new VerifyOTPMail($data));
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP send to your email'
        ], 200);
    }
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', Auth::user()->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 404);
        }
        ;

        if ($user->verified_status == 'verified') {
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'Password change successfully!',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User not verified'
            ]);
        }
    }
    public function profile(Request $request)
    {
        $user = User::find($request->user_id ?? Auth::id());
        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'User not found'
            ], 404);
        }

        $followingIds = Follower::where('follower_id', Auth::id())->pluck('user_id')->toArray();

        if ($user->id == Auth::id()) {
            $user->isfollowing = null;
        } elseif (in_array($user->id, $followingIds)) {
            $user->isfollowing = true;
        } else {
            $user->isfollowing = false;
        }

        return response()->json([
            'ok' => true,
            'message' => 'User profile',
            'data' => $user
        ], 200);
    }
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:6',
            'password' => 'required|string|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::find(Auth::id());

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (Hash::check($request->current_password, $user->password)) {
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully!',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid current password!',
            ]);
        }
    }
    public function avatar(Request $request)
    {
        $user = User::findOrFail(Auth::id());


        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:20480', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filepath = $file->storeAs('avatars', $filename, 'public');

            $user->avatar = '/storage/' . $filepath;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Image uploaded successfully!',
                'path' => $user->avatar,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No image uploaded!',
        ], 400);
    }
    public function updateAvatar(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:20480', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filepath = $file->storeAs('avatars', $filename, 'public');

            $user->avatar = '/storage/' . $filepath;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Avatar updated successfully!',
                'path' => $user->avatar,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No image uploaded!',
        ], 400);
    }
    public function checkToken(Request $request)
    {
        try {
            $user = JWTAuth::setToken($request->token)->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Token is valid',
                'data' => $user
            ]);

        } catch (TokenExpiredException $e) {
            return response()->json(['status' => false, 'message' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['status' => false, 'message' => 'Invalid token'], 401);
        } catch (JWTException $e) {
            return response()->json(['status' => false, 'message' => 'Token not provided'], 400);
        }
    }
    public function storeContact(Request $request)
    {
        // Validate contact_lists as array
        $validator = Validator::make($request->all(), [
            'contact_lists' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // Store contacts as JSON string
        $contact_lists = Contact::create([
            'user_id' => Auth::id(),
            'contact_lists' => json_encode($request->contact_lists)
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Store contact lists',
            'data' => $contact_lists
        ]);
    }
    public function synContactsf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_lists' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $contactNumbers = $request->contact_lists;

        $matchedUsers = User::whereIn('contact_number', $contactNumbers)
            ->where('id', '!=', Auth::id())
            ->where('role', 'USER')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Matched registered users from contact list',
            'data' => $matchedUsers
        ]);
    }
    public function synContacts(Request $request)
    {
        // $contactNumbers = ["01797004550", "+8801797004550"];

        $validator = Validator::make($request->all(), [
            'contact_lists' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $contactNumbers = $request->contact_lists;

        $matchedUsers = User::where(function ($q) use ($contactNumbers) {
            $q->whereIn('contact_number', $contactNumbers)
                ->orWhereIn('contact_number_with_code', $contactNumbers);
        })
            ->where('id', '!=', Auth::id())
            ->where('role', 'USER')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Matched registered users from contact list',
            'data' => $matchedUsers
        ]);
    }
    public function deleteAccount(Request $request)
    {
        Auth::user()->delete();
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'status' => true,
            'message' => 'Account deleted successfully'
        ]);
    }
    public function deviceToken(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string'
        ]);

        $user = Auth::user();
        $user->device_token = $request->device_token;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Device token updated successfully',
            'device_token' => $user->device_token
        ]);
    }
}