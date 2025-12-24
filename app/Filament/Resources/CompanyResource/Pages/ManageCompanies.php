<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\ListRecords;

class ManageCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    public function getTitle(): string
    {
        return '企業一覧';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
