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

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('created_by_type', $type);
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        return response()->json($projects);
    }

    // ✅ إنشاء مشروع جديد (من أدمن أو جمعية)
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goal_amount' => 'required|numeric|min:0',
            'deadline' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        $user = $request->user();

        // إذا المستخدم أدمن → مشروع مباشر
        $createdByType = $user->user_type === 'admin' ? 'admin' : 'organization';
        $status = $user->user_type === 'admin' ? 'approved' : 'pending';

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'goal_amount' => $request->goal_amount,
            'deadline' => $request->deadline,
            'created_by_id' => $user->id,
            'created_by_type' => $createdByType,
            'status' => $status,
        ];

        // رفع صورة المشروع إن وُجدت
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('projects', 'public');
        }

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

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'goal_amount' => 'sometimes|numeric|min:0',
            'deadline' => 'nullable|date',
            'status' => 'in:pending,approved,rejected,completed',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        // إذا تم رفع صورة جديدة نحذف القديمة
        if ($request->hasFile('image')) {
            if ($project->image_path) {
                Storage::disk('public')->delete($project->image_path);
            }
            $project->image_path = $request->file('image')->store('projects', 'public');
        }

        $project->fill($request->only(['title', 'description', 'goal_amount', 'deadline', 'status']));
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
