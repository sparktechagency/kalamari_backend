<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderCompany;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProviderCompanyController extends Controller
{
    public function createProviderCompany(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'provider_types' => 'required',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'email'                 => 'required|string|email|max:255|unique:users,email',
            'password'              => 'required|string|min:6',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // return $request->all();

        $provider_company = User::create([
            'name'  => 'unknown',
            'role'  => 'COMPANY',
            'status' => 'active',
            'types' => $request->provider_types,
            'city'  => $request->city,
            'state' => $request->state,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Provider_company created successful',
            'data' => $provider_company
        ], 201);
    }

    public function getProviderCompanies(Request $request)
    {
        if ($request->user_id == true) {
            $users = User::where('id',$request->user_id)->where('role','COMPANY');

            $user_name = $users->name;
        } else {
            $users = User::where('role','COMPANY')->get();
        }

        return response()->json([
            'status' => true,
            'message' => $request->user_id ? 'Get ' . $user_name . ' company' : 'Get all provider companies',
            'data' => $users
        ], 200);
    }
}
