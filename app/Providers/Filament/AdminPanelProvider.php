<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

use App\Filament\Resources\PostResource;
use App\Filament\Widgets\AdminQuickLinks;

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

            // ★ Filament パネルのベース URL を /admin/dashboard に変更
            ->path('admin/dashboard')

            ->authGuard('admin')
            ->brandName('nibi Admin')
            ->colors(['primary' => Color::Indigo])

            // 相対テーマは一旦停止
            // ->theme('themes/admin/theme.css')

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            ->resources([
                PostResource::class,
            ])

            // （ダッシュボードページは既定のまま。パス変更だけでOK）
            // ->pages([...]) は不要

            ->widgets([
                AdminQuickLinks::class,
            ])

            // ★ パネルのホームURLも /admin/dashboard に
            ->homeUrl('/admin/dashboard')

            ->navigationItems([
                NavigationItem::make('記事')
                    ->group('Post')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => PostResource::getUrl())
                    // ★ パス判定を /admin/dashboard/... に更新
                    ->isActiveWhen(fn () => request()->is('admin/dashboard/posts*'))
                    ->sort(1),

                NavigationItem::make('求人一覧')
                    ->group('Management')
                    ->icon('heroicon-o-briefcase')
                    // ここは Blade ルート（/admin/...）なので URL 生成は従来どおりでOK
                    ->url(fn () => route('admin.jobs.index'))
                    // こちらも従来どおり（Blade 側は /admin/... のまま）
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
