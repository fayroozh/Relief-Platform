<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;

class OrganizationRegistrationController extends Controller
{
    /**
     * Handle an incoming organization registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            // حقول المستخدم المسؤول عن المنظمة
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            // حقول المنظمة
            'organization_name' => ['required', 'string', 'max:255', 'unique:'.Organization::class.',name'],
            'organization_description' => ['nullable', 'string'],
            'organization_email' => ['nullable', 'string', 'email', 'max:255'],
            'organization_phone' => ['nullable', 'string', 'max:20'],
            'organization_website' => ['nullable', 'string', 'url'],
        ]);

        // 1. إنشاء حساب المستخدم
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'user_type' => 'organization', // تحديد نوع المستخدم كـ "منظمة"
            'status' => 'active',
        ]);

        // 2. إنشاء المنظمة وربطها بالمستخدم
        $organization = Organization::create([
            'user_id' => $user->id,
            'name' => $request->organization_name,
            'slug' => Str::slug($request->organization_name),
            'description' => $request->organization_description ?? '—',
            'email' => $request->organization_email ?? $request->email,
            'phone' => $request->organization_phone ?? ($request->phone ?: ''),
            'website' => $request->organization_website,
            'status' => 'pending', // الحالة "قيد المراجعة" حتى موافقة الأدمن
        ]);

        // 3. تسجيل دخول المستخدم تلقائيًا وإرجاع التوكن
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('organization') // إرجاع بيانات المستخدم مع المنظمة
        ], 201);
    }
}
