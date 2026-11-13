<?php
use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\xthreegames\GameController;

Route::prefix('/servers/{uuid}')
    ->where(['uuid' => '[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}'])
    ->middleware('throttle:60,1,xthreegames')
    ->group(function () {
        Route::get('/', [GameController::class, 'access']);
        Route::get('/runtime', [GameController::class, 'runtime']);
        Route::get('/settings', [GameController::class, 'settings']);
        Route::post('/settings', [GameController::class, 'save'])->middleware('throttle:12,1,xthreegames-save');
        Route::get('/players', [GameController::class, 'players']);
        Route::post('/players', [GameController::class, 'moderate'])->middleware('throttle:12,1,xthreegames-moderate');
        Route::get('/moderators', [GameController::class, 'moderators']);
        Route::put('/moderators/{subuser}', [GameController::class, 'grant'])->whereNumber('subuser');
    });
