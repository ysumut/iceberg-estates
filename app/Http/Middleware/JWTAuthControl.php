<?php

namespace App\Http\Middleware;

use App\Http\Resources\Collection;
use Closure;
use Illuminate\Http\Request;

class JWTAuthControl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if(auth()->check()) {
            return $next($request);
        }
        else {
            return (new Collection([], 401))->response(false, ['JWT is invalid!']);
        }
    }
}
