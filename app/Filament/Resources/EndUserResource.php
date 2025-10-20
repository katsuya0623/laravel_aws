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
use Illuminate\Support\Facades\Hash;

class EndUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';
    protected static ?string $navigationGroup = 'Management';
    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'エンドユーザー';
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
            // ===== 氏名 =====
            Forms\Components\TextInput::make('name')
                ->label('氏名')
                ->required() // 必須
                ->maxLength(255),

            // ===== メール =====
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required() // 必須
                ->maxLength(255)
                ->unique(
                    table: User::class,
                    ignorable: fn (?User $record) => $record // 編集時は自分を除外
                ),

            // ===== パスワード =====
            Forms\Components\TextInput::make('password')
                ->label('パスワード')
                ->password()
                ->revealable()
                ->required(fn (string $context) => $context === 'create') // 作成時のみ必須
                ->minLength(8)
                ->dehydrated(fn ($state) => filled($state)) // 入力あれば保存
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('氏名')->searchable(),
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
