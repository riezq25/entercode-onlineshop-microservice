<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    function index(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);
            $search = $request->input('search');
            $sortBy = $request->input('sort_by', 'created_at');
            $orderBy = $request->input('order_by', 'desc');

            $products = Product::orderBy($sortBy, $orderBy);
            $products->when($search, function ($query) use ($search) {
                return $query->where('nama', 'like', "%$search%");
            });
            $products = $products->where('stok', '>', 0);
            $products = $products
                ->paginate($limit, ['*'], 'page', $page);

            foreach ($products as $product) {
                $product->foto = $product->getAssetFoto();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'success retrieve products',
                'data' => $products,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    function store(Request $request)
    {
        try {
            $this->validate($request, [
                'foto' => 'required|file|image|mimes:jpeg,png,jpg|max:2048',
                'nama' => 'required|string|min:3|max:255',
                'harga' => 'required|integer|min:0',
                'stok' => 'required|integer|min:0',
            ]);

            $filename = null;
            if ($request->hasFile('foto')) {
                $file = $request->file('foto');

                $filename = $file->store('products', 'public');
            }

            $validated = $request->except('foto');
            $validated['foto'] = $filename;
            $user = auth()->user();
            $product = $user->products()->create($validated);

            $product->foto = $product->getAssetFoto();

            return response()->json([
                'status' => 'success',
                'message' => 'success create product',
                'data' => $product,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    function show($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->foto = $product->getAssetFoto();

            return response()->json([
                'status' => 'success',
                'message' => 'success retrieve product detail',
                'data' => $product,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                ], 404);
            }

            if ($product->user_id !== auth()->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to update this product',
                ], 403);
            }

            $this->validate($request, [
                'foto' => 'required|file|image|mimes:jpeg,png,jpg|max:2048',
                'nama' => 'required|string|min:3|max:255',
                'harga' => 'required|integer|min:0',
                'stok' => 'required|integer|min:0',
            ]);

            $filename = null;
            if ($request->hasFile('foto')) {
                $file = $request->file('foto');

                $filename = $file->store('products', 'public');

                if ($product->foto && file_exists(storage_path('app/public/' . $product->foto))) {
                    Storage::delete('public/' . $product->foto);
                }
            }

            $validated = $request->except('foto');
            $validated['foto'] = $filename;

            $product->fill($validated);
            $product->save();

            $product->foto = $product->getAssetFoto();

            return response()->json([
                'status' => 'success',
                'message' => 'success update product',
                'data' => $product,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found',
                ], 404);
            }

            if ($product->user_id !== auth()->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to delete this product',
                ], 403);
            }

            $foto = $product->foto;
            $product->delete();

            if ($foto && file_exists(storage_path('app/public/' . $foto))) {
                Storage::delete('public/' . $foto);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'success delete product',
                'message' => 'Product deleted',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
