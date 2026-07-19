<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** @var array<int, string> */
    protected array $supportedLocales = ['en', 'ar', 'ckb'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale'));

        if ($request->has('lang') && in_array($request->string('lang')->toString(), $this->supportedLocales, true)) {
            $locale = $request->string('lang')->toString();
            $request->session()->put('locale', $locale);
        }

        if (in_array($locale, $this->supportedLocales, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
