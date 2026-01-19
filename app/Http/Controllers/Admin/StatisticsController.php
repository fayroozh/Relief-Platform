<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Models\CaseModel;
use App\Models\Donation;
use App\Models\Project;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index()
    {
        // عدد المستخدمين حسب النوع
        $userStats = [
            'total' => User::count(),
            'admins' => User::where('user_type', 'admin')->count(),
            'organizations' => User::where('user_type', 'organization')->count(),
            'donors' => User::where('user_type', 'user')->count(),
        ];

        // الجمعيات حسب الحالة
        $organizationStats = [
            'total' => Organization::count(),
            'pending' => Organization::where('status', 'pending')->count(),
            'approved' => Organization::where('status', 'approved')->count(),
            'rejected' => Organization::where('status', 'rejected')->count(),
        ];

        // الحالات حسب الحالة
        $caseStats = [
            'total' => CaseModel::count(),
            'pending' => CaseModel::where('status', 'pending')->count(),
            'approved' => CaseModel::where('status', 'approved')->count(),
            'completed' => CaseModel::where('status', 'completed')->count(),
        ];

        // المشاريع
        $projectStats = [
            'total' => Project::count(),
            'active' => Project::where('status', 'active')->count(),
            'completed' => Project::where('status', 'completed')->count(),
        ];

        // التبرعات: مجموع المبالغ وعددها
        $donationStats = [
            'total_donations' => Donation::count(),
            'total_amount' => Donation::where('status', 'completed')->sum('amount'),
            'last_month_amount' => Donation::where('status', 'completed')
                ->where('created_at', '>=', now()->subMonth())
                ->sum('amount'),
        ];

        // سلاسل يومية (آخر 30 يوم للتبرعات، آخر 7 أيام للمستخدمين)
        $donationsDaily = Donation::select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $usersDaily = User::select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // عدد الرسائل
        $messageStats = [
            'total' => Message::count(),
            'unread' => Message::where('is_read', false)->count(),
        ];

        // آخر النشاطات (اختياري للواجهة)
        $recent = [
            'latest_donations' => Donation::with('donor:id,name')->latest()->take(5)->get(),
            'latest_organizations' => Organization::latest()->take(5)->get(),
        ];

        return response()->json([
            'users' => $userStats,
            'organizations' => $organizationStats,
            'cases' => $caseStats,
            'projects' => $projectStats,
            'donations' => $donationStats,
            'messages' => $messageStats,
            'recent' => $recent,
            'series' => [
                'donations_daily' => $donationsDaily,
                'users_daily' => $usersDaily,
            ],
        ]);
    }
}
