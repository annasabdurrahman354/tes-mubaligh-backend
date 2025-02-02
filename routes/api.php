<?php

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

Route::post('login-credential', [\App\Http\Controllers\Api\AuthController::class, 'loginWithCredential']);
Route::post('login-nfc', [\App\Http\Controllers\Api\AuthController::class, 'loginWithNfc']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('update-password', [\App\Http\Controllers\Api\AuthController::class, 'updatePassword']);
    Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::post('logout-from-all-devices', [\App\Http\Controllers\Api\AuthController::class, 'logoutFromAllDevices']);
    Route::get('peserta-kediri', [\App\Http\Controllers\Api\PesertaKediriController::class, 'index']);
    Route::get('peserta-kediri/getByNfc', [\App\Http\Controllers\Api\PesertaKediriController::class, 'getByNfc']);

    Route::get('peserta-kertosono', [\App\Http\Controllers\Api\PesertaKertosonoController::class, 'index']);
    Route::get('peserta-kertosono/getByNfc', [\App\Http\Controllers\Api\PesertaKertosonoController::class, 'getByNfc']);
    Route::get('peserta-kertosono/getAuthSimak', [\App\Http\Controllers\Api\PesertaKertosonoController::class, 'getAuthSimak']);

    Route::post('akademik-kediri', [\App\Http\Controllers\Api\AkademikKediriController::class, 'store']);
    Route::post('akhlak-kediri', [\App\Http\Controllers\Api\AkhlakKediriController::class, 'store']);

    Route::post('akademik-kertosono', [\App\Http\Controllers\Api\AkademikKertosonoController::class, 'store']);
    Route::post('akhlak-kertosono', [\App\Http\Controllers\Api\AkhlakKertosonoController::class, 'store']);

    Route::get('statistik-kediri', [\App\Http\Controllers\Api\StatisticsController::class, 'getStatistikKediri']);
    Route::get('statistik-kertosono', [\App\Http\Controllers\Api\StatisticsController::class, 'getStatistikKertosono']);
});
