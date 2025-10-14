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

    // 更新後の通知
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

    // 右上（ヘッダー）のアクション
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('削除'),
        ];
    }

    // フォーム下部のアクション（保存 / 一覧へ戻る）
    protected function getFormActions(): array
    {
        return [
            // SaveAction の代替：フォームを submit させる汎用アクション
            Actions\Action::make('save')
                ->label('保存')
                ->submit('save'),

            // CancelAction の代替：一覧へ戻る汎用アクション
            Actions\Action::make('cancel')
                ->label('一覧へ戻る')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }
}
