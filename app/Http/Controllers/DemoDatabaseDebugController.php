<?php

namespace App\Http\Controllers;

use App\Support\DemoDatabaseDebug;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DemoDatabaseDebugController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return response()
            ->view('debug.demo-database', [
                'debug' => DemoDatabaseDebug::snapshot($request),
            ])
            ->header('X-Demo-Database-Debug', DemoDatabaseDebug::headerValue($request));
    }
}
