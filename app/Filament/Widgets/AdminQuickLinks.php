<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AdminQuickLinks extends Widget
{
    // このビューを表示
    protected static string $view = 'filament.widgets.admin-quick-links';

    // ダッシュボードの上部に出したい場合は小さく
    protected static ?int $sort = -100;
}
