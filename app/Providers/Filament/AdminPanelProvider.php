<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

// ★ 追加
use Filament\Pages\Dashboard as FilamentDashboard;
use App\Filament\Widgets\LegacyAdminDashboard;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('admin')
            ->brandName('nibi Admin')
            ->colors(['primary' => Color::Indigo])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            // ★ 追加：Filament に Dashboard ページを明示登録（/admin と ルート名を生やす）
            ->pages([
                FilamentDashboard::class,   // => route('filament.admin.pages.dashboard') が定義される
            ])

            // ★ 追加：レガシーダッシュボードウィジェットを表示
            ->widgets([
                LegacyAdminDashboard::class,
            ])

            // ナビは /admin（= Filament ダッシュボード）か /admin/dashboard のどちらでもアクティブに
            ->navigationItems([
                NavigationItem::make('ダッシュボードへ')
                    ->icon('heroicon-o-home')
                    ->sort(-100)
                    ->url('/admin') // 押すと /admin を開く（安全）
                    ->isActiveWhen(fn () => request()->is('admin') || request()->is('admin/dashboard')),
            ])
            ->navigationGroups([
                NavigationGroup::make('Company'),
                NavigationGroup::make('Post'),
                NavigationGroup::make('Management'),
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
