<?php

use App\Http\Controllers\Api\OptionsController;
use App\Http\Controllers\Api\PesertaKertosonoVerifikasiController;
use App\Http\Controllers\Api\PPWB\SiswaPPWBController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\WhatsAppController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/secret/cache', function() {
    $exitCode = \Illuminate\Support\Facades\Artisan::call('optimize');
    $exitCode = \Illuminate\Support\Facades\Artisan::call('filament:optimize');
    return '<h1>Cache facade value refreshed</h1>';
});

Route::post('login-credential', [\App\Http\Controllers\Api\AuthController::class, 'loginByCredential']);
Route::post('login-rfid', [\App\Http\Controllers\Api\AuthController::class, 'loginByRFID']);
Route::post('send-whatsapp', [WhatsAppController::class, 'send']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::post('logout-from-all-devices', [\App\Http\Controllers\Api\AuthController::class, 'logoutFromAllDevices']);
    Route::get('peserta-kediri', [\App\Http\Controllers\Api\PesertaKediriController::class, 'index']);
    Route::get('peserta-kediri/rfid', [\App\Http\Controllers\Api\PesertaKediriController::class, 'getByRFID']);

    Route::get('peserta-kertosono', [\App\Http\Controllers\Api\PesertaKertosonoController::class, 'index']);
    Route::get('peserta-kertosono/rfid', [\App\Http\Controllers\Api\PesertaKertosonoController::class, 'getByRFID']);

    Route::post('akademik-kediri', [\App\Http\Controllers\Api\AkademikKediriController::class, 'store']);
    Route::post('akhlak-kediri', [\App\Http\Controllers\Api\AkhlakKediriController::class, 'store']);

    Route::post('akademik-kertosono', [\App\Http\Controllers\Api\AkademikKertosonoController::class, 'store']);
    Route::post('akhlak-kertosono', [\App\Http\Controllers\Api\AkhlakKertosonoController::class, 'store']);

    Route::get('statistik-kediri', [\App\Http\Controllers\Api\StatisticsController::class, 'getStatistikKediri']);
    Route::get('statistik-kertosono', [\App\Http\Controllers\Api\StatisticsController::class, 'getStatistikKertosono']);

    // Route to get specific Peserta Kertosono data for verification
    Route::get('/peserta-kertosono/verifikasi', [PesertaKertosonoVerifikasiController::class, 'index']);
    Route::get('/peserta-kertosono/verifikasi/{id_tes_santri}', [PesertaKertosonoVerifikasiController::class, 'show']);
    Route::post('/peserta-kertosono/verifikasi/{id_tes_santri}', [PesertaKertosonoVerifikasiController::class, 'update']); // Using PATCH for partial updates

    Route::post('/update-username', [UserProfileController::class, 'updateUsername'])->name('update.username');
    Route::post('/update-password', [UserProfileController::class, 'updatePassword'])->name('update.password');
    Route::post('/update-photo', [UserProfileController::class, 'updatePhoto'])->name('update.photo');
    Route::post('/update-rfid', [UserProfileController::class, 'updateRfid'])->name('update.rfid');

    // --- Routes for Select Options ---
    Route::prefix('options')->group(function () {
        Route::get('provinsi', [OptionsController::class, 'getProvinsi']);
        Route::get('kota/{provinsi_id}', [OptionsController::class, 'getKota']);
        Route::get('kecamatan/{kota_id}', [OptionsController::class, 'getKecamatan']);
        Route::get('kelurahan/{kecamatan_id}', [OptionsController::class, 'getKelurahan']);
        Route::get('daerah-sambung', [OptionsController::class, 'getDaerahSambung']);
        Route::get('ponpes', [OptionsController::class, 'getPonpes']);
    });


});

Route::get('/siswa-ppwb', [SiswaPPWBController::class, 'index']);
Route::post('/siswa-ppwb/update-photo', [SiswaPPWBController::class, 'updatePhoto']);
