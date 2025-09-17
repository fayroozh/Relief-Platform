<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // عرض كل المشاريع
    public function index()
    {
        return response()->json(Project::all());
    }

    // إنشاء مشروع جديد
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goal_amount' => 'required|numeric|min:0',
            'deadline' => 'nullable|date',
            'created_by_id' => 'required|integer',
            'created_by_type' => 'required|in:admin,organization',
        ]);

        $project = Project::create($request->all());

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project,
        ], 201);
    }

    // عرض مشروع محدد
    public function show(Project $project)
    {
        return response()->json($project);
    }

    // تحديث مشروع
    public function update(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'goal_amount' => 'sometimes|numeric|min:0',
            'deadline' => 'nullable|date',
            'status' => 'in:active,inactive,completed',
        ]);

        $project->update($request->all());

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project,
        ]);
    }

    // حذف مشروع
    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }
}
