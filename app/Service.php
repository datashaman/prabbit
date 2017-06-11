<?php

namespace App;

use Cache;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\LaravelCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use League\Flysystem\Adapter\Local;
use Log;
use Illuminate\Http\Request;

class Service extends Fluent
{
    public function service(
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

    public function repo(): Client
    {
        $repo = $this->github['repo'];
        return $this
            ->service(
                'github', 
                [
                    'base_uri' => config('services.github.base_uri') . "repos/$repo/",
                ]
            );
    }

    public function json(Response $response): Collection
    {
        return collect(json_decode($response->getBody(), true));
    }

    public function markdown(string $text): string
    {
        $repo = $this->github['repo'];
        $key = sha1("$repo-$text");

        $html = Cache::rememberForever(
            $key,
            function () use ($repo, $text): string {
                return (string) $this
                    ->service('github')
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

    public function openPullRequests(
        string $userLogin = null
    ): Collection {
        $options = [
            'query' => [
                'status' => 'open',
            ],
        ];
        $pullRequests = $this->json($this->repo()->get('pulls', $options));

        if (!empty($userLogin)) {
            return $pullRequests->where('user.login', $userLogin);
        }

        return $pullRequests;
    }

    public function buildJob()
    {
        return $this->json(
            $this
                ->service('jenkins')
                ->get('job/Horizon/view/PR/job/PR-HorizonWebApp-QA')
        );
    }

    public function status($ref)
    {
        $icons = $this->icons;
        $status = $this->json($this->repo()->get("commits/$ref/status"));

        $status['icon'] = $icons['states'][$status['state']];
        $status['statuses'] = collect($status['statuses'])
            ->map(function ($s) use ($icons) {
                $s['icon'] = $icons['statuses'][$s['context']];
                return $s;
            })
            ->all();

        return $status;
    }

    public function mapPullRequest(array $pr): array
    {
        Log::debug('pull request', compact('pr'));

        $labels = $this->json($this->repo()->get(array_get($pr, '_links.issue.href')))->get('labels');

        $status = $this->status(array_get($pr, 'head.sha'));

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
                'merged',
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
    }

    public function mapData()
    {
        $pullRequests = $this->openPullRequests()
            ->map([$this, 'mapPullRequest']);
        return compact('pullRequests');
    }
}
