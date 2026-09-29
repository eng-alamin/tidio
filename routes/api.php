<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MacroController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

// All workspace-scoped resources sit under this prefix and share the
// 'auth:sanctum' + 'workspace.member' middleware pair, so every route below
// is automatically tenant-isolated at the HTTP layer (see
// EnsureBelongsToWorkspace) as well as by workspace_id at the DB layer.
Route::middleware(['auth:sanctum', 'workspace.member'])
    ->prefix('workspaces/{workspace}')
    ->group(function () {
        Route::apiResource('conversations', ConversationController::class);
        Route::apiResource('conversations.messages', MessageController::class)->only(['index', 'store']);
        Route::apiResource('contacts', ContactController::class);
        Route::apiResource('macros', MacroController::class)->except(['show']);
    });

Route::middleware('auth:sanctum')
    ->get('invoices/{invoice}/download', [InvoiceController::class, 'download'])
    ->name('invoices.download');
