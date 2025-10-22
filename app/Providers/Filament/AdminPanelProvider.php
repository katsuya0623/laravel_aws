<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;

use App\Filament\Resources\PostResource;
use App\Filament\Widgets\AdminQuickLinks; // ★ 追加

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

            // 相対テーマは一旦停止（404対策）
            // ->theme('themes/admin/theme.css')

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            // 自作AdminDashboardを拾わせない
            // ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            ->resources([
                PostResource::class,
            ])

            // 純正ダッシュボードのみ明示
            ->pages([
                Pages\Dashboard::class,
            ])

            // ★ Admin専用のクイックリンクWidgetを表示
            ->widgets([
                AdminQuickLinks::class,
            ])

            ->homeUrl('/admin')

            ->navigationItems([
                NavigationItem::make('記事')
                    ->group('Post')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => PostResource::getUrl())
                    ->isActiveWhen(fn () => request()->is('admin/posts*'))
                    ->sort(1),

                NavigationItem::make('求人一覧')
                    ->group('Management')
                    ->icon('heroicon-o-briefcase')
                    ->url(fn () => route('admin.jobs.index'))
                    ->isActiveWhen(fn () => request()->is('admin/recruit_jobs*') || request()->is('admin/jobs*'))
                    ->sort(10),

                NavigationItem::make('応募一覧')
                    ->group('Management')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => route('admin.applications.index'))
                    ->isActiveWhen(fn () => request()->is('admin/applications*'))
                    ->sort(11),
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
