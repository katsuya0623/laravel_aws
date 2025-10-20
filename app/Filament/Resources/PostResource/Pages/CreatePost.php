<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
    protected static ?string $title = '記事を作成';

    /**
     * 作成前に必ず user_id を埋める
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // adminガード優先 → webガード → null
        $adminId = Auth::guard('admin')->id();
        $webId   = Auth::id();

        $data['user_id'] = $data['user_id']
            ?? $adminId
            ?? $webId
            ?? 1; // 最後の保険（既知の管理ユーザーIDに合わせて必要なら変更）

        return $data;
    }
}
