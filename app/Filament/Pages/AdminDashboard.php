<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;
use App\Filament\Widgets\AdminQuickLinks;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\PopularJobsTable;

class AdminDashboard extends Dashboard
{
    public static function getSlug(): string
    {
        return 'dashboard';
    }

    protected static ?string $navigationLabel = 'ダッシュボード';
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $title           = 'ダッシュボード';

    public function getWidgets(): array
    {
        return [
            AdminStatsOverview::class,
            PopularJobsTable::class,
            AdminQuickLinks::class,
        ];
    }

    // ★ ここだけ public に変更
    public function getColumns(): int|array
    {
        return [
            'sm' => 1,
            'lg' => 2,
        ];
    }
}
