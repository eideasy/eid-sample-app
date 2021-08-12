<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $availableLangs = ['et', 'en', 'lv', 'lt', 'ru'];

        if ($request->input('lang')) {
            $language = substr($request->lang, 0, 2);
            if (in_array($language, $availableLangs)) {
                App::setLocale($language);
            }
        }

        if (session()->has('applocale')) {
            App::setLocale(session('applocale'));
        }

        return $next($request);
    }
}
