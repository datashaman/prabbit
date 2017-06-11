<?php

use App\Events\PullRequestEvent;
use App\Events\StatusEvent;
use Illuminate\Http\Request;

Route::get('/', function () {
    $data = app(App\Service::class)->mapData();
    return view('welcome', $data);
});

Route::post('/webhook', function (Request $request) {
    $service = app(App\Service::class);

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
                case 'closed':
                    event(
                        new PullRequestEvent(
                            array_only(
                                $payload->all(),
                                [
                                    'action',
                                    'number',
                                    'pull_request.merged',
                                ]
                            )
                        )
                    );
                    break;
                case 'labeled':
                case 'unlabeled':
                    event(
                        new PullRequestEvent(
                            array_only(
                                $payload->all(),
                                [
                                    'action',
                                    'number',
                                    'label',
                                ]
                            )
                        )
                    );
                    break;
                case 'opened':
                case 'reopened':
                    event(
                        new PullRequestEvent(
                            $service->mapPullRequest(
                                $payload->get('pull_request')
                            )
                        )
                    );
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
            break;
        case 'status':
            event(
                new StatusEvent(
                    array_only(
                        $payload->all(),
                        [
                            'target_url',
                            'context',
                            'description',
                            'state',
                        ]
                    )
                )
            );
            break;
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
