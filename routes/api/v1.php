<?php

use App\Http\Controllers\v1\AuthController;
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
