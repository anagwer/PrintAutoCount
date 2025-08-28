<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NumberSequenceController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return 'User is authenticated';
});

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('users/change-password', [UserController::class, 'changePassword']);

    Route::group(['middleware' => 'permission:Auth - Read'], function () {
        Route::get('/admin', function () {
            return 'Admin is authenticated';
        });

        Route::post('/register', [AuthController::class, 'register']);

        Route::group(['prefix' => 'organizations'], function () {
            Route::get('/', [OrganizationController::class, 'get']);
            Route::get('/{id}', [OrganizationController::class, 'id']);
            Route::post('/', [OrganizationController::class, 'store']);
            Route::patch('/{id}', [OrganizationController::class, 'update']);
            Route::delete('/{id}', [OrganizationController::class, 'destroy']);
        });
        Route::group(['prefix' => 'number-sequences'], function () {
            Route::get('/', [NumberSequenceController::class, 'get']);
            Route::get('/{id}', [NumberSequenceController::class, 'id']);
            Route::post('/', [NumberSequenceController::class, 'store']);
            Route::put('/{id}', [NumberSequenceController::class, 'update']);
        });

        Route::group(['prefix' => 'permissions'], function () {
            Route::get('/', [PermissionController::class, 'get']);
            Route::post('/', [PermissionController::class, 'store']);
            Route::get('/{id}', [PermissionController::class, 'id']);
            Route::patch('/{id}', [PermissionController::class, 'update']);
            Route::delete('/{id}', [PermissionController::class, 'destroy']);
        });

        Route::group(['prefix' => 'roles'], function () {
            Route::get('/', [RoleController::class, 'get']);
            Route::post('/', [RoleController::class, 'store']);
            Route::post('/permission/sync', [RoleController::class, 'assignPermissionToRole']);
            Route::post('asign/user', [RoleController::class, 'assignRoleToUser']);
            Route::get('/{id}', [RoleController::class, 'id']);
            Route::patch('/{id}', [RoleController::class, 'update']);
            Route::delete('/{id}', [RoleController::class, 'destroy']);
        });

        Route::group(['prefix' => 'users'], function () {
            Route::get('/', [UserController::class, 'get']);
            Route::post('/', [UserController::class, 'store']);
            Route::patch('/{id}', [UserController::class, 'update']);
            Route::post('/reset-password', [UserController::class, 'resetPassword']);
            Route::patch('/role/{id}', [UserController::class, 'setRole']);
        });

        Route::group(['prefix' => 'lah-report'], function () {
            Route::get('/', [ReportController::class, 'get']);
            Route::get('/debtor', [ReportController::class, 'DebtorReport']);
            Route::get('/export', [ReportController::class, 'DebtorReportExcel']);
        });

        Route::group(['prefix' => 'area'], function () {
            Route::get('/', [AreaController::class, 'get']);
        });

    });

});
