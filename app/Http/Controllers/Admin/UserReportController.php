<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserReport;
use Illuminate\Http\Request;

class UserReportController extends Controller
{
    public function userReport(Request $request)
    {
        $user_reports = UserReport::all();

        return response()->json([
            'status' => true,
            'message' => 'All user reports',
            'data' => $user_reports
        ]);
    }
}