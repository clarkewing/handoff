<?php

use ClarkeWing\Handoff\Http\Controllers\HandoffController;
use Illuminate\Support\Facades\Route;

Route::prefix('/handoff')->middleware(['web'])->group(function () {

    Route::get('/', HandoffController::class)
        ->middleware(['throttle:handoff'])
        ->name('handoff');
});
