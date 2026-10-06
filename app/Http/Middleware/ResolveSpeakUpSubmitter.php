<?php

namespace App\Http\Middleware;

use App\Support\SpeakUpSubmitter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSpeakUpSubmitter
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->filled('as') && in_array($request->query('as'), ['staff', 'freelancer', 'vendor'], true)) {
            $request->session()->put('speak_up_as', $request->query('as'));
        }

        $submitter = SpeakUpSubmitter::resolve($request->session()->get('speak_up_as'));
        if ($submitter === null) {
            return redirect()->route('home')->with('error', 'Please login first.');
        }

        $request->attributes->set('speak_up_submitter', $submitter);

        return $next($request);
    }
}
