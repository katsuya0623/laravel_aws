<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Company;
use App\Models\Job;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class OverviewStats extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('ユーザー', User::count())->description('総登録数'),
            Card::make('企業', class_exists(Company::class) ? Company::count() : 0),
            Card::make('求人', class_exists(Job::class) ? Job::count() : 0),
        ];
    }
}
