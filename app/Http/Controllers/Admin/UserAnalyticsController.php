<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserAnalyticsController extends Controller
{
    public function userAnalytics(Request $request)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $twelveMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        // Daily Active Users
        $dau = User::where('role', 'USER')->whereDate('last_login_at', $today)->count();

        // Monthly Active Users
        $mau = User::where('role', 'USER')->whereBetween('last_login_at', [$startOfMonth, now()])->count();

        // Monthly Signups (this month only)
        $msup = User::where('role', 'USER')->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        // Actual signups from DB
        $signups = User::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
            DB::raw('COUNT(*) as count')
        )
            ->where('role', 'USER')
            ->where('created_at', '>=', $twelveMonthsAgo)
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        // Build last 6 months structure with 0 fallback
        $monthlySignups = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $monthlySignups[] = [
                'month' => $date->format('M'), // e.g. July
                'count' => $signups[$key]->count ?? 0
            ];
        }

        // // Build current month + future 5 months structure with 0 fallback
        // $monthlySignups = [];
        // for ($i = 0; $i < 6; $i++) {
        //     $date = Carbon::now()->addMonths($i); // current to future
        //     $key = $date->format('Y-m');

        //     $monthlySignups[] = [
        //         'month' => $date->format('M-Y'), // Ex: Jul-2025
        //         'count' => $signups[$key]->count ?? 0
        //     ];
        // }

        return response()->json([
            'status' => true,
            'message' => 'User statistics',
            'data' => [
                'monthly_active_users' => $mau,
                'daily_active_users' => $dau,
                'monthly_signups' => $msup,
                'monthly_signups_new_users' => $monthlySignups,
            ]
        ]);
    }
}
