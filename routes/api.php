<?php

use App\Http\Controllers\AiStreamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Conversation;
use App\Services\PrismChatService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// AI Chat API Routes
Route::middleware([''])->group(function (): void {
    // Stream AI responses
    
});
