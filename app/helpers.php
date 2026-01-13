<?php

use Illuminate\Support\Facades\Route;

if (!function_exists('isActiveSection')) {
    function isActiveSection(array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Route::is($pattern)) {
                return true;
            }
        }
        return false;
    }
}
