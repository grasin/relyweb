<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\DB;
use Closure;

class Login
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
		if(\App\Administrator::where('secure', session('admin'))->count() > 0){
			return $next($request);
		}
        return redirect('admin/login');
    }
}
