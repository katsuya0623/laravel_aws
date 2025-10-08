<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EndUserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EndUserResource extends Resource
{
    protected static ?string $model = User::class;

    // /admin/users に固定
    protected static ?string $slug = 'users';

    protected static ?string $navigationGroup = 'Management';
    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'エンドユーザー';

    /** ← ここを追加：ページタイトル用ラベル */
    protected static ?string $modelLabel = 'EndUser';
    protected static ?string $pluralModelLabel = 'EndUsers';

    /** エンドユーザーのみ */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'enduser');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('氏名')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')->label('Email')->email()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')->label('パスワード')->password()->revealable()
                ->dehydrated(fn ($s) => filled($s))
                ->dehydrateStateUsing(fn ($s) => bcrypt($s))
                ->minLength(8),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEndUsers::route('/'),
        ];
    }
}
