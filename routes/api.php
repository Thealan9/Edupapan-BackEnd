<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AvailabilityController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\ContextController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LocalController;
use App\Http\Controllers\Admin\PalletController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TicketDetailController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Warehouseman\TicketController as WarehousemanTicket;
use App\Http\Controllers\Warehouseman\TicketDetailController as WarehousemanTicketDetail;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);




Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/yo', fn(Request $r) => $r->user());
    Route::post('/logout', [AuthController::class, 'logout']);


    Route::prefix('admin')->middleware('role:admin')->group(function () {

        // Vista Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Vista Usuarios
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);

        //libros
        Route::get('/book', [BookController::class, 'index']);
        Route::get('/book/{libro}', [BookController::class, 'show']);
        Route::post('/book', [BookController::class, 'store']);
        Route::put('/book/{libro}', [BookController::class, 'update']);
        Route::delete('/book/{libro}', [BookController::class, 'destroy']);

        //vehiculos
        Route::get('/vehicle', [VehicleController::class, 'index']);
        Route::get('/vehicle/{veh}', [VehicleController::class, 'show']);
        Route::post('/vehicle', [VehicleController::class, 'store']);
        Route::put('/vehicle/{veh}', [VehicleController::class, 'update']);
        Route::delete('/vehicle/{veh}', [VehicleController::class, 'destroy']);

        //pallet
        Route::get('/pallet', [PalletController::class, 'index']);
        Route::get('/pallet/{pallet}', [PalletController::class, 'show']);
        Route::post('/pallet', [PalletController::class, 'store']);
        Route::put('/pallet/{pallet}', [PalletController::class, 'update']);
        Route::delete('/pallet/{pallet}', [PalletController::class, 'destroy']);

        //TIckets
        Route::post('/ticket/entry', [TicketController::class, 'createEntry']);
        Route::post('/ticket/sale', [TicketController::class, 'createSale']);
        Route::post('/ticket/removed', [TicketController::class, 'createRemoved']);
        Route::post('/ticket/change', [TicketController::class, 'createChange']);

        Route::post('/ticket/{ticket}/accept-partial', [TicketController::class, 'ApprovePartial']);
        Route::post('/ticket/{ticket}/reject-partial', [TicketController::class, 'RejectPartial']);

        //Almacenista flujo
        Route::patch('/ticket/{ticket}/accept', [WarehousemanTicket::class, 'accept']);
        Route::patch('/ticket-detail/{detail}/process', [WarehousemanTicketDetail::class, 'processDetail']);
        Route::post('/ticket/{ticket}/complete-entry', [WarehousemanTicket::class, 'completeEntry']);
        Route::post('/ticket/{ticket}/complete-sale', [WarehousemanTicket::class, 'completeSale']);
        Route::post('/ticket/{ticket}/complete-change', [WarehousemanTicket::class, 'completeChange']);
        Route::post('/ticket/{ticket}/complete-removed', [WarehousemanTicket::class, 'completeRemoved']);

        //solicitar completar parcial
        Route::post('/ticket/{ticket}/request-partial', [WarehousemanTicket::class, 'confirmPartial']);
        Route::post('/ticket/{ticket}/complete-partial', [WarehousemanTicket::class, 'completePartialTicket']);
        //autocompletar si faltan paquetes en la venta
        Route::post('/ticket/{ticket}/autocomplete-sale', [WarehousemanTicketDetail::class, 'addDetail']);


        // //locales
        // Route::get('/locals', [LocalController::class, 'index']);
        // Route::post('/locals', [LocalController::class, 'store']);
        // Route::get('/locals/{local}', [LocalController::class, 'show']);
        // Route::put('/locals/{local}', [LocalController::class, 'update']);
        // Route::delete('/locals/{local}', [LocalController::class, 'destroy']);

        // // servicios
        // Route::get('/services', [ServiceController::class, 'index']);
        // Route::post('/services', [ServiceController::class, 'store']);
        // Route::get('/services/{service}', [ServiceController::class, 'show']);
        // Route::put('/services/{service}', [ServiceController::class, 'update']);
        // Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

        // // context
        // Route::post('/service-contexts/{service}', [ContextController::class, 'store']);
        // Route::put('/service-contexts/{context}', [ContextController::class, 'update']);
        // Route::delete('/service-contexts/{context}', [ContextController::class, 'destroy']);

        // // availability
        // Route::post('/service-availability/{service}/', [AvailabilityController::class, 'store']);
        // Route::put('/service-availability/{availability}', [AvailabilityController::class, 'update']);
        // Route::delete('/service-availability/{availability}', [AvailabilityController::class, 'destroy']);
        // Route::post('/service-availability/{service}/range', [AvailabilityController::class, 'storeRange']);

        // // reservas
        // Route::get('/bookings', [BookingController::class, 'index']);
        // Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        // Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);
        // Route::delete('/bookings/{booking}', [BookingController::class, 'cancel']);
    });
});
