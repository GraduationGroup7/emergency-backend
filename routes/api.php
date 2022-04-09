<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * Authentication Routes
 */
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);
    Route::post('register', [\App\Http\Controllers\AuthController::class, 'register']);
});


/**
 * Protected Routes
 */
Route::middleware('auth:sanctum')->group(function() {
   Route::get('/user', function (Request $request) {
       return $request->user();
   });

    Route::get('users', [\App\Http\Controllers\UserController::class, 'getUsers']);

    Route::group(['prefix' => 'emergency_types'], function () {
        Route::get('/', [\App\Http\Controllers\EmergencyTypeController::class, 'getEmergencyTypes']);
        Route::post('/', [\App\Http\Controllers\EmergencyTypeController::class, 'createEmergencyType']);
        Route::get('/{id}', [\App\Http\Controllers\EmergencyTypeController::class, 'getEmergencyType']);
        Route::put('/{id}', [\App\Http\Controllers\EmergencyTypeController::class, 'updateEmergencyType']);
        Route::delete('/{id}', [\App\Http\Controllers\EmergencyTypeController::class, 'deleteEmergencyType']);
    });

    Route::group(['prefix' => 'authorities'], function () {
        Route::get('/', [\App\Http\Controllers\AuthorityController::class, 'getAuthorities']);
        Route::get('/{id}', [\App\Http\Controllers\AuthorityController::class, 'getAuthority']);
        Route::put('/{id}', [\App\Http\Controllers\AuthorityController::class, 'updateAuthority']);
        Route::delete('/{id}', [\App\Http\Controllers\AuthorityController::class, 'deleteAuthority']);
    });

    Route::group(['prefix' => 'agents'], function () {
        Route::get('/', [\App\Http\Controllers\AgentController::class, 'getAgents']);
        Route::get('/{id}', [\App\Http\Controllers\AgentController::class, 'getAgent']);
        Route::post('/', [\App\Http\Controllers\AgentController::class, 'createAgent']);
        Route::put('/{id}', [\App\Http\Controllers\AgentController::class, 'updateAgent']);
        Route::delete('/{id}', [\App\Http\Controllers\AgentController::class, 'deleteAgent']);
    });
});

