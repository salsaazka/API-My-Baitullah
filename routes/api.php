<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\OtpController;
use App\Http\Controllers\API\VoiceController;
use App\Http\Controllers\API\LogoutController;
use App\Http\Controllers\API\AuthController;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return response()->json(Auth::user());
});


// AUTH
Route::post('/login', App\Http\Controllers\Api\LoginController::class)->name('login');
Route::post('/register', App\Http\Controllers\Api\RegisterController::class)->name('register');
Route::post('/register_ios', App\Http\Controllers\Api\RegisterController::class)->name('register_ios');

Route::post('kirim_otp', [OtpController::class, 'kirim_otp']);

// USER
Route::middleware(['auth:api'])->group(function () {
    Route::get('user', [UserController::class, 'fetch']);
    Route::post('user/update_profile_user', [UserController::class, 'updateProfile']);
    Route::post('user_photo', [UserController::class, 'updatePhoto']);
    Route::post('user/update_rekening', [UserController::class, 'updateRekening']);
    Route::get('user/get_user_by_id/{id}', [UserController::class, 'getUserById']);
    // Route::post('user/update_ktp', [UserController::class, 'updateKtp']);
    // Route::post('user/update_vaksin', [UserController::class, 'updateVaksin']);
    Route::post('user/update_status_login', [UserController::class, 'updateStatusLogin']);
    Route::post('user/update_device_id', [UserController::class, 'updateDeviceId']);
    
});

// DELETE ACCOUNT
Route::delete('/delete-account', [UserController::class, 'deleteAccount']);

Route::post('/logout', App\Http\Controllers\Api\LogoutController::class)->name('logout');
Route::middleware('auth:api')->post('/refresh', [AuthController::class, 'refreshToken']);

Route::group(['prefix' => 'voice_channel', 'middleware' => ['auth:api']], function () {
    Route::post('/create', [VoiceController::class, 'create']);
    Route::post('/insert_voice', [VoiceController::class, 'insertTrsVoice']);
    Route::post('/update_trs_voice', [VoiceController::class, 'updateTrsVoice']);
    Route::get('/get_all_data', [VoiceController::class, 'getAllData']);
    Route::get('/get_my_data/{id}', [VoiceController::class, 'getMyData']);
    Route::get('/search', [VoiceController::class, 'searchData']);
    Route::get('/cek_transaksi_voice', [VoiceController::class, 'cekTransaksiVoice']);
    
    // Cek user online
    Route::post('/insert_voice_online', [VoiceController::class, 'insertLogVoiceOnline']);
    Route::post('/update_voice_online', [VoiceController::class, 'updateLogVoiceOnline']);
    Route::get('/realtime_user_online/{id}', [VoiceController::class, 'getRealtimeUserOnline']);
});