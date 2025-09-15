<?php
namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // عرض جميع التصنيفات
    public function index()
    {
        return response()->json(Category::all());
    }

    // إنشاء تصنيف جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive'
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    // عرض تصنيف واحد
    public function show(Category $category)
    {
        return response()->json($category);
    }

    // تعديل تصنيف
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive'
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    // حذف تصنيف
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
