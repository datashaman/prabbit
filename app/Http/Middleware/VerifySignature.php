<?php

namespace App\Http\Middleware;

use Closure;

class VerifySignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $data = $request->getContent();
        $key = config('prabbit.github.secret');

        $signature = 'sha1=' . hash_hmac(
            'sha1',
            $data,
            $key
        );

        if (
            !hash_equals(
                $signature,
                $request->header('X-Hub-Signature')
            )
        ) {
            return response()
                ->json(
                    [
                        'status' => 'fail',
                        'message' => 'Signature mismatch',
                    ],
                    400
                );
        }

        return $next($request);
    }
}
