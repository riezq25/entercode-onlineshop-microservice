<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    function index(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);
            $search = $request->input('search');
            $sortBy = $request->input('sort_by', 'created_at');
            $orderBy = $request->input('order_by', 'desc');

            $carts = Cart::where('user_id', $request->user()->id);
            $carts = $carts->when($search, function ($query, $search) {
                $query->whereHas('product', function ($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%");
                });
            });
            $carts = $carts->when($sortBy, function ($query, $sortBy) use ($orderBy) {
                $query->orderBy($sortBy, $orderBy);
            });
            $carts = $carts->with('product');
            $carts = $carts->paginate($limit, ['*'], 'page', $page);

            foreach ($carts as $cart) {
                $cart->product->foto = $cart->product->getAssetFoto();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'success retrieve carts',
                'data' => $carts,
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
                'product_id' => 'required|exists:products,id',
                'jumlah' => 'required|integer|min:1',
            ]);

            $product = Product::find($request->product_id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'product not found',
                ], 404);
            }

            if ($product->user_id == $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'you cannot add your own product to cart',
                ], 400);
            }

            if ($product->stok < $request->jumlah) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'product out of stock',
                ], 400);
            }

            $cart = $request->user()->carts()
                ->where('product_id', $request->product_id)
                ->first();

            if ($cart) {
                if ($cart->jumlah + $request->jumlah > $product->stok) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'product already in cart and out of stock',
                    ], 400);
                }

                $cart->jumlah += $request->jumlah;
                $cart->save();

                return response()->json([
                    'status' => 'success',
                    'message' => 'success add product to cart',
                    'data' => $cart,
                ]);
            } else {
                $cart = $request->user()->carts()->create([
                    'product_id' => $request->product_id,
                    'jumlah' => $request->jumlah,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'success add product to cart',
                'data' => $cart,
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
            $this->validate($request, [
                'jumlah' => 'required|integer|min:1',
            ]);

            $cart = $request->user()->carts()->where('id', $id)->first();

            if (!$cart) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'cart not found',
                ], 404);
            }

            $product = $cart->product;

            if ($product->stok < $request->jumlah) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'product out of stock',
                ], 400);
            }

            $cart->jumlah = $request->jumlah;
            $cart->save();

            return response()->json([
                'status' => 'success',
                'message' => 'success update cart',
                'data' => $cart,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    function destroy(Request $request, $id)
    {
        try {
            $cart = $request->user()->carts()->where('id', $id)->first();

            if (!$cart) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'cart not found',
                ], 404);
            }

            $cart->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'success delete cart',
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
