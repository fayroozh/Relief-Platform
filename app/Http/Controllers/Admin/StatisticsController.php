<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Organization;
use App\Models\Project;
use App\Models\CaseModel;
use App\Models\Donation;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        // وقت التخزين مؤقت (بالثواني) — قابل للتعديل عبر ?cache_ttl=30
        $ttl = (int) $request->get('cache_ttl', 60);

        $stats = Cache::remember('admin_statistics', $ttl, function () {
            // أساسيّات العدادات
            $usersCount = User::count();
            $organizationsCount = Organization::count();
            $projectsCount = Project::count();

            // التبرعات (مجموع ومجموع عدد السجلات)
            $donationsTotalAmount = (float) Donation::sum('amount');
            $donationsCount = Donation::count();

            // الحالات بحسب الحالة
            $casesByStatus = CaseModel::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'); // يعيد مصفوفة ['pending' => 5, ...]

            // احصائية تقدم الحالات (نسبة الانجاز لكل حالة) — آخر 10 حالات
            $casesProgress = CaseModel::select(
                'id',
                'title',
                'goal_amount',
                'collected_amount',
                DB::raw('CASE WHEN goal_amount > 0 THEN LEAST(100, (collected_amount / goal_amount) * 100) ELSE 0 END as progress')
            )
                ->orderByDesc('progress')
                ->take(10)
                ->get();

            // أفضل 5 مشاريع حسب مجموع التبرعات — يحتاج علاقة donations على Project model
            $topProjects = Project::withSum('donations', 'amount')
                ->orderByDesc('donations_sum_amount') // الاسم الصحيح
                ->take(5)
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'title' => $p->title ?? $p->name ?? null,
                        'donations_sum' => (float) ($p->donations_sum_amount ?? 0), // نفس الاسم
                    ];
                });


            // ترند التبرعات الشهري للـ 6 أشهر الماضية
            $monthly = Donation::select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('SUM(amount) as total'))
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            return [
                'users_count' => $usersCount,
                'organizations_count' => $organizationsCount,
                'projects_count' => $projectsCount,
                'donations' => [
                    'total_amount' => $donationsTotalAmount,
                    'total_count' => $donationsCount,
                ],
                'cases_by_status' => $casesByStatus,
                'top_projects' => $topProjects,
                'cases_progress' => $casesProgress,
                'monthly_donations' => $monthly,
                'generated_at' => now()->toDateTimeString(),
            ];
        });

        return response()->json($stats);
    }
}
