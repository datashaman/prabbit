<?php

use Illuminate\Http\Request;

Route::get('/', function () {
    $data = app(App\Service::class)->mapData();
    return view('welcome', $data);
});

Route::post('/webhook', function (Request $request) {
    $event = $request->header('X-GitHub-Event');

    switch ($event) {
        case 'ping':
            return response()
                ->json(
                    [
                        'status' => 'success',
                        'message' => 'pong',
                    ]
                );
        default:
            return response()
                ->json(
                    [
                        'status' => 'error',
                        'message' => 'Unhandled event',
                        'data' => compact('event')
                    ],
                    400
                );
    }
})->middleware('verifySignature');
