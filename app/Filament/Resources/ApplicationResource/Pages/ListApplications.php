<?php

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 作成ボタンはいらないので削除して、
            // 代わりに CSV エクスポートボタンを出す
            Actions\Action::make('export')
                ->label('CSVエクスポート')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => route('admin.applications.export', request()->query())),
        ];
    }
}
