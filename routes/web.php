<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\LaravelCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use League\Flysystem\Adapter\Local;

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
    function service(
        string $name,
        array $options = []
    ): Client {
        static $stack;
        static $adapters = [];

        if (!isset($stack)) {
            $stack = HandlerStack::create();
            $stack->push(
                new CacheMiddleware(
                    new PrivateCacheStrategy(
                        new LaravelCacheStorage(
                            Cache::store('file')
                        )
                    )
                ),
                'cache'
            );
        }

        if (!array_has($adapters, $name)) {
            $options = array_merge(
                config("services.$name"),
                $options,
                ['handler' => $stack]
            );
            $adapters[$name] = new Client($options);
        }

        return $adapters[$name];
    }

    function repo(): Client
    {
        $repo = config('prabbit.repo');
        return service('github', [
            'base_uri' => config('services.github.base_uri') . "repos/$repo/",
        ]);
    }

    function json(Response $response): Collection
    {
        return collect(json_decode($response->getBody(), true));
    }

    function markdown(string $text): string
    {
        $repo = config('prabbit.repo');
        $key = sha1("$repo-$text");

        $html = Cache::rememberForever(
            $key,
            function () use ($repo, $text): string {
                return (string) service('github')
                    ->post('markdown', [
                        'json' => [
                            'text' => $text,
                            'mode' => 'gfm',
                            'context' => $repo,
                        ],
                    ])
                    ->getBody();
            }
        );

        return $html;
    }

    function openPullRequests(
        string $userLogin = null
    ): Collection {
        $options = [
            'query' => [
                'status' => 'open',
            ],
        ];
        $pullRequests = json(repo()->get('pulls', $options));

        if (!empty($userLogin)) {
            return $pullRequests->where('user.login', $userLogin);
        }

        return $pullRequests;
    }

    function buildJob()
    {
        return json(service('jenkins')->get('job/Horizon/view/PR/job/PR-HorizonWebApp-QA'));
    }

    function status($ref)
    {
        $icons = config('prabbit.icons');
        $status = json(repo()->get("commits/$ref/status"));

        $status['icon'] = $icons['states'][$status['state']];
        $status['statuses'] = collect($status['statuses'])
            ->map(function ($s) use ($icons) {
                $s['icon'] = $icons['statuses'][$s['context']];
                return $s;
            })
            ->all();

        return $status;
    }

    function generateData()
    {
        $pullRequests = openPullRequests()
            ->map(function (array $pr): array {
                Log::debug('pull request', compact('pr'));

                $labels = json(repo()->get(array_get($pr, '_links.issue.href')))->get('labels');

                $status = status(array_get($pr, 'head.sha'));

                $user = array_only(
                    $pr['user'],
                    [
                        'avatar_url',
                        'html_url',
                        'login',
                        'url',
                    ]
                );

                return array_only(
                    $pr,
                    [
                        'body',
                        'created_at',
                        'html_url',
                        'number',
                        'title',
                        'updated_at',
                        'url',
                    ]
                ) + compact(
                    'labels',
                    'status',
                    'user'
                );
            });

        return compact('pullRequests');
    }

    return view('welcome', generateData());
});
