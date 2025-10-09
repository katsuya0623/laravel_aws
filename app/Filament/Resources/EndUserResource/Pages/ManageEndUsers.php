<?php

namespace App\Filament\Resources\EndUserResource\Pages;

use App\Filament\Resources\EndUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEndUsers extends ManageRecords
{
    protected static string $resource = EndUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('ダッシュボードへ')
                ->icon('heroicon-o-home')
                ->url('/admin/dashboard') // ← ルート指定からパス指定へ修正
                ->color('gray'),
            Actions\CreateAction::make()->label('作成'),
        ];
    }
}
