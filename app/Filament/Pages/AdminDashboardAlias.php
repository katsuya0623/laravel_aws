<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

class AdminDashboardAlias extends Dashboard
{
    // /admin/dashboard を提供
    public static function getSlug(): string
    {
        return 'dashboard';
    }

    protected static ?string $navigationLabel = 'ダッシュボード';
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $title           = 'ダッシュボード';
}
