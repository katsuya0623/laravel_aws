<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Models\Admin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'メンバー管理';
    protected static ?string $pluralModelLabel = '管理者';
    protected static ?string $modelLabel = '管理者';
    protected static ?string $navigationGroup = 'システム';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('基本情報')->schema([
                TextInput::make('name')
                    ->label('名前')
                    ->required()
                    ->maxLength(191),

                TextInput::make('email')
                    ->label('メールアドレス')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
            ])->columns(2),

            Section::make('パスワード')->schema([
                TextInput::make('password')
                    ->label('パスワード')
                    ->password()
                    ->required(fn (string $context) => $context === 'create')
                    ->rule(Password::defaults())
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state)),

                TextInput::make('password_confirmation')
                    ->label('パスワード（確認）')
                    ->password()
                    ->same('password')
                    ->dehydrated(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->label('名前')->searchable()->sortable(),
                TextColumn::make('email')->label('メール')->searchable()->sortable(),
                TextColumn::make('created_at')->label('作成日')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()->label('編集'),
                Tables\Actions\DeleteAction::make()->label('削除'),
            ])
            // ★ ここにあった headerActions の CreateAction を削除しました（ボタン重複防止）
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('一括削除'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'edit'   => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
