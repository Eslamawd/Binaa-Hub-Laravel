<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
       public function index()
    {
          $categories = Category::parentsOnly()->paginate(8);

         return response()->json(['categories' => $categories]);
    }

    
       public function getByAdmin()
    {
          $categories = Category::with('children')->paginate(10);


    return response()->json(['categories' => $categories]);
    }


       public function getAll()
    {
         $categories = Category::whereNull('parent_id')
        ->with('children') // جلب الأبناء مباشرة
        ->get();

    return response()->json(['categories' => $categories]);
    }

    public function getAllCat()
{
    $categories = Category::whereNull('parent_id')
        ->with('childrenRecursive')
        ->get();

    return response()->json(['categories' => $categories]);
}

public function store(Request $request)
{
    $request->validate([
        'name'     => 'required|string',
        'parent_id'   => 'nullable|exists:categories,id',
    ]);

    // رفع الصورة لو موجودة
    

    $category = Category::create([
        'name'     => $request->name,
        'parent_id'   => $request->parent_id,
    ]);

    return response()->json(['category' => $category], 201);
}


public function show(Request $request,$id)
{
    $perPage = 12; // عدد الفئات الفرعية في كل صفحة

    $category = Category::findOrFail($id);

    // الأطفال paginated
    $childrenQuery = Category::where('parent_id', $category->id);
    $children = $childrenQuery->paginate($perPage);

    // المنتجات برضو paginated
    $products = $category->products()->visible()->paginate($perPage);


    
    return response()->json([
        'category' => $category,
        'children' => $children,
        'products' => $products,
    ]);
}


 public function update(Request $request, $id)
{
    $category = Category::findOrFail($id);

    $request->validate([
        'name' => 'sometimes|string|max:255',
        'parent_id' => 'sometimes|nullable|exists:categories,id',
    ]);

    $category->update($request->only(['name', 'parent_id']));

    return response()->json([
        'message' => 'Category updated successfully',
        'category' => $category
    ], 200);
}


    public function destroy($category)
    {
        $category = Category::findOrFail($category);
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }

}
