<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    // 画面タイトル
    protected static ?string $title = '管理者の編集';

    // 更新後の通知（任意）
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('管理者を更新しました');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // 右上のアクション（削除/一覧へ）などのラベル
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('削除'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\SaveAction::make()->label('保存'),
            Actions\CancelAction::make()->label('一覧へ戻る'),
        ];
    }
}
