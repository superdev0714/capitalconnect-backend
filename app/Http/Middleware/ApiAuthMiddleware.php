<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\User;

use Closure;

class ApiAuthMiddleware
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
        $token = $request->header('X-Authorization');
        $prefix = 'Token ';

        if ($token != null && substr($token, 0, strlen($prefix)) === $prefix) {
            $token = substr($token, strlen($prefix));
            if ($this->checkAuthorization($token))
                return $next($request);
        }

        return response()->json([
            'errors' => ['You are not an authorized user.']
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function checkAuthorization($token) {
        if (Auth::check()) {
            $user = Auth::user();
            if ($token === $user->remember_token) {
                return true;
            }
        }

        $count = User::where('remember_token', $token)->count();
        if ($count === 1) {
            $user = User::where('remember_token', $token)->first();
            Auth::login($user, true);
            return true;
        }

        return false;
    }
}
