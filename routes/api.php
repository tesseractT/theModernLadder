<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

Route::prefix(config('api.version'))->group(function (): void {
    foreach (Arr::pluck(config('modules.modules', []), 'api_routes') as $routeFile) {
        if (is_string($routeFile) && file_exists($routeFile)) {
            require $routeFile;
        }
    }
});
