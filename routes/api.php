<?php

use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/teams', [TeamController::class, 'index']);
Route::post('/teams', [TeamController::class, 'store']);
Route::put('/teams/{teamName}', [TeamController::class, 'update']);
Route::delete('/teams/{teamName}', [TeamController::class, 'destroy']);
Route::delete('/teams', [TeamController::class, 'destroyAll']);
Route::post('/teams/defaults', [TeamController::class, 'resetDefaults']);

Route::post('/fixtures', [MatchController::class, 'generateFixtures']);
Route::put('/fixtures/play', [MatchController::class, 'playCurrentWeek']);
Route::get('/fixtures/standings', [MatchController::class, 'getStandings']);
Route::put('/fixtures/play-all', [MatchController::class, 'playAllWeeks']);
Route::get('/fixtures/championship-predictions', [MatchController::class, 'getChampionshipPredictions']);
Route::put('/fixtures/update-result', [MatchController::class, 'updateMatchResult']);
