<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Since we're only using /login as an api call, no use for
                // redirect. We also can't just remove this middleware as we'd
                // have to replace it with an empty middleware to handle guest
                // routes in Kernel.php, so might as well keep this.
                //return redirect(RouteServiceProvider::HOME);
            }
        }
        return $next($request);
    }
}
