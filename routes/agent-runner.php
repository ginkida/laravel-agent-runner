<?php

use Ginkida\AgentRunner\Http\Controllers\StatusCallbackController;
use Ginkida\AgentRunner\Http\Controllers\ToolCallbackController;
use Ginkida\AgentRunner\Http\Middleware\VerifyHmacSignature;
use Illuminate\Support\Facades\Route;

Route::middleware([VerifyHmacSignature::class])->group(function () {
    Route::post('/tools/{toolName}', ToolCallbackController::class)
        ->where('toolName', '[a-zA-Z][a-zA-Z0-9_]*');

    Route::post('/sessions/{sessionId}/status', StatusCallbackController::class)
        ->where('sessionId', '[a-zA-Z0-9_\-]+');
});
