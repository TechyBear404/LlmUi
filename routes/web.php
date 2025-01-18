<?php

use App\Http\Controllers\AskController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CustomInstructionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SettingsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/ask', [AskController::class, 'ask'])->name('ask.post');

    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
    Route::put('/conversations/{conversation}/model', [ConversationController::class, 'updateModel'])->name('conversations.model.update');
    Route::put('/conversations/{conversation}/custom-instruction', [ConversationController::class, 'updateCustomInstruction'])->name('conversations.custom-instruction.update');

    // Custom Instructions routes
    Route::get('/custom-instructions', [CustomInstructionController::class, 'index'])->name('custom-instructions.index');
    Route::prefix('custom-instructions')->name('custom-instructions.')->group(function () {
        Route::post('/', [CustomInstructionController::class, 'store'])->name('store');
        Route::put('/{instruction}', [CustomInstructionController::class, 'update'])->name('update');
        Route::delete('/{instruction}', [CustomInstructionController::class, 'destroy'])->name('destroy');
        Route::post('/{instruction}/set-active', [CustomInstructionController::class, 'setActive'])->name('set-active');
    });
    Route::get('/settings', [SettingsController::class, 'index'])
        ->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])
        ->name('settings.update');
});
