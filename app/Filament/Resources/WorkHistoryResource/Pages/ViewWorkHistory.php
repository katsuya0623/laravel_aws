<?php

namespace App\Filament\Resources\WorkHistoryResource\Pages;

use App\Filament\Resources\WorkHistoryResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewWorkHistory extends ViewRecord
{
    protected static string $resource = WorkHistoryResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('職歴詳細')->schema([
                TextEntry::make('user.name')->label('ユーザー'),
                TextEntry::make('company_name')->label('会社名'),
                TextEntry::make('position')->label('役職/職種')->placeholder('-'),
                TextEntry::make('start_date')->label('開始')->date(),
                TextEntry::make('end_date')->label('終了')->date()->placeholder('在職中'),
                TextEntry::make('is_current')
                    ->label('現在')
                    ->formatStateUsing(fn($s) => $s ? '在職中' : '退職'),
                TextEntry::make('description')->label('詳細')->columnSpanFull()->placeholder('-'),
                TextEntry::make('created_at')->label('登録日')->dateTime()->hint('自動記録'),
                TextEntry::make('updated_at')->label('更新日')->dateTime()->hint('自動記録'),
            ])->columns(2),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return []; // 編集/削除などは出さない
    }
}
