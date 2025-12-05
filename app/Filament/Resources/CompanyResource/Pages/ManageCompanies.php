<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ManageCompanies extends ListRecords
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
}
