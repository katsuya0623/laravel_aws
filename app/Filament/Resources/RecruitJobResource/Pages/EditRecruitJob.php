<?php

namespace App\Filament\Resources\RecruitJobResource\Pages;

use App\Filament\Resources\RecruitJobResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditRecruitJob extends EditRecord
{
    protected static string $resource = RecruitJobResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach ($data as $k => $v) {
            if ($v === '') $data[$k] = null;
        }
        if (array_key_exists('slug', $data) && empty($data['slug'] ?? null) && !empty($data['title'] ?? null)) {
            $data['slug'] = Str::slug(($data['title'] ?? '') . '-' . Str::random(6));
        }
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('削除')];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return '保存しました';
    }
}
