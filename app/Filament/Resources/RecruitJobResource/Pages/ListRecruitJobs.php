<?php

namespace App\Filament\Resources\RecruitJobResource\Pages;

use App\Filament\Resources\RecruitJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecruitJobs extends ListRecords
{
    protected static string $resource = RecruitJobResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('新規作成')];
    }
}
