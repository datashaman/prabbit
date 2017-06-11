<!doctype html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Laravel</title>
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet">
        <link href="https://opensource.keycdn.com/fontawesome/4.7.0/font-awesome.min.css" rel="stylesheet">
        <style>
        .content {
            padding: 20px;
        }
        .fa-bug {
            color: #cb2431;
        }
        .fa-check {
            color: #28a745;
        }
        .labels {
            list-style-type: none;
        }
        .labels li {
            display: inline;
        }
        .label {
            color: #ffffff;
            padding: 3px;
        }
        </style>

        <script src="https://js.pusher.com/4.0/pusher.min.js"></script>
        <script>
            // Enable pusher logging - don't include this in production
            Pusher.logToConsole = true;

            var pusher = new Pusher('72ca32a21430c1d8ac8d', {
                cluster: 'eu',
                encrypted: true
            });

            var channel = pusher.subscribe('my-channel');
            channel.bind('my-event', function(data) {
                alert(data.message);
            });
        </script>
    </head>
    <body>
        <div class="position-ref full-height">
            @if (Route::has('login'))
                <div class="top-right links">
                    @if (Auth::check())
                        <a href="{{ url('/home') }}">Home</a>
                    @else
                        <a href="{{ url('/login') }}">Login</a>
                        <a href="{{ url('/register') }}">Register</a>
                    @endif
                </div>
            @endif

            <div class="content">
                <h1>
                    PRabbit
                </h1>

                <div class="pull-requests">
                    @foreach ($pullRequests as $pr)
                        <h2>
                            <i class="fa fa-{{ $pr['status']['icon'] }}"></i>

                            <a href="{{ $pr['html_url'] }}">
                                <span class="number">#{{ $pr['number'] }}</span>
                                {{ $pr['title'] }}
                            </a>
                            by
                            <a href="{{ $pr['user']['html_url'] }}">
                                <img src="{{ $pr['user']['avatar_url'] }}" height="20" width="20" />
                                {{ $pr['user']['login'] }}
                            </a>
                        </h2>

                        <ul class="labels">
                            @foreach ($pr['labels'] as $label)
                                <li><span class="label" style="background-color:#{{ $label['color'] }}" >{{ $label['name'] }}</span></li>
                            @endforeach
                        </ul>

                        <ul class="statuses">
                            @foreach ($pr['status']['statuses'] as $status)
                                <li>
                                    <div class="status">
                                        @if (starts_with($status['icon'], 'http'))
                                            <img src="{{ $status['icon'] }}" height="20" width="20" />
                                        @else
                                            <i class="fa fa-{{ $status['icon'] }}"></i>
                                        @endif
                                        <a target="_blank" href="{{ $status['target_url'] }}">{{ $status['description'] }}</a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </div>
    </body>
</html>
