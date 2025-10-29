<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkHistoryResource\Pages;
use App\Models\WorkHistory;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate; // ★ 追加

class WorkHistoryResource extends Resource
{
    protected static ?string $model = WorkHistory::class;

    protected static ?string $navigationIcon   = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup  = 'ユーザー管理';
    protected static ?int    $navigationSort   = 60;
    protected static ?string $modelLabel       = '職歴';
    protected static ?string $pluralModelLabel = '職歴（閲覧のみ）';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with('user'); // N+1回避
                if (request()->filled('user_id')) {
                    $query->where('user_id', (int) request()->get('user_id'));
                }
            })
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('ユーザー')->toggleable()->searchable()->sortable(),
                TextColumn::make('company_name')->label('会社名')->toggleable()->searchable()->sortable(),
                TextColumn::make('position')->label('役職/職種')->toggleable()->searchable()->sortable(),
                TextColumn::make('start_date')->label('開始')->date()->sortable()->toggleable(),
                TextColumn::make('end_date')->label('終了')->date()->placeholder('在職中')->sortable()->toggleable(),
                TextColumn::make('is_current')
                    ->label('現在')
                    ->formatStateUsing(fn ($s) => $s ? '在職中' : '退職')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('created_at')->label('登録日')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_current')->label('在職中'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkHistories::route('/'),
            'view'  => Pages\ViewWorkHistory::route('/{record}'),
        ];
    }

    /** ★ Gate（Policy）に委譲して判定する */
    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', WorkHistory::class);
    }

    // 完全閲覧専用
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
}
