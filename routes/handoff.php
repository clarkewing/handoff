<?php

use ClarkeWing\Handoff\Http\Controllers\HandoffController;
use Illuminate\Support\Facades\Route;

$registrar = Route::prefix('/handoff')->middleware(['web']);

/** @var string|null $domain */
$domain = config('handoff.domain');

if (is_string($domain) && $domain !== '') {
    $registrar->domain($domain);
}

$registrar->group(function () {

    Route::get('/', HandoffController::class)
        ->middleware(['throttle:handoff'])
        ->name('handoff');
});
