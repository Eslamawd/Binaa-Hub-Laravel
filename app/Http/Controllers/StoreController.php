<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    //
    public function index () {
        $stores = Store::paginate(10);
        return response()->json($stores);
    }

  public function show($id)
{
    $store = Store::findOrFail($id);
    $products = $store->products()->paginate(6); // ✅ pagination شغال هنا

    return response()->json([
        'store' => $store,
        'products' => $products
    ]);
}

public function update(Request $request, $id)
{
    $store = Store::findOrFail($id);

    $validated = $request->validate([
        'name' => 'sometimes|string',
        'image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        'description' => 'nullable|string',
        'address'=> 'nullable|string',
        'phone' => 'nullable|string|max:20', // أو regex لو فيه فورمات معين
        'status' => 'nullable|in:active,inactive',
        
    ]);

    if (!$request->user()->hasRole('admin') && $request->user()->id !== $store->vendor_id) {
        return response()->json(['error' => 'You are not authorized to update this store.'], 403);
    }

    if ($request->hasFile('image')) {
        if ($store->image && \Storage::disk('public')->exists($store->image)) {
            \Storage::disk('public')->delete($store->image);
        }

        $image = $request->file('image')->store('stores', 'public');
        $validated['image'] = $image;
    }

    $store->update($validated);

    return response()->json([
        'message' => 'Store updated successfully',
        'store' => $store,
    ]);
}



    
}
