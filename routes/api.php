<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;
use App\Http\Controllers\UserController;

Route::post('/events', [EventController::class, 'store']);
Route::post('/events/update', [EventController::class, 'update']);
Route::post('/toggle-going', [EventController::class, 'toggleGoing']);

Route::post('/upload-avatar', [UserController::class, 'uploadAvatar']);
Route::post('/update-name', [UserController::class, 'updateName']);

Route::get('/events', [EventController::class, 'getEvents']);
Route::get('/events-organized-by-user/{user_id}', [EventController::class, 'getEventsOrganizedByUser']);
Route::get('/events-to-which-user-is-going/{user_id}', [EventController::class, 'getEventsToWhichUserIsGoing']);
Route::get('/events/search', [EventController::class, 'search']);
