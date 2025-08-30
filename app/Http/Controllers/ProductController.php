<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
public function index(Request $request)
{
    $query = Product::query();

    // Category filter
    if ($request->filled('category') && $request->category !== 'all') {
        $query->where('category_id', $request->category);
    }

    // Search
    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('description', 'like', '%' . $request->search . '%')
              ->orWhere('city', 'like', '%' . $request->search . '%');
        });
    }

    // Boolean filters
    if ($request->boolean('inStock')) {
        $query->where('stock', '>', 0);
    }

    if ($request->boolean('warrantyOnly')) {
        $query->where('warrantyOnly', true);
    }

    if ($request->boolean('freeShipping')) {
        $query->where('freeShipping', true);
    }

    // City filter
    if ($request->filled('city')) {
        $query->where('city', 'like', '%' . $request->city . '%');
    }

    // Store filter
    if ($request->filled('storeId')) {
        $query->where('storeId', $request->storeId);
    }

    // Sorting (whitelist)
    $allowedSorts = ['name', 'price', 'updated_at', 'created_at'];
    $sortBy = in_array($request->sortBy, $allowedSorts) ? $request->sortBy : 'updated_at';
    $order = $request->order === 'asc' ? 'asc' : 'desc';

    // Pagination
    $products = $query->orderBy($sortBy, $order)->with('category')->paginate(12);

    return response()->json([ 'products' => $products]);
}
   




        public function show($id)
        {
            $product = Product::with('category')->findOrFail($id);
            return response()->json(['product' => $product]);
        }

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string',
        'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'price' => 'required|numeric',
        'stock' => 'required|integer|min:0',
        'old_price' => 'nullable|numeric',
        'rating' => 'nullable|numeric|min:1|max:5',
        'reviews' => 'nullable|integer|min:0',
        'description' => 'nullable|string',
        'category_id' => 'required|exists:categories,id',
    ]);

    $images = [];

    if ($request->hasFile('images')) {
         foreach ($request->file('images') as $image) {
        $path = $image->store('products', 'public'); // ✅ هنا disk اسمه "public"
        $images[] = $path;
    }
    }

    if (!empty($images)) {
        $validated['images'] = $images;
    }

    $user = auth()->user();

     if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user->stripe_account_id ) {
            return response()->json(['error' => 'Your Stripe account is not set up or enabled for charges.'], 403);
        }

    $validated['vendor_id'] = $user->id;

    $product = Product::create($validated);

    return response()->json([
        'message' => 'Product created successfully',
        'product' => $product,
    ], 201);
}

public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'name' => 'sometimes|string',
        'images.*' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        'price' => 'sometimes|numeric',
        'old_price' => 'nullable|numeric',
        'stock' => 'sometimes|integer|min:0',
        'rating' => 'nullable|numeric|min:1|max:5',
        'reviews' => 'nullable|integer|min:0',
        'description' => 'nullable|string',
        'category_id' => 'sometimes|exists:categories,id',
    ]);

       if ( !$request->user()->hasRole('admin') && $request->user()->id !== $product->vendor_id) {
            return response()->json(['error' => 'You are not authorized to update this product.'], 403);
        }

  

    // تحديث الصور المتعددة (اختياري حسب مشروعك)
    if ($request->hasFile('images')) {

         if ($product->images) {
        foreach ($product->images as $oldImage) {
            \Storage::disk('public')->delete('products/' . $oldImage);
        }
        }
        $images = [];
       foreach ($request->file('images') as $image) {
        $path = $image->store('products', 'public'); // ✅ هنا disk اسمه "public"
        $images[] = $path;
       }

        $validated['images'] = $images; // لو عندك عمود images في الجدول
    }


    $product->update($validated);

    return response()->json([
        'message' => 'Product updated successfully',
        'product' => $product,
    ]);
}




        public function destroy($id)
        {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json(['message' => 'Product deleted']);
        }
}
