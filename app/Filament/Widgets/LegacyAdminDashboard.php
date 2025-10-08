<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class LegacyAdminDashboard extends Widget
{
    protected static string $view = 'filament.widgets.legacy-admin-dashboard';

    protected static ?int $sort = 10;

    public static function canView(): bool
    {
        return auth('admin')->check();
    }
}
