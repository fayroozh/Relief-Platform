<?php
namespace App\Http\Controllers;

use App\Models\CaseModel;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    // GET /api/cases?status=approved&q=keyword
    public function index(Request $request)
    {
        $query = CaseModel::with(['organization', 'category']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($q = $request->query('q')) {
            $query->where('title', 'like', "%$q%")
                  ->orWhere('description', 'like', "%$q%");
        }

        $perPage = (int) $request->query('per_page', 10);
        return response()->json($query->paginate($perPage));
    }

    // POST /api/cases
    public function store(Request $request)
    {
        $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'goal_amount' => 'required|numeric|min:1',
        ]);

        $case = CaseModel::create([
            'organization_id' => $request->organization_id,
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'goal_amount' => $request->goal_amount,
            'collected_amount' => 0,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Case created successfully, pending approval',
            'case' => $case
        ], 201);
    }

    // PUT /api/cases/{id}/status
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

    // ✅ دالة لتحديث المبلغ بعد التبرع (تناديها DonationController)
    public function addDonation($caseId, $amount)
    {
        $case = CaseModel::findOrFail($caseId);

        $case->collected_amount += $amount;

        if ($case->collected_amount >= $case->goal_amount) {
            $case->status = 'completed';
        }

        $case->save();
        return $case;
    }
}
