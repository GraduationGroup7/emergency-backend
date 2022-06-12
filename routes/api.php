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
    // For Administrative Users
    Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);
    Route::post('register', [\App\Http\Controllers\AuthController::class, 'register']);

    // For Regular Users
    Route::group(['prefix' => 'customer'], function () {
        Route::post('register', [\App\Http\Controllers\AuthController::class, 'customer_register']);
    });
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
        Route::get('available', [\App\Http\Controllers\AgentController::class, 'getAvailableAgents']);
        Route::get('/', [\App\Http\Controllers\AgentController::class, 'getAgents']);
        Route::get('/{id}', [\App\Http\Controllers\AgentController::class, 'getAgent']);
        Route::post('/', [\App\Http\Controllers\AgentController::class, 'createAgent']);
        Route::put('/{id}', [\App\Http\Controllers\AgentController::class, 'updateAgent']);
        Route::delete('/{id}', [\App\Http\Controllers\AgentController::class, 'deleteAgent']);
    });

    Route::group(['prefix' => 'customers'], function () {
        Route::get('/', [\App\Http\Controllers\CustomerController::class, 'getCustomers']);
    });

    Route::group(['prefix' => 'emergencies'], function () {
        Route::get('archival', [\App\Http\Controllers\EmergencyController::class, 'getArchivalEmergencies']);
        Route::post('merge', [\App\Http\Controllers\EmergencyController::class, 'mergeEmergencies']);
        Route::get('/', [\App\Http\Controllers\EmergencyController::class, 'getEmergencies']);
        Route::get('/{id}', [\App\Http\Controllers\EmergencyController::class, 'getEmergency']);
        Route::post('/', [\App\Http\Controllers\EmergencyController::class, 'createEmergency']);

        Route::group(['prefix' => '{id}'], function () {
            Route::post('assign_agents', [\App\Http\Controllers\EmergencyController::class, 'assignAgentsToEmergency']);
            Route::post('remove_agents', [\App\Http\Controllers\EmergencyController::class, 'removeAgentsFromEmergency']);
            Route::get('chat_room', [\App\Http\Controllers\EmergencyController::class, 'getChatRoom']);
        });
    });

    Route::group(['prefix' => 'chat_rooms'], function () {
        Route::get('/', [\App\Http\Controllers\ChatRoomController::class, 'getAllChatRooms']);
        Route::group(['prefix' => '{id}'], function () {
            Route::get('/', [\App\Http\Controllers\ChatRoomController::class, 'getChatRoom']);
            Route::post('/', [\App\Http\Controllers\ChatRoomController::class, 'postMessageToChatRoom']);
            Route::get('/', [\App\Http\Controllers\ChatRoomController::class, 'getChatRoom']);
            Route::get('messages', [\App\Http\Controllers\ChatRoomController::class, 'getChatRoomMessages']);
        });
    });

    // route group for pusher
    Route::group(['prefix' => 'pusher'], function () {
        Route::post('auth', [\App\Http\Controllers\PusherController::class, 'auth']);
    });
});

// Serving files without authentication (for now)
Route::group(['prefix' => 'emergencies'], function () {
    Route::group(['prefix' => '{id}'], function () {
        Route::get('get_file/{file_name}', [\App\Http\Controllers\EmergencyController::class, 'getEmergencyFile']);
    });
});

