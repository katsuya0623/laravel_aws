<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCompanies extends ManageRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('ダッシュボードへ')
                ->icon('heroicon-o-home')
                ->url('/admin/dashboard'),
        ];
    }

    // 行アクション（編集/削除）は Resource 側の table() に任せるのでここでは追加しない
}
