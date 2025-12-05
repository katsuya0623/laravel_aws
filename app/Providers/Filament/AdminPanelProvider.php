<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

use App\Filament\Resources\PostResource;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\EndUserResource;   // ★ これ追加
use App\Filament\Widgets\AdminQuickLinks;
use App\Filament\Pages\AdminDashboard;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Route;

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
            // ページは明示登録でもOKだが、今回は確実に動かすため routes で生やす
            // ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            ->resources([
                PostResource::class,
                CompanyResource::class,
            ])

            ->pages([
                AdminDashboard::class, // ページ自体は登録
            ])

            // ★ ここで /admin/dashboard を“相対名”で登録（filament.admin. は自動付与）
            ->routes(function () {
                Route::get('/dashboard', AdminDashboard::class)
                    ->name('pages.dashboard'); // ← 重要：相対名にする
            })

            ->widgets([
                AdminQuickLinks::class,
            ])

            // ログイン後のホーム
            ->homeUrl(fn() => AdminDashboard::getUrl())

            ->navigationItems([
                // ▼ Post
                NavigationItem::make('記事')
                    ->group('Post')
                    ->icon('heroicon-o-document-text')
                    ->url(fn() => PostResource::getUrl('index'))
                    ->isActiveWhen(fn() => request()->is('admin/posts*'))
                    ->sort(1),

                // ▼ Management（ダッシュボードと同じ順） ---------------------

                // ① エンドユーザー
                NavigationItem::make('エンドユーザー')
                    ->group('Management')
                    ->icon('heroicon-o-user-group')
                    ->url(fn() => EndUserResource::getUrl('index'))
                    ->isActiveWhen(fn() => request()->is('admin/users*'))
                    ->sort(8),

                // ② 企業一覧
                NavigationItem::make('企業一覧')
                    ->group('Management')
                    ->icon('heroicon-o-building-office-2')
                    ->url(fn() => CompanyResource::getUrl('index'))
                    ->isActiveWhen(fn() => request()->is('admin/companies*'))
                    ->sort(9),

                // ③ 求人一覧
                NavigationItem::make('求人一覧')
                    ->group('Management')
                    ->icon('heroicon-o-briefcase')
                    ->url(fn() => route('admin.jobs.index'))
                    ->isActiveWhen(fn() => request()->is('admin/recruit_jobs*') || request()->is('admin/jobs*'))
                    ->sort(10),

                // ④ 応募一覧
                NavigationItem::make('応募一覧')
                    ->group('Management')
                    ->icon('heroicon-o-document-text')
                    ->url(fn() => route('admin.applications.index'))
                    ->isActiveWhen(fn() => request()->is('admin/applications*'))
                    ->sort(11),
            ])


            ->navigationGroups([
                NavigationGroup::make('Post'),
                NavigationGroup::make('Management'),
                NavigationGroup::make('System'),   // ★ 英語化して一番下に
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
