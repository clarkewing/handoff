<?php

use ClarkeWing\Handoff\Http\Controllers\HandoffController;
use Illuminate\Support\Facades\Route;

Route::prefix('/handoff')->group(function () {

    /** @phpstan-ignore method.nonObject */
    Route::get('/', HandoffController::class)
        ->middleware(['throttle:handoff'])
        ->name('handoff');
});
