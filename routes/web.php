<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Features\FeaturesController;
use App\Http\Controllers\Features\DashboardController;
use App\Http\Controllers\Features\InventoryController;
use App\Http\Controllers\Features\UsersController;
use App\Http\Controllers\Features\QRCodeController;
use App\Http\Controllers\Features\EvacuationController as FeaturesEvacuationController;
use App\Http\Controllers\PhoneFeatures\PhoneFeaturesController;
use App\Http\Controllers\PhoneFeatures\AddInventoryController;
use App\Http\Controllers\PhoneFeatures\EvacuationController as PhoneEvacuationController;
use App\Http\Controllers\PhoneFeatures\ReliefGoodsController;
// Authentication Routes
Route::get('/', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

Route::middleware('firebase.auth')->group(function () {
    Route::get('/features', [FeaturesController::class, 'index'])->name('features');

    Route::get('/features/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/features/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/features/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/features/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/features/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::get('/features/inventory/batch/{batchId}', [InventoryController::class, 'batch'])->name('inventory.batch');

    Route::get('/features/users', [UsersController::class, 'index'])->name('users.index');
    Route::post('/features/users', [UsersController::class, 'store'])->name('users.store');
    Route::put('/features/users/{user}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('/features/users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');

    Route::get('/features/qr', [QRCodeController::class, 'index'])->name('qrcode.index');
    Route::get('/features/qr/next-relief-pack', [QRCodeController::class, 'nextReliefPack'])->name('qrcode.relief-pack.next');
    Route::post('/features/qr/reserve-relief-pack', [QRCodeController::class, 'reserveReliefPack'])->name('qrcode.relief-pack.reserve');
    Route::post('/features/qr/scan', [QRCodeController::class, 'scan'])->name('qrcode.scan');

    Route::get('/features/evacuation', [FeaturesEvacuationController::class, 'index'])->name('evacuation.index');

    Route::get('/phoneFeatures', [PhoneFeaturesController::class, 'index'])->name('phoneFeatures');
    Route::get('/phoneFeatures/addInventory', [AddInventoryController::class, 'index'])->name('phoneFeatures.addInventory');
    Route::post('/phoneFeatures/addInventory', [AddInventoryController::class, 'store'])->name('phoneFeatures.addInventory.store');
    Route::get('/phoneFeatures/evacuation', [PhoneEvacuationController::class, 'index'])->name('phoneFeatures.evacuation');
    Route::post('/phoneFeatures/evacuation/scan', [PhoneEvacuationController::class, 'scan'])->name('phoneFeatures.evacuation.scan');
    Route::get('/phoneFeatures/reliefGoods', [ReliefGoodsController::class, 'index'])->name('phoneFeatures.reliefGoods');
    Route::post('/phoneFeatures/reliefGoods/scan', [ReliefGoodsController::class, 'scan'])->name('phoneFeatures.reliefGoods.scan');
});
