<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\xthreepaper\PluginController;

Route::prefix('/servers/{serverUuid}')
    ->where(['serverUuid' => '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}'])
    ->middleware('throttle:30,1,x3paper-catalog')
    ->group(function () {
        Route::get('/status', [PluginController::class, 'status']);
        Route::get('/search', [PluginController::class, 'search']);
        Route::get('/projects/{projectId}/versions', [PluginController::class, 'versions'])
            ->where('projectId', '[a-zA-Z0-9]{1,32}');
        Route::post('/install', [PluginController::class, 'install'])->middleware('throttle:6,1,x3paper-install');
    });
