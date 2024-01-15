<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/api/events',
        '/api/events/update/',
        '/api/upload-avatar',
        '/api/toggle-going',
        '/api/update-name',
        '/events-organized-by-user/{user_id}',
        '/events-to-which-user-is-going/{user_id}',
        '/events/search',
        'auth/google',
        'auth/google/callback',

        // URIs to be excluded
    ];

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $sessionToken = $request->session()->token();
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        Log::info('CSRF Validation', [
            'sessionToken' => $sessionToken,
            'inputToken' => $token,
            'headerToken' => $request->header('X-CSRF-TOKEN'),
            'requestURL' => $request->fullUrl(),
            'requestHeaders' => $request->headers->all()
        ]);

        return parent::tokensMatch($request);
    }
}
