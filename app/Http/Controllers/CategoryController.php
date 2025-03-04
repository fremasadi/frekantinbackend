<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    private function getImageUrl($imagePath)
    {
        return $imagePath ? url('storage/' . $imagePath) : null;
    }

    // Menampilkan semua kategori
    public function index()
    {
        $categories = Category::all()->map(function ($category) {
            $category->image = $this->getImageUrl($category->image);
            return $category;
        });

        return response()->json([
            'status' => true,
            'data' => $categories
        ]);
    }

    // Menambahkan kategori baru
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:categories,name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle image upload
        $imagePath = $request->hasFile('image') ? 
                     $request->file('image')->store('public/category-images') : 
                     null;

        if ($imagePath) {
            $imagePath = str_replace('public/', '', $imagePath);
        }

        $category = Category::create([
            'name' => $request->name,
            'image' => $imagePath
        ]);

        $category->image = $this->getImageUrl($category->image);

        return response()->json([
            'status' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    // Mengupdate kategori
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:categories,name,' . $category->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $imagePath = $request->file('image')->store('public/category-images');
            $imagePath = str_replace('public/', '', $imagePath);
        }

        $category->update([
            'name' => $request->name,
            'image' => $imagePath ? $imagePath : $category->image
        ]);

        $category->image = $this->getImageUrl($category->image);

        return response()->json([
            'status' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ], 200);
    }

    // Menghapus kategori
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        
        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}