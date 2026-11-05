<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('appearance', $request->cookie('appearance') ?? 'dark');

        // Design de l'interface : « passerelle » (par défaut) ou « large » (Au large).
        $design = $request->cookie('design');
        View::share('design', in_array($design, ['passerelle', 'large'], true) ? $design : 'passerelle');

        return $next($request);
    }
}
