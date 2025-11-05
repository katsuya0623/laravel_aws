<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class AdminDashboard extends BaseDashboard
{
    /** ナビゲーション（左メニュー）表示名 */
    protected static ?string $navigationLabel = 'ダッシュボード';

    /** アイコン */
    protected static ?string $navigationIcon = 'heroicon-o-home';

    /** ページタイトル */
    protected static ?string $title = 'ダッシュボード';

    /** 
     * URLスラッグを指定（デフォルトは '' → /admin）
     * これを 'dashboard' にすることで /admin/dashboard に変更可能
     */
    public static function getSlug(): string
    {
        return 'dashboard';
    }

    /**
     * ナビゲーションでの順序（任意）
     */
    protected static ?int $navigationSort = 1;

    /**
     * ナビゲーショングループ（任意：ダッシュボードを最上位に）
     */
    protected static ?string $navigationGroup = null;

    /**
     * ページの説明（任意：ツールチップなどに表示）
     */
    protected static ?string $navigationLabelDescription = '管理者用ダッシュボード';
}
