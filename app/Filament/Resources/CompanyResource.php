<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn; // ★ 一覧でON/OFFするため

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    // 任意：メニュー表示
    // protected static ?string $navigationIcon = 'heroicon-o-building-office';
    // protected static ?string $navigationLabel = '企業一覧';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('企業名')
                ->required(),

            TextInput::make('slug')
                ->label('Slug')
                ->unique(ignoreRecord: true),

            TextInput::make('description')
                ->label('説明')
                ->columnSpanFull(),

            // 編集画面でも切り替え可能
            Toggle::make('is_sponsor')
                ->label('スポンサー企業')
                ->helperText('ONにすると記事作成でスポンサーとして選択可能'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('企業名')
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true),

                // ★ 一覧から直接 ON/OFF
                ToggleColumn::make('is_sponsor')
                    ->label('SP'),

                TextColumn::make('updated_at')
                    ->label('更新日')
                    ->date('Y-m-d'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        // Manage系（一覧・作成・編集が1画面）
        return [
            'index' => Pages\ManageCompanies::route('/'),
        ];
    }
}
