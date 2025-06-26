<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // // user register
    // public function register(Request $request)
    // {
    //     // create otp
    //     $otp            = rand(100000, 999999);
    //     $otp_expires_at = Carbon::now()->addMinutes(10);

    //     // Send OTP Email
    //     $email_otp = [
    //         'userName' => explode('@', $request->email)[0],
    //         'otp' => $otp,
    //         'validity' => '10 minute'
    //     ];

    //     // rear case handle
    //     $rearUser = User::where('email', $request->email)->first();

    //     if (($rearUser && $rearUser->verified_status == 'unverified')) {

    //         // update otp and otp expires
    //         $rearUser->otp = $otp;
    //         $rearUser->otp_expires_at = $otp_expires_at;
    //         $rearUser->save();

    //         try {
    //             Mail::to($rearUser->email)->send(new VerifyOTPMail($email_otp));
    //         } catch (Exception $e) {
    //             Log::error($e->getMessage());
    //         }

    //         // json response
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Your account already exists, please verify your account, check you email for OTP'
    //         ], 201);
    //     }

    //     // validation roles
    //     $validator = Validator::make($request->all(), [
    //         'full_name'             => 'required|string|max:255',
    //         'user_name'             => 'sometimes|max:255',
    //         'email'                 => 'required|string|email|max:255|unique:users,email',
    //         'password'              => 'required|string|min:6|confirmed',
    //     ]);

    //     // check validation
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message'   => $validator->errors()
    //         ], 422);
    //     }

    //     $user = User::create([
    //         'name'           => ucfirst($request->full_name),
    //         'user_name'      => $request->user_name ? '@' . ucfirst($request->user_name) . '_' . rand(0, 9) : '@' . explode(' ', trim($request->full_name))[0] . '_' . rand(0, 9),
    //         'email'          => $request->email,
    //         'password'       => Hash::make($request->password),
    //         'otp'            => $otp,
    //         'otp_expires_at' => $otp_expires_at,
    //     ], 201);

    //     try {
    //         Mail::to($user->email)->send(new VerifyOTPMail($email_otp));
    //     } catch (Exception $e) {
    //         Log::error($e->getMessage());
    //     }

    //     // json response
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Register successfully, OTP send you email, please verify your account'
    //     ], 201);
    // }

    // // verify otp
    // public function verifyOtp(Request $request)
    // {

    //     $validator = Validator::make($request->all(), [
    //         'otp' => 'required|numeric',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()
    //         ], 422);
    //     }

    //     $user = User::where('otp', $request->otp)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message'  => 'Invalid OTP'
    //         ], 401);
    //     }

    //     // check otp
    //     if ($user->otp_expires_at > Carbon::now()) {

    //         // active
    //         $user->last_login_at = Carbon::now();
    //         $user->user_status   = 'active';

    //         // user status update
    //         $user->otp                = null;
    //         $user->otp_expires_at     = null;
    //         $user->otp_verified_at    = Carbon::now();
    //         $user->verified_status    = 'verified';
    //         $user->save();

    //         // custom token time
    //         $tokenExpiry = Carbon::now()->addDays(7);
    //         $customClaims = ['exp' => $tokenExpiry->timestamp];
    //         $token = JWTAuth::customClaims($customClaims)->fromUser($user);

    //         // Generate JWT Token
    //         // $token = JWTAuth::fromUser($user);

    //         // json response
    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'Email verified successfully',
    //             'access_token' => $token,
    //             'token_type' => 'bearer',
    //             'expires_in' => $tokenExpiry,
    //             // 'expires_in' => $tokenExpiry->diffInSeconds(Carbon::now()),
    //             // 'expires_in' => JWTAuth::factory()->getTTL() * 60,
    //         ], 200);
    //     } else {

    //         return response()->json([
    //             'status' => false,
    //             'message'  => 'OTP expired time out'
    //         ], 401);
    //     }
    // }

    // // resend otp
    // public function resendOtp(Request $request)
    // {
    //     // validation roles
    //     $validator = Validator::make($request->all(), [
    //         'email'                 => 'required|string|email',
    //     ]);

    //     // check validation
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message'   => $validator->errors()
    //         ], 422);
    //     }

    //     // Check if User Exists
    //     $user = User::where('email', $request->email)->first();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found'
    //         ], 404);
    //     }

    //     $otp            = rand(100000, 999999);
    //     $otp_expires_at = Carbon::now()->addMinutes(10);

    //     // email user check
    //     if ($user->verified_status == 'unverified') {

    //         // update otp and otp expired at
    //         $user->otp = $otp;
    //         $user->otp_expires_at = $otp_expires_at;
    //         $user->otp_verified_at = null;
    //         $user->save();
    //     } else {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'User already verified.'
    //         ], 200);
    //     }

    //     // Send OTP Email
    //     $data = [
    //         'userName' => explode('@', $request->email)[0],
    //         'otp' => $otp,
    //         'validity' => '10 minute'
    //     ];

    //     try {
    //         Mail::to($user->email)->send(new VerifyOTPMail($data));
    //     } catch (Exception $e) {
    //         Log::error($e->getMessage());
    //     }

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'OTP resend to your email'
    //     ], 200);
    // }

    // user login
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
                'message' => 'Not found',
            ], 404);
        }

        // Check Account Status
        if ($user->status != 'active') {
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact support.',
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

    // User Logout
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status' => true,
                'message' => 'Logged out successful'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to logout, please try again'
            ], 500);
        }
    }

    // // forgot password
    // public function forgotPassword(Request $request)
    // {
    //     // Validation Rules
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email|max:255',
    //     ]);

    //     // Return Validation Errors
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors(),
    //         ], 422);
    //     }

    //     // Check if User Exists
    //     $user = User::where('email', $request->email)->first();

    //     // User Not Found
    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found',
    //         ], 404);
    //     }

    //     // create otp
    //     $otp = rand(100000, 999999);
    //     $otp_expires_at = Carbon::now()->addMinutes(10);

    //     if ($user->verified_status == 'verified') {

    //         // update otp and otp veridied and otp expired at
    //         $user->otp_verified_at = null;
    //         $user->otp             = $otp;
    //         $user->otp_expires_at  = $otp_expires_at;
    //         $user->verified_status          = 'unverified';
    //         $user->save();
    //     } else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not verified',
    //         ], 404);
    //     }

    //     $data = [
    //         'userName' => explode('@', $request->email)[0],
    //         'otp' => $otp,
    //         'validity' => '10 minutes'
    //     ];

    //     try {
    //         Mail::to($request->email)->send(new VerifyOTPMail($data));
    //     } catch (Exception $e) {
    //         Log::error($e->getMessage());
    //     }

    //     return response()->json([
    //         'status'  => true,
    //         'message' => 'OTP send to your email'
    //     ], 200);
    // }

    // // after forgot password then change password
    // public function changePassword(Request $request)
    // {

    //     // Validation Rules
    //     $validator = Validator::make($request->all(), [
    //         'password' => 'required|string|min:6|confirmed'
    //     ]);

    //     // Return Validation Errors
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors(),
    //         ], 422);
    //     }

    //     // Check if User Exists
    //     $user = User::where('email', Auth::user()->email)->first();

    //     // User Not Found
    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Unauthenticated',
    //         ], 404);
    //     };

    //     if ($user->verified_status == 'verified') {
    //         $user->password = Hash::make($request->password);
    //         $user->save();
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Password change successfully!',
    //         ]);
    //     } else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not verified'
    //         ]);
    //     }
    // }

    // user profile by id
    public function profile()
    {
        $user = User::find(Auth::id());
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Your profile',
            'data' => $user
        ], 200);
    }

    // // user update your account password
    // public function updatePassword(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'current_password' => 'required|min:6',
    //         'password'         => 'required|string|min:6|confirmed'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()
    //         ], 422);
    //     }

    //     $user = User::find(Auth::id());

    //     if (! $user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found'
    //         ], 404);
    //     }

    //     if (Hash::check($request->current_password, $user->password)) {
    //         $user->password = Hash::make($request->password);
    //         $user->save();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Password updated successfully!',
    //         ]);
    //     } else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invalid current password!',
    //         ]);
    //     }
    // }

    // // upload avatar
    // public function avatar(Request $request)
    // {
    //     $user = User::findOrFail(Auth::id());


    //     $validator = Validator::make($request->all(), [
    //         'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()
    //         ], 422);
    //     }

    //     if ($request->hasFile('avatar')) {
    //         $file      = $request->file('avatar');
    //         $filename  = time() . '_' . $file->getClientOriginalName();
    //         $filepath  = $file->storeAs('avatars', $filename, 'public');

    //         $user->avatar = '/storage/' . $filepath;
    //         $user->save();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Image uploaded successfully!',
    //             'path'    => $user->avatar,
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => false,
    //         'message' => 'No image uploaded!',
    //     ], 400);
    // }

    // // update profile avatar
    // public function updateAvatar(Request $request)
    // {
    //     $user = User::findOrFail(Auth::id());

    //     $validator = Validator::make($request->all(), [
    //         'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()
    //         ], 422);
    //     }

    //     if ($request->hasFile('avatar')) {
    //         if ($user->avatar && file_exists(public_path($user->avatar))) {
    //             unlink(public_path($user->avatar));
    //         }

    //         $file      = $request->file('avatar');
    //         $filename  = time() . '_' . $file->getClientOriginalName();
    //         $filepath  = $file->storeAs('avatars', $filename, 'public');

    //         $user->avatar = '/storage/' . $filepath;
    //         $user->save();

    //         return response()->json([
    //             'status'      => true,
    //             'message' => 'Avatar updated successfully!',
    //             'path'    => $user->avatar,
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => false,
    //         'message' => 'No image uploaded!',
    //     ], 400);
    // }
}