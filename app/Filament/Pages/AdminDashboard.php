<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

class AdminDashboard extends Dashboard
{
    // ← slug は付けない（デフォルトで /admin）
    protected static ?string $navigationLabel = 'ダッシュボード';
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $title           = 'ダッシュボード';
}
