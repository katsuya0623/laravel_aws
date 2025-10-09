<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Models\Application;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationGroup = 'Management';
    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = '応募一覧';
    protected static ?string $modelLabel      = '応募';
    protected static ?string $pluralModelLabel= '応募';

    protected static function has(string $col): bool
    {
        return Schema::hasColumn((new Application)->getTable(), $col);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('氏名')->required()->visible(fn()=>self::has('name')),
            Forms\Components\TextInput::make('email')->label('メール')->email()->visible(fn()=>self::has('email')),
            Forms\Components\TextInput::make('phone')->label('電話')->visible(fn()=>self::has('phone')),
            Forms\Components\Select::make('job_id')->label('求人')
                ->relationship('job', 'title')->searchable()->preload()
                ->visible(fn()=>self::has('job_id')),
            Forms\Components\FileUpload::make('resume_path')->label('履歴書')
                ->directory('resumes')->downloadable()->visible(fn()=>self::has('resume_path')),
            Forms\Components\Textarea::make('note')->label('メモ')->rows(5)
                ->visible(fn()=>self::has('note')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('応募日時')->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('job.title')->label('応募求人')->toggleable()
                    ->visible(fn()=>self::has('job_id')),
                Tables\Columns\TextColumn::make('name')->label('氏名')->searchable()->visible(fn()=>self::has('name')),
                Tables\Columns\TextColumn::make('email')->label('メール')->toggleable()->visible(fn()=>self::has('email')),
                Tables\Columns\TextColumn::make('phone')->label('電話')->toggleable()->visible(fn()=>self::has('phone')),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()->label('詳細'),
                Tables\Actions\EditAction::make()->label('編集'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('削除'),
            ])
            ->defaultSort('id','desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit'   => Pages\EditApplication::route('/{record}/edit'),
            'view'   => Pages\ViewApplication::route('/{record}'),
        ];
    }
}

namespace App\Filament\Resources\ApplicationResource\Pages;

use App\Filament\Resources\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;
    protected function getHeaderActions(): array
    {
        return [ Actions\CreateAction::make()->label('手動追加') ];
    }
}

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;
    protected function getCreatedNotificationTitle(): ?string { return '応募を追加しました'; }
}

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;
    protected function getHeaderActions(): array
    {
        return [ Actions\DeleteAction::make()->label('削除') ];
    }
    protected function getSavedNotificationTitle(): ?string { return '保存しました'; }
}

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;
}
