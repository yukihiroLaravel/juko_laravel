<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('login')->group(function () {
    Route::post('/', 'Auth\LoginController')->name('login');
    Route::post('instructor', 'Auth\InstructorLoginController')->name('instructor.login');
});

Route::prefix('logout')->group(function () {
    Route::post('/', 'Auth\LogoutController')->name('logout');
    Route::post('instructor', 'Auth\InstructorLogoutController')->name('instructor.logout');
});
