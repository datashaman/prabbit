<?php

use Illuminate\Http\Request;

Route::get('/', function () {
    $data = app(App\Service::class)->mapData();
    return view('welcome', $data);
});

Route::get('/webhook', function (Request $request) {
    app(App\Service::class)->verifySignature($request);
    return 'OK';
});
