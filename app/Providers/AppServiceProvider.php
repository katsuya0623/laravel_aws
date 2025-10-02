<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Support\RoleResolver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // @role('enduser') / @role('company,admin') などで使用可
        Blade::if('role', function (string $csvRoles) {
            $user = auth('admin')->user() ?? auth('web')->user();
            if (!$user) return false;

            $current = (string) (RoleResolver::resolve($user) ?? '');
            $allowed = array_filter(array_map('trim', explode(',', $csvRoles)));

            return in_array($current, $allowed, true);
        });
    }
}
