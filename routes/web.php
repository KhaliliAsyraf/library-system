<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowerController;
use App\Http\Middleware\CheckBearerToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'api', 'as' => 'api.', 'middleware' => [CheckBearerToken::class]], function () {
    Route::group(['prefix' => 'borrowers', 'as' => 'borrower.'], function () {
        Route::post('/', [BorrowerController::class, 'store'])->name('store');
    });
    Route::group(['prefix' => 'books', 'as' => 'book.'], function () {
        Route::post('/', [BookController::class, 'store'])->name('store');
        Route::get('/', [BookController::class, 'list'])->name('list');
        Route::post('/borrow', [BookController::class, 'createBorrow'])->name('borrow.create')->middleware('throttle:100,1'); // Limit to 100 requests per minute
        Route::put('/borrow/{id}/return', [BookController::class, 'returnBorrowed'])->name('borrow.return');
    });
});