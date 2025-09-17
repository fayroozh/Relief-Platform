<?php
namespace App\Http\Controllers;

use App\Models\CaseModel;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    // عرض جميع الحالات (مع إمكانية فلترة بالحالة)
    public function index(Request $request)
    {
        $status = $request->query('status');
        $cases = CaseModel::when($status, function ($q) use ($status) {
            return $q->where('status', $status);
        })->with(['organization', 'category'])->get();

        return response()->json($cases);
    }

    // إضافة حالة جديدة (مستخدم/جمعية)
    public function store(Request $request)
    {
        $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'goal_amount' => 'required|numeric|min:1',
        ]);

        $case = CaseModel::create($request->all());

        return response()->json([
            'message' => 'Case created successfully, pending approval',
            'case' => $case
        ], 201);
    }

    // تحديث حالة (للأدمن)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,completed'
        ]);

        $case = CaseModel::findOrFail($id);
        $case->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Case status updated',
            'case' => $case
        ]);
    }
}
