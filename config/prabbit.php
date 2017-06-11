<?php

return [
    'icons' => [
        'states' => [
            'pending' => 'circle',
            'success' => 'check',
            'error' => 'exclamation-circle',
            'failure' => 'bug',
        ],
        'statuses' => [
            'continuous-integration/styleci/pr' => 'https://cdn.styleci.io/prod/favicon-32x32.png',
            'continuous-integration/styleci/push' => 'https://cdn.styleci.io/prod/favicon-32x32.png',
            'jenkins/dbs' => 'wrench',
            'jenkins/hz/wapp/build' => 'wrench',
            'jenkins/hz/wapp/qa' => 'ship',
            'jenkins/hz/wapp/sonarqube' => 'https://www.sonarqube.org/favicon.ico',
            'jenkins/hz/wapp/staging' => 'ship',
            'sonarqube' => 'https://www.sonarqube.org/favicon.ico',
        ],
    ],

    'github' => [
        'repo' => 'oneafricamedia/horizon',
        'secret' => env('GITHUB_SECRET'),
    ],
];
