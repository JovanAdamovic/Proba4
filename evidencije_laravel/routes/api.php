<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PredmetController;
use App\Http\Controllers\UpisController;
use App\Http\Controllers\ZadatakController;
use App\Http\Controllers\PredajaController;
use App\Http\Controllers\ProveraPlagijataController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KalendarController;
use App\Http\Controllers\StatistikaController;


Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']); 

    Route::get('/users', [UserController::class, 'index']); 

    Route::get('/predmeti/moji', [PredmetController::class, 'moji']);
    Route::get('/zadaci/moji', [ZadatakController::class, 'moji']);
    Route::get('/predaje/moje', [PredajaController::class, 'moje']); 
    Route::get('/predaje/za-moje-predmete', [PredajaController::class, 'zaMojePredmete']); 

    Route::get('/kalendar/rokovi', [KalendarController::class, 'rokovi']);
    Route::get('/statistika/mesecno', [StatistikaController::class, 'mesecno']);


    Route::get('/predmeti', [PredmetController::class, 'index']);
    Route::get('/predmeti/{id}', [PredmetController::class, 'show']);

    Route::get('/zadaci', [ZadatakController::class, 'index']);
    Route::get('/zadaci/{id}', [ZadatakController::class, 'show']);

    Route::get('/predaje', [PredajaController::class, 'index']);
    Route::get('/predaje/{id}/file', [PredajaController::class, 'file']);
    Route::get('/predaje/{id}', [PredajaController::class, 'show']);

    Route::post('/predaje', [PredajaController::class, 'store']);
    Route::delete('/predaje/{id}', [PredajaController::class, 'destroy']);

    Route::post('/zadaci', [ZadatakController::class, 'store']);
    Route::put('/zadaci/{id}', [ZadatakController::class, 'update']);
    Route::delete('/zadaci/{id}', [ZadatakController::class, 'destroy']);
    Route::put('/predaje/{id}', [PredajaController::class, 'update']); 

    Route::get('/provere-plagijata', [ProveraPlagijataController::class, 'index']);
    Route::get('/provere-plagijata/{id}', [ProveraPlagijataController::class, 'show']);
    Route::post('/predaje/{predajaId}/provera-plagijata', [ProveraPlagijataController::class, 'pokreni']);

    Route::post('/predmeti', [PredmetController::class, 'store']);
    Route::put('/predmeti/{id}', [PredmetController::class, 'update']);
    Route::delete('/predmeti/{id}', [PredmetController::class, 'destroy']);

    Route::get('/upisi', [UpisController::class, 'index']);
    Route::get('/upisi/{id}', [UpisController::class, 'show']);
    Route::post('/upisi', [UpisController::class, 'store']);
    Route::put('/upisi/{id}', [UpisController::class, 'update']);
    Route::delete('/upisi/{id}', [UpisController::class, 'destroy']);
});

