<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Project;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    // عرض كل التبرعات (للمسؤولين فقط غالباً)
    public function index()
    {
        return response()->json(Donation::with(['user', 'project'])->get());
    }

    // التبرع لمشروع
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $donation = Donation::create([
            'user_id' => $request->user()->id,
            'project_id' => $request->project_id,
            'amount' => $request->amount,
            'status' => 'confirmed', // مؤقتاً نخليها مباشرة
        ]);

        // تحديث المبلغ المجموع في المشروع
        $project = Project::find($request->project_id);
        $project->raised_amount += $request->amount;
        $project->save();

        return response()->json([
            'message' => 'Donation successful',
            'donation' => $donation,
        ], 201);
    }

    // عرض تبرعات مستخدم معين
    public function myDonations(Request $request)
    {
        return response()->json(
            Donation::with('project')->where('user_id', $request->user()->id)->get()
        );
    }
}
