<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Organization;
use App\Models\CaseModel;
use App\Models\Project;
use App\Models\PendingDonation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    // ✅ عرض الملف الشخصي + الإحصائيات + آخر النشاطات
    public function show(Request $request)
    {
        $user = $request->user();

        $donationsCount = Donation::where('user_id', $user->id)->count();
        $projectsCount = Project::where('created_by_id', $user->id)->count();
        $casesCount = CaseModel::whereHas('organization', fn($q) => $q->where('user_id', $user->id))->count();

        $organization = Organization::where('user_id', $user->id)->first();

        // آخر النشاطات (آخر 3 تبرعات / مشاريع)
        $recentDonations = Donation::where('user_id', $user->id)
            ->latest()->take(3)->get(['id', 'amount', 'status', 'created_at']);
        $recentProjects = Project::where('created_by_id', $user->id)
            ->latest()->take(3)->get(['id', 'title', 'status', 'created_at']);
        $recentPendingDonations = PendingDonation::where('user_id', $user->id)
            ->latest()->take(3)->get(['id', 'amount', 'status', 'created_at']);

        return response()->json([
            'user' => $user,
            'stats' => [
                'donations' => $donationsCount,
                'projects' => $projectsCount,
                'cases' => $casesCount,
            ],
            'organization_status' => $organization?->status,
            'organization' => $organization,
            'recent_activity' => [
                'donations' => $recentDonations,
                'projects' => $recentProjects,
                'pending_donations' => $recentPendingDonations,
                'last_login' => $user->updated_at,
                'joined_at' => $user->created_at,
            ]
        ]);
    }

    // ✅ تعديل المعلومات الأساسية (الاسم، الإيميل، الصورة، الغلاف، النبذة)
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'bio' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'photo_url' => 'nullable|url',
            'cover_image_url' => 'nullable|url',
        ]);

        // حفظ الصور
        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('profile_photos', 'public');
        } elseif ($request->filled('photo_url')) {
            $data['photo_path'] = $request->input('photo_url');
        }

        if ($request->hasFile('cover_image')) {
            if ($user->cover_image) {
                Storage::disk('public')->delete($user->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('cover_images', 'public');
        } elseif ($request->filled('cover_image_url')) {
            $data['cover_image'] = $request->input('cover_image_url');
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    // ✅ تغيير كلمة المرور
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password updated successfully']);
    }
}
