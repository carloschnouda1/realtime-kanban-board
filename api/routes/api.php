<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ColumnController;
use Illuminate\Support\Facades\Route;

Route::post('/boards', [BoardController::class, 'store']);
Route::get('/boards/{board}', [BoardController::class, 'show']);

Route::post('/boards/{board}/columns', [ColumnController::class, 'store']);

Route::post('/columns/{column}/cards', [CardController::class, 'store']);
Route::patch('/cards/{card}', [CardController::class, 'update']);
Route::patch('/cards/{card}/move', [CardController::class, 'move']);
Route::delete('/cards/{card}', [CardController::class, 'destroy']);
