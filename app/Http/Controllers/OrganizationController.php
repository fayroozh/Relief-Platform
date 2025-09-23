<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::query();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($q = $request->query('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $perPage = (int) $request->query('per_page', 15);
        $result = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($result);
    }

    // POST /api/organizations  (submit new org) -> user can be logged-in or guest
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'email' => 'nullable|email|unique:organizations,email',
            'phone' => 'nullable|string|unique:organizations,phone',
            'website' => 'nullable|url',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120' // each file max 5MB
        ]);

        $user = $request->user(); // can be null if guest allowed

        // create unique slug
        $slugBase = Str::slug($request->name);
        $slug = $slugBase;
        $i = 1;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $i++;
        }

        $org = Organization::create([
            'user_id' => $user?->id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'email' => $request->email,
            'phone' => $request->phone,
            'website' => $request->website,
            'status' => 'pending',
        ]);

        // handle documents upload
        $uploaded = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store("organizations/{$org->id}", 'public');
                $uploaded[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
            $org->update(['documents' => $uploaded]);
        }

        return response()->json([
            'message' => 'Organization submitted (pending admin approval)',
            'organization' => $org,
        ], 201);
    }

    // GET /api/organizations/{id}
    public function show($id)
    {
        $org = Organization::findOrFail($id);
        return response()->json($org);
    }

    // PUT /api/organizations/{id}
    public function update(Request $request, $id)
    {
        $org = Organization::findOrFail($id);

        // only owner or admin can update
        if (! $request->user() || ($request->user()->id !== $org->user_id && $request->user()->user_type !== 'admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'email' => 'nullable|email|unique:organizations,email,'.$org->id,
            'phone' => 'nullable|string|unique:organizations,phone,'.$org->id,
            'website' => 'nullable|url',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        $org->fill($request->only(['name', 'description', 'email', 'phone', 'website']));

        // update slug if name changed
        if ($request->filled('name') && $org->isDirty('name')) {
            $slugBase = Str::slug($org->name);
            $slug = $slugBase;
            $i = 1;
            while (Organization::where('slug', $slug)->where('id', '!=', $org->id)->exists()) {
                $slug = $slugBase . '-' . $i++;
            }
            $org->slug = $slug;
        }

        $org->save();

        // append documents if any
        $uploaded = $org->documents ?? [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store("organizations/{$org->id}", 'public');
                $uploaded[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now()->toDateTimeString(),
                ];
            }
            $org->update(['documents' => $uploaded]);
        }

        return response()->json(['message' => 'Organization updated', 'organization' => $org]);
    }

    // POST /api/organizations/{id}/approve  (admin)
    // NOTE: لا ننشئ حساب للمؤسسة هنا ولا نولد كلمة مرور — المؤسسة سجلت بياناتها وهي التي ستستعمل بياناتها عند التسجيل لاحقاً
    public function approve(Request $request, $id)
    {
        $org = Organization::findOrFail($id);

        // only admin
        if (! $request->user() || $request->user()->user_type !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $org->update([
            'status' => 'approved',
            'approved_at' => now(),
            'admin_notes' => $request->input('admin_notes') ?? null,
        ]);

        // إرسال إشعار إيميل خفيف للجهة المعلنة (إذا لديها إيميل)
        if ($org->email) {
            $body = "تمت الموافقة على حساب جمعيتكم: {$org->name}\n\n";
            $body .= "يمكنكم الآن تسجيل الدخول عبر صفحة تسجيل الجمعيات باستخدام البريد وكلمة المرور التي أدخلتموها عند التسجيل (أو التي قُمتُم بتعيينها لاحقاً).\n\n";
            if ($org->admin_notes) {
                $body .= "ملاحظات الإدارة: {$org->admin_notes}\n\n";
            }
            $body .= "مع تحيات فريق المنصة.";

            Mail::raw($body, function ($msg) use ($org) {
                $msg->to($org->email)->subject('تمت الموافقة على حساب جمعيتكم');
            });
        }

        return response()->json(['message' => 'Organization approved', 'organization' => $org]);
    }

    // POST /api/organizations/{id}/reject  (admin)
    public function reject(Request $request, $id)
    {
        $org = Organization::findOrFail($id);

        if (! $request->user() || $request->user()->user_type !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate(['reason' => 'nullable|string|max:2000']);

        $org->update([
            'status' => 'rejected',
            'admin_notes' => $request->input('reason') ?? null,
        ]);

        if ($org->email) {
            $body = "نأسف لإبلاغكم أن طلب الجمعية \"{$org->name}\" قد رُفض.\n\n";
            if ($org->admin_notes) {
                $body .= "سبب الرفض: {$org->admin_notes}\n\n";
            }
            $body .= "يمكنكم تعديل الطلب وإعادة الإرسال عبر لوحة حسابكم أو التواصل مع الدعم.";
            Mail::raw($body, function ($msg) use ($org) {
                $msg->to($org->email)->subject('تم رفض طلب تسجيل الجمعية');
            });
        }

        return response()->json(['message' => 'Organization rejected', 'organization' => $org]);
    }

    // optional: delete organization (owner or admin)
    public function destroy(Request $request, $id)
    {
        $org = Organization::findOrFail($id);
        if (! $request->user() || ($request->user()->id !== $org->user_id && $request->user()->user_type !== 'admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // delete stored files
        if ($org->documents && is_array($org->documents)) {
            foreach ($org->documents as $doc) {
                if (!empty($doc['path'])) {
                    Storage::disk('public')->delete($doc['path']);
                }
            }
        }

        $org->delete();

        return response()->json(['message' => 'Organization deleted']);
    }
}
