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
    $data = app(App\Service::class)->mapData();
    return view('welcome', $data);
});

Route::get('/webhook', function (Request $request) {
    app(App\Service::class)->verifySignature($request);
    return 'OK';
});
