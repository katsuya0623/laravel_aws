<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecruitJobResource\Pages;
use App\Models\Job;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Schema;

class RecruitJobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static ?string $navigationGroup   = 'Management';
    protected static ?string $navigationIcon    = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel   = '求人一覧';
    protected static ?string $modelLabel        = '求人';
    protected static ?string $pluralModelLabel  = '求人';

    protected static ?string $slug = 'recruit-jobs';

    protected static function has(string $col): bool
    {
        return Schema::hasColumn((new Job())->getTable(), $col);
    }

    public static function form(Form $form): Form
    {
        $hasStatus      = self::has('status');
        $hasIsPublished = self::has('is_published');

        return $form->schema([
            Section::make('基本情報')->schema([
                Grid::make(12)->schema([
                    TextInput::make('title')
                        ->label('タイトル')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(8),

                    // 会社：company_id 優先、無ければ company_name
                    Select::make('company_id')
                        ->label('企業')
                        ->relationship('company', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn () => self::has('company_id'))
                        ->columnSpan(4),

                    TextInput::make('company_name')
                        ->label('企業')
                        ->maxLength(255)
                        ->visible(fn () => !self::has('company_id') && self::has('company_name'))
                        ->columnSpan(4),
                ]),

                Textarea::make('excerpt')
                    ->label('概要')
                    ->rows(3)
                    ->columnSpanFull()
                    ->visible(fn () => self::has('excerpt')),

                Textarea::make('description')
                    ->label('本文（仕事内容・必須スキル・歓迎・福利厚生など）')
                    ->rows(10)
                    ->required()
                    ->columnSpanFull(),
            ])->columns(1),

            Section::make('詳細')->schema([
                Grid::make(12)->schema([
                    TextInput::make('location')
                        ->label('勤務地')
                        ->maxLength(255)
                        ->visible(fn () => self::has('location'))
                        ->columnSpan(6),

                    Select::make('employment_type')
                        ->label('雇用形態')
                        ->options([
                            '正社員'     => '正社員',
                            '契約社員'   => '契約社員',
                            '業務委託'   => '業務委託',
                            'アルバイト' => 'アルバイト',
                            'インターン' => 'インターン',
                        ])
                        ->native(false)
                        ->visible(fn () => self::has('employment_type'))
                        ->columnSpan(3),

                    Select::make('work_style')
                        ->label('働き方')
                        ->options([
                            '出社'         => '出社',
                            'フルリモート' => 'フルリモート',
                            'ハイブリッド' => 'ハイブリッド',
                        ])
                        ->native(false)
                        ->visible(fn () => self::has('work_style'))
                        ->columnSpan(3),
                ]),

                Grid::make(12)->schema([
                    TextInput::make('salary_from')
                        ->label('給与（下限）')
                        ->numeric()
                        ->minValue(0)
                        ->visible(fn () => self::has('salary_from'))
                        ->columnSpan(4),

                    TextInput::make('salary_to')
                        ->label('給与（上限）')
                        ->numeric()
                        ->minValue(0)
                        ->visible(fn () => self::has('salary_to'))
                        ->columnSpan(4),

                    Select::make('salary_unit')
                        ->label('単位')
                        ->options([
                            '年収' => '年収',
                            '月収' => '月収', // フロントと統一
                            '時給' => '時給',
                        ])
                        ->native(false)
                        ->visible(fn () => self::has('salary_unit'))
                        ->columnSpan(4),
                ]),

                Grid::make(12)->schema([
                    TextInput::make('apply_url')
                        ->label('応募ページURL')
                        ->url()
                        ->maxLength(512)
                        ->visible(fn () => self::has('apply_url'))
                        ->columnSpan(6),

                    TextInput::make('external_url')
                        ->label('外部求人URL')
                        ->url()
                        ->maxLength(512)
                        ->visible(fn () => self::has('external_url'))
                        ->columnSpan(6),
                ]),

                TextInput::make('tags')
                    ->label('タグ（スペース区切り）')
                    ->maxLength(255)
                    ->visible(fn () => self::has('tags')),
            ])->columns(1),

            Section::make('公開設定')->schema([
                Grid::make(12)->schema([
                    ToggleButtons::make('status')
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
                        ->visible(fn () => $hasStatus)
                        ->columnSpan(6),

                    ToggleButtons::make('is_published')
                        ->label('公開状態')
                        ->options([0 => '下書き', 1 => '公開'])
                        ->colors([0 => 'gray', 1 => 'success'])
                        ->inline()
                        ->visible(fn () => !$hasStatus && $hasIsPublished)
                        ->columnSpan(6),

                    DateTimePicker::make('published_at')
                        ->label('公開日時')
                        ->seconds(false)
                        ->visible(fn () => self::has('published_at'))
                        ->columnSpan(6),
                ]),

                TextInput::make('slug')
                    ->label('スラッグ（未入力なら自動生成）')
                    ->maxLength(190)
                    ->helperText('保存時に空なら「title」から自動生成します。')
                    ->visible(fn () => self::has('slug')),
            ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        $hasStatus      = self::has('status');
        $hasIsPublished = self::has('is_published');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('タイトル')->searchable()->wrap(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('企業')->toggleable()->sortable()
                    ->visible(fn () => self::has('company_id')),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('企業')->toggleable()->sortable()
                    ->visible(fn () => !self::has('company_id') && self::has('company_name')),

                Tables\Columns\TextColumn::make('location')
                    ->label('勤務地')->toggleable()
                    ->visible(fn () => self::has('location')),

                Tables\Columns\TextColumn::make('salary_from')
                    ->label('下限')->numeric()->toggleable()
                    ->visible(fn () => self::has('salary_from')),

                Tables\Columns\TextColumn::make('salary_to')
                    ->label('上限')->numeric()->toggleable()
                    ->visible(fn () => self::has('salary_to')),

                Tables\Columns\TextColumn::make('salary_unit')
                    ->label('単位')->toggleable()
                    ->visible(fn () => self::has('salary_unit')),

                $hasStatus
                    ? Tables\Columns\BadgeColumn::make('status')->label('状態')
                        ->colors(['secondary' => 'draft', 'success' => 'published', 'danger' => 'closed'])
                    : Tables\Columns\BadgeColumn::make('is_published')->label('公開')
                        ->colors(['secondary' => 0, 'success' => 1])
                        ->formatStateUsing(fn ($s) => $s ? '公開' : '下書き'),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('公開')->dateTime('Y-m-d H:i')->sortable()
                    ->visible(fn () => self::has('published_at')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新')->since()->sortable()->alignment(Alignment::End),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('状態')
                    ->options(['draft'=>'下書き','published'=>'公開','closed'=>'募集停止'])
                    ->visible(fn () => $hasStatus),

                Tables\Filters\SelectFilter::make('is_published')->label('公開')
                    ->options([1 => '公開', 0 => '下書き'])
                    ->visible(fn () => !$hasStatus && $hasIsPublished),
            ])
            ->actions([
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
use Illuminate\Support\Str;

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

    /** 保存直前にデータを整形（空文字→null、slug自動生成） */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach ($data as $k => $v) {
            if ($v === '') $data[$k] = null;
        }
        if (array_key_exists('slug', $data) && empty($data['slug'] ?? null) && !empty($data['title'] ?? null)) {
            $data['slug'] = Str::slug(($data['title'] ?? '') . '-' . Str::random(6));
        }
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '求人を作成しました';
    }
}

class EditRecruitJob extends EditRecord
{
    protected static string $resource = RecruitJobResource::class;

    /** 更新直前にデータを整形（空文字→null、slug自動生成） */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach ($data as $k => $v) {
            if ($v === '') $data[$k] = null;
        }
        if (array_key_exists('slug', $data) && empty($data['slug'] ?? null) && !empty($data['title'] ?? null)) {
            $data['slug'] = Str::slug(($data['title'] ?? '') . '-' . Str::random(6));
        }
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [ Actions\DeleteAction::make()->label('削除') ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return '保存しました';
    }
}
