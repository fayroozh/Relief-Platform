<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    // ✅ عرض جميع المشاريع (مع فلترة بالحالة أو النوع)
    public function index(Request $request)
    {
        $query = Project::query();
        $user = $request->user();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('created_by_type', $type);
        }

        if (!$user || $user->user_type !== 'admin') {
            $query->where('status', 'approved');
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        return response()->json($projects);
    }

    // ✅ إنشاء مشروع جديد (من أدمن أو جمعية)
    public function store(Request $request)
    {
        // معالجة حقول JSON القادمة من FormData (تأتي كسلسلة نصية أحياناً)
        if ($request->has('suggested_amounts') && is_string($request->input('suggested_amounts'))) {
            $request->merge(['suggested_amounts' => json_decode($request->input('suggested_amounts'), true)]);
        }
        if ($request->has('payment_channels') && is_string($request->input('payment_channels'))) {
            $request->merge(['payment_channels' => json_decode($request->input('payment_channels'), true)]);
        }
        // تحويل القيم المنطقية من نصوص ("true"/"false") إلى boolean
        if ($request->has('allow_custom_amount')) {
            $request->merge(['allow_custom_amount' => filter_var($request->allow_custom_amount, FILTER_VALIDATE_BOOLEAN)]);
        }
        if ($request->has('enable_repetition')) {
            $request->merge(['enable_repetition' => filter_var($request->enable_repetition, FILTER_VALIDATE_BOOLEAN)]);
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goal_amount' => 'required|numeric|min:0',
            'deadline' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'manager_name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'suggested_amounts' => 'nullable|array',
            'payment_channels' => 'nullable|array',
            'thank_you_message' => 'nullable|string',
            'allow_custom_amount' => 'boolean',
            'enable_repetition' => 'boolean',
            'repetition_type' => 'nullable|string|in:weekly,monthly',
        ]);

        $user = $request->user();

        // إذا المستخدم أدمن → مشروع مباشر
        $createdByType = $user->user_type === 'admin' ? 'admin' : 'organization';
        $status = $user->user_type === 'admin' ? 'approved' : 'pending';

        $data = $validatedData;
        $data['created_by_id'] = $user->id;
        $data['created_by_type'] = $createdByType;
        $data['status'] = $status;

        // رفع صورة المشروع إن وُجدت
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('projects', 'public');
        }
        // إزالة 'image' من المصفوفة لأننا خزنا المسار في 'image_path'
        unset($data['image']);

        $project = Project::create($data);

        return response()->json([
            'message' => $user->user_type === 'admin'
                ? 'Project created and published successfully'
                : 'Project submitted and pending admin approval',
            'project' => $project,
        ], 201);
    }

    // ✅ عرض مشروع محدد
    public function show(Project $project)
    {
        return response()->json($project);
    }

    // ✅ تحديث مشروع (جمعية أو أدمن)
    public function update(Request $request, Project $project)
    {
        $user = $request->user();

        // تحقق من صلاحية التعديل
        if ($user->user_type !== 'admin' && $project->created_by_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // معالجة حقول JSON القادمة من FormData
        if ($request->has('suggested_amounts') && is_string($request->input('suggested_amounts'))) {
            $request->merge(['suggested_amounts' => json_decode($request->input('suggested_amounts'), true)]);
        }
        if ($request->has('payment_channels') && is_string($request->input('payment_channels'))) {
            $request->merge(['payment_channels' => json_decode($request->input('payment_channels'), true)]);
        }
        if ($request->has('allow_custom_amount')) {
            $request->merge(['allow_custom_amount' => filter_var($request->allow_custom_amount, FILTER_VALIDATE_BOOLEAN)]);
        }
        if ($request->has('enable_repetition')) {
            $request->merge(['enable_repetition' => filter_var($request->enable_repetition, FILTER_VALIDATE_BOOLEAN)]);
        }

        $validatedData = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'goal_amount' => 'sometimes|numeric|min:0',
            'deadline' => 'nullable|date',
            'status' => 'in:pending,approved,rejected,completed',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'manager_name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'suggested_amounts' => 'nullable|array',
            'payment_channels' => 'nullable|array',
            'thank_you_message' => 'nullable|string',
            'allow_custom_amount' => 'boolean',
            'enable_repetition' => 'boolean',
            'repetition_type' => 'nullable|string|in:weekly,monthly',
        ]);

        // إذا تم رفع صورة جديدة نحذف القديمة
        if ($request->hasFile('image')) {
            if ($project->image_path) {
                Storage::disk('public')->delete($project->image_path);
            }
            $validatedData['image_path'] = $request->file('image')->store('projects', 'public');
        }
        unset($validatedData['image']);

        $project->fill($validatedData);
        $project->save();

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project,
        ]);
    }

    // ✅ حذف مشروع (أدمن فقط)
    public function destroy(Project $project, Request $request)
    {
        $user = $request->user();

        if ($user->user_type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($project->image_path) {
            Storage::disk('public')->delete($project->image_path);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }

    // ✅ موافقة الأدمن على مشروع جمعية
    public function approve(Request $request, $id)
    {
        $user = $request->user();

        if ($user->user_type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $project = Project::findOrFail($id);
        $project->update(['status' => 'approved']);

        return response()->json(['message' => 'Project approved', 'project' => $project]);
    }

    // ✅ رفض مشروع جمعية
    public function reject(Request $request, $id)
    {
        $user = $request->user();

        if ($user->user_type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['reason' => 'nullable|string|max:1000']);
        $project = Project::findOrFail($id);
        $project->update([
            'status' => 'rejected',
            'admin_notes' => $request->reason,
        ]);

        return response()->json(['message' => 'Project rejected', 'project' => $project]);
    }
}
