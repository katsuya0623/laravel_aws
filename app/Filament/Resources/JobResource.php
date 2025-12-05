<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobResource\Pages;
use App\Models\Job;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Schema;

class JobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static ?string $navigationGroup = 'Management';
    protected static ?string $navigationIcon  = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = '求人一覧';
    protected static ?string $modelLabel      = '求人';
    protected static ?string $pluralModelLabel= '求人';
    protected static ?int $navigationSort = 10;


    /** 安全にカラム有無を判定 */
    protected static function has(string $col): bool
    {
        return Schema::hasColumn((new Job)->getTable(), $col);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('基本情報')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('タイトル')->required()->maxLength(255),

                Forms\Components\Select::make('company_id')
                    ->label('企業')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => self::has('company_id')),

                Forms\Components\Textarea::make('description')
                    ->label('仕事内容')
                    ->rows(6)
                    ->visible(fn () => self::has('description')),

                Forms\Components\ToggleButtons::make('status')
                    ->label('公開状態')
                    ->options([
                        'draft'     => '下書き',
                        'published' => '公開',
                        'closed'    => '募集停止',
                    ])->colors([
                        'draft'     => 'gray',
                        'published' => 'success',
                        'closed'    => 'danger',
                    ])->inline()
                    ->visible(fn () => self::has('status')),
            ])->columns(2),

            Forms\Components\Section::make('公開設定')->schema([
                Forms\Components\DateTimePicker::make('published_at')
                    ->label('公開日時')
                    ->seconds(false)
                    ->visible(fn () => self::has('published_at')),
            ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('タイトル')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('企業')->sortable()->toggleable()
                    ->visible(fn () => self::has('company_id')),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('状態')
                    ->colors([
                        'secondary' => 'draft',
                        'success'   => 'published',
                        'danger'    => 'closed',
                    ])
                    ->visible(fn () => self::has('status')),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('公開')->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->visible(fn () => self::has('published_at')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => '下書き',
                        'published' => '公開',
                        'closed' => '募集停止',
                    ])
                    ->visible(fn () => self::has('status')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('編集'),
                Tables\Actions\ViewAction::make()->label('表示')->visible(false), // 使わなければ非表示
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('削除'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJobs::route('/'),
            'create' => Pages\CreateJob::route('/create'),
            'edit'   => Pages\EditJob::route('/{record}/edit'),
        ];
    }
}

namespace App\Filament\Resources\JobResource\Pages;

use App\Filament\Resources\JobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;

class ListJobs extends ListRecords
{
    protected static string $resource = JobResource::class;

    protected function getHeaderActions(): array
    {
        return [ Actions\CreateAction::make()->label('新規作成') ];
    }
}

class CreateJob extends CreateRecord
{
    protected static string $resource = JobResource::class;
    protected function getCreatedNotificationTitle(): ?string { return '求人を作成しました'; }
}

class EditJob extends EditRecord
{
    protected static string $resource = JobResource::class;

    protected function getHeaderActions(): array
    {
        return [ Actions\DeleteAction::make()->label('削除') ];
    }

    protected function getSavedNotificationTitle(): ?string { return '保存しました'; }
}
