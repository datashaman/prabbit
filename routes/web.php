<?php

use GrahamCampbell\GitHub\Facades\GitHub;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;

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

class Client extends GuzzleClient {
    public function __construct(string $base_uri, string $username, string $token) {
        $auth = [$username, $token];
        parent::__construct(compact('base_uri', 'auth'));
    }  
}

function repo() {
    return 'OneAfricaMedia/horizon';
}

function client(string $base_uri, string $username, string $token) {
    static $adapters = [];

    if (!array_has($adapters, $base_uri)) {
        $adapters[$base_uri] = new Client($base_uri,  $username,  $token);
    }

    return $adapters[$base_uri];
}

function github()
{
    return client('https://api.github.com', env('GITHUB_USERNAME'), env('GITHUB_TOKEN'));
}

function jenkins()
{
    return client('https://jenkins.oam.cool', env('JENKINS_USERNAME'), env('JENKINS_TOKEN'));
}

function json(Response $response): Collection
{
    return collect(json_decode($response->getBody(), true));
}

function openPullRequests(
    string $userLogin = null
): Collection {
    $repo = repo();
    $options = [
        'query' => [
            'status' => 'open',
        ],
    ];
    $pullRequests = json(github()->get("/repos/$repo/pulls", $options));

    if (!empty($userLogin)) {
        return $pullRequests->where('user.login', $userLogin);
    }

    return $pullRequests;
}

function buildJob()
{
    return json(jenkins()->get('/job/Horizon/view/PR/job/PR-HorizonWebApp-QA'));
}

Route::get('/', function () {
    $pullRequests = openPullRequests()
        ->map(function ($pr) {
            $statuses = json(github()->get(array_get($pr, '_links.statuses.href')));

            $build = array_only(
                $statuses->where('context', 'jenkins/hz/wapp/build')->first(),
                [
                    'state',
                    'description',
                    'target_url',
                    'created_at',
                ]
            );

            $user = array_only(
                $pr['user'],
                [
                    'login',
                    'html_url',
                    'url',
                ]
            );

            return array_only(
                $pr,
                [
                    'id',
                    'html_url',
                    'url',
                    'title',
                    'body',
                    'created_at',
                    'updated_at',
                ]
            ) + compact('build', 'user');
        });

    return view('welcome', compact('pullRequests'));
});
