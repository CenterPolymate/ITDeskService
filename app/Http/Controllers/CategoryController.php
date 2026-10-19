<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    private function authorizeAdminOrManager(): void
    {
        $user = request()->user();
        if (! $user || ! in_array($user->role, ['administrator', 'manager'], true)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(): Response
    {
        $this->authorizeAdminOrManager();
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Categories/Index', compact('categories'));
    }

    public function create(): Response
    {
        $this->authorizeAdminOrManager();

        return Inertia::render('Categories/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdminOrManager();
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('categories.index')->with('success', 'เพิ่มหมวดหมู่สำเร็จ');
    }

    public function edit(Category $category): Response
    {
        $this->authorizeAdminOrManager();

        return Inertia::render('Categories/Edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorizeAdminOrManager();
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('categories.index')->with('success', 'อัปเดตหมวดหมู่สำเร็จ');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorizeAdminOrManager();
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'ลบหมวดหมู่สำเร็จ');
    }
}
