<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecruitJobResource\Pages;
use App\Models\Job;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;

class RecruitJobResource extends Resource
{
    /** 既存の Job モデルを利用 */
    protected static ?string $model = Job::class;

    /** 左ナビ表示 */
    protected static ?string $navigationGroup = 'Management';
    protected static ?string $navigationIcon  = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = '求人一覧';
    protected static ?string $modelLabel      = '求人';
    protected static ?string $pluralModelLabel= '求人';

    /** URL スラッグを固定（/admin/recruit-jobs） */
    protected static ?string $slug = 'recruit-jobs';

    /** 列存在チェックのユーティリティ */
    protected static function has(string $col): bool
    {
        return Schema::hasColumn((new Job())->getTable(), $col);
    }

    /** 作成/編集フォーム */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('基本情報')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('タイトル')->required()->maxLength(255),

                Forms\Components\Select::make('company_id')
                    ->label('企業')->relationship('company', 'name')
                    ->searchable()->preload()
                    ->visible(fn()=>self::has('company_id')),

                Forms\Components\Textarea::make('excerpt')
                    ->label('概要')->rows(3)->columnSpanFull()
                    ->visible(fn()=>self::has('excerpt')),

                Forms\Components\Textarea::make('description')
                    ->label('仕事内容')->rows(10)->columnSpanFull()
                    ->visible(fn()=>self::has('description')),
            ])->columns(2),

            Forms\Components\Section::make('公開設定')->schema([
                Forms\Components\ToggleButtons::make('status')
                    ->label('公開状態')
                    ->options([
                        'draft'     => '下書き',
                        'published' => '公開',
                        'closed'    => '募集停止',
                    ])
                    ->colors([
                        'draft'     => 'gray',
                        'published' => 'success',
                        'closed'    => 'danger',
                    ])
                    ->inline()
                    ->visible(fn()=>self::has('status')),

                Forms\Components\DateTimePicker::make('published_at')
                    ->label('公開日時')->seconds(false)
                    ->visible(fn()=>self::has('published_at')),
            ])->columns(2),
        ]);
    }

    /** 一覧テーブル */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('タイトル')->searchable()->wrap(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('企業')->toggleable()->sortable()
                    ->visible(fn()=>self::has('company_id')),

                Tables\Columns\BadgeColumn::make('status')->label('状態')
                    ->colors([
                        'secondary' => 'draft',
                        'success'   => 'published',
                        'danger'    => 'closed',
                    ])
                    ->visible(fn()=>self::has('status')),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('公開')->dateTime('Y-m-d H:i')->sortable()
                    ->visible(fn()=>self::has('published_at')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新')->since()->sortable()->alignment(Alignment::End),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('状態')
                    ->options([
                        'draft'     => '下書き',
                        'published' => '公開',
                        'closed'    => '募集停止',
                    ])->visible(fn()=>self::has('status')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('編集'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('削除'),
            ])
            ->defaultSort('id','desc');
    }

    /** ページ定義 */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRecruitJobs::route('/'),
            'create' => Pages\CreateRecruitJob::route('/create'),
            'edit'   => Pages\EditRecruitJob::route('/{record}/edit'),
        ];
    }
}

namespace App\Filament\Resources\RecruitJobResource\Pages;

use App\Filament\Resources\RecruitJobResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;

class ListRecruitJobs extends ListRecords
{
    protected static string $resource = RecruitJobResource::class;

    protected function getHeaderActions(): array
    {
        return [ Actions\CreateAction::make()->label('新規作成') ];
    }
}

class CreateRecruitJob extends CreateRecord
{
    protected static string $resource = RecruitJobResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return '求人を作成しました';
    }
}

class EditRecruitJob extends EditRecord
{
    protected static string $resource = RecruitJobResource::class;

    protected function getHeaderActions(): array
    {
        return [ Actions\DeleteAction::make()->label('削除') ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return '保存しました';
    }
}
