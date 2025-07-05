<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserAnalyticsController extends Controller
{
    // public function userAnalytics(Request $request)
    // {

    //     $today = Carbon::today();
    //     $startOfMonth = Carbon::now()->startOfMonth();
    //     return $endOfMonth = Carbon::now()->endOfMonth();
    //     $startOfWeek = Carbon::now()->startOfWeek();

    //     // Daily Active Users (last_login_at is today)
    //     $dau = User::where('role','USER')->whereDate('last_login_at', $today)->count();

    //     // Weekly Active Users
    //     // $wau = User::where('role','USER')->whereBetween('last_login_at', [$startOfWeek, Carbon::now()])->count();

    //     // Monthly Active Users
    //     $mau = User::where('role','USER')->whereBetween('last_login_at', [$startOfMonth, Carbon::now()])->count();

    //     // Monthly Signups (based on created_at)
    //     $monthlySignups = User::where('role','USER')->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'User statistics',
    //         'data' => [
    //             // 'weekly_active_users'  => $wau,
    //             'monthly_active_users' => $mau,
    //             'daily_active_users'   => $dau,
    //             'monthly_signups'      => $monthlySignups,
    //         ]
    //     ]);
    // }

    // public function userAnalytics(Request $request)
    // {
    //     $today = Carbon::today();
    //     $startOfMonth = Carbon::now()->startOfMonth();
    //     $endOfMonth = Carbon::now()->endOfMonth();

    //     // Daily Active Users (last_login_at is today)
    //     $dau = User::where('role', 'USER')->whereDate('last_login_at', $today)->count();

    //     // Monthly Active Users
    //     $mau = User::where('role', 'USER')->whereBetween('last_login_at', [$startOfMonth, now()])->count();

    //     // Monthly Signups (based on created_at)
    //     $msup = User::where('role', 'USER')->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();


    //     // Monthly Signups Grouped by Month
    //     $monthlySignups = User::select(
    //         DB::raw("DATE_FORMAT(created_at, '%Y-%M') as month"),
    //         DB::raw('COUNT(*) as count')
    //     )
    //         ->where('role', 'USER')
    //         ->groupBy('month')
    //         ->orderBy('month', 'desc')
    //         ->take(12)
    //         ->get();




    //     return response()->json([
    //         'status' => true,
    //         'message' => 'User statistics',
    //         'data' => [
    //             'monthly_active_users' => $mau,
    //             'daily_active_users'   => $dau,
    //             'monthly_signups'      => $msup,
    //             'monthly_signups_new_users'      => $monthlySignups,
    //         ]
    //     ]);
    // }

    public function userAnalytics(Request $request)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // 6 মাস আগের তারিখ
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        // Daily Active Users
        $dau = User::where('role', 'USER')->whereDate('last_login_at', $today)->count();

        // Monthly Active Users
        $mau = User::where('role', 'USER')->whereBetween('last_login_at', [$startOfMonth, now()])->count();

        // Monthly Signups (this month only)
        $msup = User::where('role', 'USER')->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        // Monthly Signups (last 6 months)
        $monthlySignups = User::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%M') as month"),
            DB::raw('COUNT(*) as count')
        )
            ->where('role', 'USER')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('month')
            ->orderBy('month', 'asc') // ascending for chart plotting
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'User statistics',
            'data' => [
                'monthly_active_users'       => $mau,
                'daily_active_users'         => $dau,
                'monthly_signups'            => $msup,
                'monthly_signups_new_users'  => $monthlySignups,
            ]
        ]);
    }
}
