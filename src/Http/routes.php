<?php

use Illuminate\Support\Facades\Route;
use DmLogic\GooglePhotoIndex\Http\Controllers\GoogleOAuthController;

Route::get('/google-oauth/start', [GoogleOAuthController::class, 'start'])
     ->name('oauth.start');
Route::post('/google-oauth/request', [GoogleOAuthController::class, 'generateRequest'])
     ->name('oauth.generate');
Route::get('/google-oauth/handle', [GoogleOAuthController::class, 'handleRedirect'])
     ->name('oauth.handle');
