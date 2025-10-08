<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdmins extends ListRecords
{
    protected static string $resource = AdminResource::class;

    // 画面タイトル（パンくず・ヘッダー用）
    protected static ?string $title = '管理者一覧';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('管理者を追加'), // ヘッダー右上ボタンの文言
        ];
    }
}
