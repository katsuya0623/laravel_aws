<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class ProfileRelationManager extends RelationManager
{
    // Company モデルの `profile()` リレーション名
    protected static string $relationship = 'profile';

    protected static ?string $title = '企業プロフィール';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // 事業内容 / 紹介
                Forms\Components\Textarea::make('description')
                    ->label('事業内容 / 紹介')
                    ->rows(4)
                    ->maxLength(2000)
                    ->columnSpanFull(),

                // ロゴ画像
                Forms\Components\FileUpload::make('logo_path')
                    ->label('ロゴ画像（最大10MB / SVG, PNG, JPG, WebP）')
                    ->image()
                    ->disk('public')
                    ->directory('company_logos')
                    ->maxSize(10240)
                    ->imagePreviewHeight('120')
                    ->openable()
                    ->downloadable()
                    ->nullable(),

                // 連絡先
                Forms\Components\TextInput::make('website_url')
                    ->label('Webサイト')
                    ->url()
                    ->maxLength(255)
                    ->placeholder('https://example.com'),

                Forms\Components\TextInput::make('email')
                    ->label('代表メール')
                    ->email()
                    ->maxLength(255)
                    ->placeholder('info@example.com'),

                Forms\Components\TextInput::make('tel')
                    ->label('電話番号')
                    ->maxLength(20)
                    ->placeholder('03-1234-5678 / +81-3-1234-5678'),

                // 住所
                Forms\Components\TextInput::make('postal_code')
                    ->label('郵便番号')
                    ->maxLength(8)
                    ->placeholder('123-4567'),

                Forms\Components\TextInput::make('prefecture')
                    ->label('都道府県')
                    ->maxLength(255),

                Forms\Components\TextInput::make('city')
                    ->label('市区町村')
                    ->maxLength(255),

                Forms\Components\TextInput::make('address1')
                    ->label('番地・建物')
                    ->maxLength(255),

                Forms\Components\TextInput::make('address2')
                    ->label('部屋番号など')
                    ->maxLength(255),

                // 詳細情報
                Forms\Components\TextInput::make('industry')
                    ->label('業種')
                    ->maxLength(255),

                Forms\Components\TextInput::make('employees')
                    ->label('従業員数')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(1000000),

                Forms\Components\DatePicker::make('founded_on')
                    ->label('設立日')
                    ->native(false)
                    ->displayFormat('Y-m-d'),
            ])
            ->columns(3);
    }

    public function canCreate(): bool
    {
        return false;
    }

    public function canDelete($record): bool
    {
        return false;
    }

    public function canDeleteAny(): bool
    {
        return false;
    }
}
