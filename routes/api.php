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
        Route::post('resend_code', [\App\Http\Controllers\AuthController::class, 'customer_ask_verification']);
        Route::post('verify', [\App\Http\Controllers\AuthController::class, 'verifyPhone']);
    });
});


/**
 * Protected Routes
 */
Route::middleware('auth:sanctum')->group(function() {
    Route::get('users', [\App\Http\Controllers\UserController::class, 'getUsers']);

    Route::group(['prefix' => 'user'], function () {
        Route::get('/', [\App\Http\Controllers\AuthController::class, 'getUser']);
        Route::get('emergencies', [\App\Http\Controllers\EmergencyController::class, 'getEmergenciesForUser']);
    });

    Route::group(['prefix' => 'chat_rooms'], function () {
        Route::get('/', [\App\Http\Controllers\ChatRoomController::class, 'getAllChatRooms']);
        Route::group(['prefix' => '{id}'], function () {
            Route::get('/', [\App\Http\Controllers\ChatRoomController::class, 'getChatRoom']);
            Route::post('/', [\App\Http\Controllers\ChatRoomController::class, 'postMessageToChatRoom']);
            Route::get('messages', [\App\Http\Controllers\ChatRoomController::class, 'getChatRoomMessages']);
        });
    });

    Route::group(['prefix' => 'pusher'], function () {
        Route::post('auth', [\App\Http\Controllers\PusherController::class, 'auth']);
        Route::post('notifications/auth', [\App\Http\Controllers\PusherController::class, 'notificationsAuth']);
    });

    Route::get('agent_emergency', [\App\Http\Controllers\EmergencyController::class, 'getAgentEmergency']);

    Route::group(['prefix' => 'emergencies'], function () {
        Route::post('/', [\App\Http\Controllers\EmergencyController::class, 'createEmergency']);
    });

    Route::group(['prefix' => 'emergency_types'], function () {
        Route::get('/', [\App\Http\Controllers\EmergencyController::class, 'getEmergencyTypes']);
    });

    Route::group(['prefix' => 'agents'], function () {
        Route::group(['prefix' => 'chat_rooms'], function () {
            Route::get('/', [\App\Http\Controllers\AgentController::class, 'getAgentChatRooms']);
            Route::group(['prefix' => 'authority'], function () {
                Route::post('{id}', [\App\Http\Controllers\AgentController::class, 'sendMessageToAuthority']);
            });
        });
    });

    Route::group(['prefix' => 'authorities'], function () {
        Route::group(['prefix' => 'chat_rooms'], function () {
            Route::get('/', [\App\Http\Controllers\AuthorityController::class, 'getAuthorityChatRooms']);
            Route::group(['prefix' => '{id}'], function () {
                Route::get('/', [\App\Http\Controllers\AuthorityController::class, 'getChatMessages']);
            });
            Route::post('/', [\App\Http\Controllers\AuthorityController::class, 'openChatRoom']);
            Route::post('message', [\App\Http\Controllers\AuthorityController::class, 'sendMessage']);
        });
    });

    // Admin and Authority Accessible Routes
    Route::middleware([\App\Http\Middleware\AllowAdminAndAuthority::class])->group(function () {
        Route::group(['prefix' => 'emergencies'], function () {
            Route::get('archival', [\App\Http\Controllers\EmergencyController::class, 'getArchivalEmergencies']);
            Route::post('merge', [\App\Http\Controllers\EmergencyController::class, 'mergeEmergencies']);
            Route::get('create_form', [\App\Http\Controllers\EmergencyController::class, 'getEmergencyCreateForm']);
            Route::post('bulk_delete', [\App\Http\Controllers\EmergencyController::class, 'bulkDeleteEmergencies']);
            Route::get('/', [\App\Http\Controllers\EmergencyController::class, 'getEmergencies']);

            Route::group(['prefix' => '{id}'], function () {
                Route::get('merge-able', [\App\Http\Controllers\EmergencyController::class, 'getMergeableEmergencies']);
                Route::get('all', [\App\Http\Controllers\EmergencyController::class, 'getAllEmergencyData']);
                Route::get('form', [\App\Http\Controllers\EmergencyController::class, 'getEmergencyForm']);
                Route::post('assign_agents', [\App\Http\Controllers\EmergencyController::class, 'assignAgentsToEmergency']);
                Route::post('remove_agents', [\App\Http\Controllers\EmergencyController::class, 'removeAgentsFromEmergency']);
                Route::get('chat_room', [\App\Http\Controllers\EmergencyController::class, 'getChatRoom']);
                Route::group(['prefix' => 'notes'], function () {
                    Route::get('/', [\App\Http\Controllers\EmergencyController::class, 'getEmergencyNotes']);
                    Route::post('/', [\App\Http\Controllers\EmergencyController::class, 'postEmergencyNote']);
                });
                Route::get('/', [\App\Http\Controllers\EmergencyController::class, 'getEmergency']);
                Route::put('/', [\App\Http\Controllers\EmergencyController::class, 'updateEmergency']);
                Route::delete('/', [\App\Http\Controllers\EmergencyController::class, 'deleteEmergency']);
            });
        });

        Route::get('available-agents', [\App\Http\Controllers\AgentController::class, 'getAvailableAgentsCollection']);

        Route::group(['prefix' => 'agents'], function () {
            Route::get('available', [\App\Http\Controllers\AgentController::class, 'getAvailableAgents']);
            Route::get('create_form', [\App\Http\Controllers\AgentController::class, 'getAgentCreateForm']);
            Route::post('bulk_delete', [\App\Http\Controllers\AgentController::class, 'bulkDeleteAgents']);
            Route::group(['prefix' => '{id}'], function () {
                Route::get('form', [\App\Http\Controllers\AgentController::class, 'getAgentForm']);
                Route::get('/', [\App\Http\Controllers\AgentController::class, 'getAgent']);
                Route::put('/', [\App\Http\Controllers\AgentController::class, 'updateAgent']);
                Route::delete('/', [\App\Http\Controllers\AgentController::class, 'deleteAgent']);
            });

            Route::get('/', [\App\Http\Controllers\AgentController::class, 'getAgents']);
            Route::post('/', [\App\Http\Controllers\AgentController::class, 'createAgentRoute']);
        });
    });

    // Routes only accessible by Admin
    Route::middleware([\App\Http\Middleware\ProtectAdmin::class])->group(function () {
        Route::group(['prefix' => 'authorities'], function () {
            Route::get('/', [\App\Http\Controllers\AuthorityController::class, 'getAuthorities']);
            Route::get('create_form', [\App\Http\Controllers\AuthorityController::class, 'getAuthorityCreateForm']);
            Route::post('bulk_delete', [\App\Http\Controllers\AuthorityController::class, 'bulkDeleteAuthorities']);
            Route::post('/', [\App\Http\Controllers\AuthorityController::class, 'createAuthorityFromForm']);
            Route::group(['prefix' => '{id}'], function () {
                Route::get('form', [\App\Http\Controllers\AuthorityController::class, 'getAuthorityForm']);
                Route::get('/', [\App\Http\Controllers\AuthorityController::class, 'getAuthority']);
                Route::put('/', [\App\Http\Controllers\AuthorityController::class, 'updateAuthority']);
                Route::delete('/', [\App\Http\Controllers\AuthorityController::class, 'deleteAuthority']);
            });
        });

        Route::group(['prefix' => 'chat_rooms'], function () {
            Route::get('/', [\App\Http\Controllers\ChatRoomController::class, 'getAllChatRooms']);
        });

        Route::group(['prefix' => 'customers'], function () {
            Route::get('create_form', [\App\Http\Controllers\CustomerController::class, 'getCustomerCreateForm']);
            Route::post('bulk_delete', [\App\Http\Controllers\CustomerController::class, 'bulkDeleteCustomers']);
            Route::group(['prefix' => '{id}'], function () {
                Route::get('form', [\App\Http\Controllers\CustomerController::class, 'getCustomerForm']);
                Route::get('/', [\App\Http\Controllers\CustomerController::class, 'getCustomer']);
                Route::put('/', [\App\Http\Controllers\CustomerController::class, 'updateCustomer']);
                Route::delete('/', [\App\Http\Controllers\CustomerController::class, 'deleteCustomer']);
            });
            Route::get('/', [\App\Http\Controllers\CustomerController::class, 'getCustomers']);
        });

        Route::group(['prefix' => 'admin'], function () {
            Route::get('tables', [\App\Http\Controllers\AdminController::class, 'getTableRoutes']);
            Route::post('backup', [\App\Http\Controllers\AdminController::class, 'takeProjectBackup']);
            Route::get('get_backup/{name}', [\App\Http\Controllers\AdminController::class, 'getBackup']);
            Route::post('get_file', [\App\Http\Controllers\AdminController::class, 'getFileFromS3']);
        });
    });
});

// Serving files without authentication (for now)
Route::group(['prefix' => 'emergencies'], function () {
    Route::group(['prefix' => '{id}'], function () {
        Route::get('get_file/{file_name}', [\App\Http\Controllers\EmergencyController::class, 'getEmergencyFile']);
    });
});

