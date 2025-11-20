<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Mail\OrganizationApproved;
use App\Mail\OrganizationRejected;
use Illuminate\Support\Facades\Mail;

class OrganizationController extends Controller
{
    // عرض جميع المنظمات (مع إمكانية الفلترة حسب الحالة)
    public function index(Request $request)
    {
        $query = Organization::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return $query->get();
    }

    // الموافقة على منظمة
    public function approve(Organization $organization)
    {
        if ($organization->status !== 'pending') {
            return response()->json(['message' => 'Organization is not pending approval.'], 409);
        }

        $organization->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // إرسال إيميل للمنظمة
        Mail::to($organization->user->email)->send(new OrganizationApproved($organization));

        return response()->json(['message' => 'Organization approved successfully.', 'organization' => $organization]);
    }

    // رفض منظمة
    public function reject(Request $request, Organization $organization)
    {
        $request->validate(['reason' => 'required|string|min:10']);

        if ($organization->status !== 'pending') {
            return response()->json(['message' => 'Organization is not pending approval.'], 409);
        }

        $organization->update([
            'status' => 'rejected',
            'admin_notes' => $request->reason, // حفظ سبب الرفض
        ]);

        // إرسال إيميل للمنظمة مع سبب الرفض
        Mail::to($organization->user->email)->send(new OrganizationRejected($organization, $request->reason));

        return response()->json(['message' => 'Organization rejected successfully.']);
    }
}