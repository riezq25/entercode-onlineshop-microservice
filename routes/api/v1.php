<?php

use App\Http\Controllers\v1\AuthController;
use App\Http\Controllers\v1\ProductController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'prefix' => 'auth',
        'as' => 'auth.',
    ],
    function () {
        Route::post('login', [AuthController::class, 'login'])
            ->name('login');
        Route::post('register', [AuthController::class, 'register'])
            ->name('register');
        Route::group(
            [
                'middleware' => 'auth:sanctum',
            ],
            function () {
                Route::post('logout', [AuthController::class, 'logout'])
                    ->name('logout');
                Route::get('me', [AuthController::class, 'me'])
                    ->name('me');
                Route::get('revoke', [AuthController::class, 'revoke'])
                    ->name('revoke');
            }
        );
    }
);

Route::group(
    [
        'middleware' => 'auth:sanctum',
    ],
    function () {
        Route::group(
            [
                'prefix' => 'products',
                'as' => 'products.',
            ],
            function () {
                Route::get('/', [ProductController::class, 'index'])
                    ->name('index');
                Route::get('/{id}', [ProductController::class, 'show'])
                    ->name('show');
                Route::group(
                    [
                        'middleware' => 'is_seller',
                    ],
                    function () {
                        Route::post('/', [ProductController::class, 'store'])
                            ->name('store');
                        Route::put('/{id}', [ProductController::class, 'update'])
                            ->name('update');
                        Route::delete('/{id}', [ProductController::class, 'destroy'])
                            ->name('destroy');
                        Route::post('/{id}/to-cart', [ProductController::class, 'addToCart'])
                            ->name('to-cart');
                    }
                );
            }
        );
    }
);
