<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

use App\Filament\Pages\AdminDashboard; // ← 自作ダッシュボード Page
use App\Filament\Resources\PostResource; // ← 追加：PostResource を明示的に use

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

            // 自動検出
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'),     for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            // ← 追加：自動検出が拾えない場合に備えて明示登録
            ->resources([
                PostResource::class,
            ])

            // ダッシュボード（/admin 以下のホームはこの Page）
            ->pages([
                AdminDashboard::class,
            ])

            // ロゴ/ホームクリック時は必ず /admin へ
            ->homeUrl('/admin')

            // 追加メニュー
            ->navigationItems([
                // ← 追加：記事（PostResource）へのリンクを安全に
                NavigationItem::make('記事')
                    ->group('Post')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => PostResource::getUrl()) // ルート名直書きを回避
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

            // ミドルウェア
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
