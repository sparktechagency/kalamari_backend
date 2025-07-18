<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserReport;
use Illuminate\Http\Request;

class UserReportController extends Controller
{
    public function getReports(Request $request)
    {
        $user_reports = UserReport::all();

        foreach ($user_reports as $user_report) {
            $user_report->reporter_info = User::find($user_report->reporter_id);
            $user_report->reported_info = User::find($user_report->reported_id);
        }

        return response()->json([
            'status' => true,
            'message' => 'All reports',
            'data' => $user_reports
        ]);
    }

    public function getReport(Request $request)
    {
        $report = UserReport::find($request->report_id);

        $report->reporter_info = User::find($report->reporter_id);
        $report->reported_info = User::find($report->reported_id);

        return response()->json([
            'status' => true,
            'message' => 'View report',
            'data' => $report
        ]);
    }
}