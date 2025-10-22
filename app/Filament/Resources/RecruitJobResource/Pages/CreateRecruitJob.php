<?php

namespace App\Filament\Resources\RecruitJobResource\Pages;

use App\Filament\Resources\RecruitJobResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateRecruitJob extends CreateRecord
{
    protected static string $resource = RecruitJobResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach ($data as $k => $v) {
            if ($v === '') $data[$k] = null;
        }
        if (array_key_exists('slug', $data) && empty($data['slug'] ?? null) && !empty($data['title'] ?? null)) {
            $data['slug'] = Str::slug(($data['title'] ?? '') . '-' . Str::random(6));
        }
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '求人を作成しました';
    }
}
