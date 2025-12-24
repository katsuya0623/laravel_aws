<?php

namespace App\Filament\Resources\EndUserResource\Pages;

use App\Filament\Resources\EndUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEndUsers extends ManageRecords
{
    protected static string $resource = EndUserResource::class;

    public function getTitle(): string
    {
        return '求職者一覧';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('作成'),
        ];
    }
}
