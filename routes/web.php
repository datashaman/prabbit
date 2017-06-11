<?php

use Illuminate\Http\Request;

Route::get('/', function () {
    $data = app(App\Service::class)->mapData();
    return view('welcome', $data);
});

Route::post('/webhook', function (Request $request) {
    $payload = $request->json();

    Log::debug('webhook', ['payload' => $payload->all()]);

    $event = $request->header('X-GitHub-Event');
    $action = $payload->get('action');

    switch ($event) {
        case 'ping':
            return response()
                ->json(
                    [
                        'status' => 'success',
                        'message' => 'pong',
                    ]
                );
        case 'pull_request':
            switch ($action) {
                case 'labeled':
                case 'opened':
                default:
                    return response()
                        ->json(
                            [
                                'status' => 'error',
                                'message' => 'Unhandled action',
                                'data' => compact('event', 'action')
                            ],
                            400
                        );
            }
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
