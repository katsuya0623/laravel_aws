<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Pages\AdminDashboard;   // ★ 追加

class AdminHome extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.empty';

    public static function getSlug(): string
    {
        return '';
    }

    public function mount()
    {
        if (auth('admin')->check()) {
            // ★ ここを Filament の $this->redirect() に変更
            $this->redirect(AdminDashboard::getUrl(panel: 'admin'));
            return;
        }
        $this->redirect('/admin/login');
    }
}
