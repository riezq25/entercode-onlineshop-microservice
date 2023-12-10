<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Mail\newOrderBuyerMail;
use App\Mail\paidOrderMail;
use App\Mail\processedOrderMail;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TransactionController extends Controller
{
    function index(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);
            $search = $request->input('search');
            $status = $request->input('status');
            $sortBy = $request->input('sort_by', 'created_at');
            $orderBy = $request->input('order_by', 'desc');

            $transactions = Transaction::where('user_id', $request->user()->id);
            $transactions = $transactions->when($search, function ($query, $search) {
                $query->whereHas('product', function ($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%");
                });
            });
            $transactions = $transactions->when($status, function ($query, $status) {
                $query->where('status', $status);
            });
            $transactions = $transactions->when($sortBy, function ($query, $sortBy) use ($orderBy) {
                $query->orderBy($sortBy, $orderBy);
            });
            $transactions = $transactions->with('product');
            $transactions = $transactions->paginate($limit, ['*'], 'page', $page);

            foreach ($transactions as $transaction) {
                $transaction->product->foto = $transaction->product->getAssetFoto();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'success retrieve transactions',
                'data' => $transactions,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    function show(Request $request, $id)
    {
        try {
            $transaction = Transaction::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail();

            $transaction->product->foto = $transaction->product->getAssetFoto();

            return response()->json([
                'status' => 'success',
                'message' => 'success retrieve transaction',
                'data' => $transaction,
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
                'cart_id' => 'required|exists:carts,id',
            ]);

            $cart = $request->user()->carts()
                ->where('id', $request->cart_id)
                ->firstOrFail();

            $product = Product::find($cart->product_id);

            if ($product->stok < $cart->jumlah) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'product out of stock',
                ], 400);
            }

            $transaction = Transaction::create([
                'order_id' => 'TRX-' . time(),
                'user_id' => $request->user()->id,
                'product_id' => $cart->product_id,
                'harga' => $cart->product->harga,
                'jumlah' => $cart->jumlah,
                'total_harga' => $cart->jumlah * $cart->product->harga,
            ]);

            $product->stok -= $cart->jumlah;
            $product->save();

            $cart->delete();

            $transaction->product->foto = $transaction->product->getAssetFoto();
            return response()->json([
                'status' => 'success',
                'message' => 'success create transaction',
                'data' => $transaction,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    function payTransaction(Request $request, $id)
    {
        try {
            $transaction = Transaction::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($transaction->status != 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'transaction status is not pending',
                ], 400);
            }

            $transaction->status = 'dibayar';
            $transaction->save();

            $transaction->product->foto = $transaction->product->getAssetFoto();
            $seller = $transaction->product->user;
            Mail::to($request->user()->email)->send(new paidOrderMail($transaction,  $seller));

            return response()->json([
                'status' => 'success',
                'message' => 'success pay transaction',
                'data' => $transaction,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    function processTransaction(Request $request, $id)
    {
        try {
            $user = $request->user();
            $transaction = Transaction::where('id', $id)
                ->firstOrFail();

            if ($user->id != $transaction->product->user_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'you are not the seller',
                ], 400);
            }

            if ($transaction->status == 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'transaction is not paid',
                ], 400);
            }

            if ($transaction->status == 'diproses') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'transaction is already processed',
                ], 400);
            }

            $transaction->status = 'diproses';
            $transaction->save();

            $transaction->product->foto = $transaction->product->getAssetFoto();
            $buyer = $transaction->user;
            Mail::to($request->user()->email)->send(new processedOrderMail($transaction,  $buyer));

            return response()->json([
                'status' => 'success',
                'message' => 'success process transaction',
                'data' => $transaction,
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
            $transaction = Transaction::where('user_id', $request->user()->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($transaction->status != 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'transaction status is not pending',
                ], 400);
            }

            $product = Product::find($transaction->product_id);
            $product->stok += $transaction->jumlah;
            $product->save();

            $transaction->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'success delete transaction',
                'data' => $transaction,
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
