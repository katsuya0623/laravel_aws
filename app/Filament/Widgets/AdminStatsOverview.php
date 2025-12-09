<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Job;
use App\Models\EndUser;
// ★ 応募モデルは実際のクラス名に合わせて修正してね
use App\Models\JobApplication;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class AdminStatsOverview extends StatsOverviewWidget
{
    // 自動更新（不要なら削ってOK）
    protected static ?string $pollingInterval = '30s';

    protected function getCards(): array
    {
        return [
            Card::make('登録企業数', Company::count()),

            Card::make(
                '公開求人数',
                // ★ 公開フラグのカラムに合わせて修正してね
                Job::where('is_published', true)->count()
            ),

            Card::make('登録ユーザー数', EndUser::count()),

            Card::make('応募総数', JobApplication::count()),
        ];
    }
}
