<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get; // ★ 追加
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon   = 'heroicon-o-document-text';
    protected static ?string $navigationLabel  = '記事';
    protected static ?string $pluralModelLabel = '記事';
    protected static ?string $modelLabel       = '記事';
    protected static ?string $navigationGroup  = 'コンテンツ';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([
                // 左：本文など
                Section::make('記事情報')->schema([
                    TextInput::make('title')
                        ->label('タイトル')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if (blank($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('スラッグ')
                        // 小文字英数字とハイフンのみ
                        ->required()
                        ->maxLength(255)
                        ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        // posts.slug のユニーク、更新時は自身を除外
                        ->unique(ignoreRecord: true)
                        ->helperText('英小文字とハイフンのみ（例：my-post-20251017）'),

                    Textarea::make('excerpt')
                        ->label('要約')
                        ->rows(3),

                    Textarea::make('body')
                        ->label('本文')
                        ->rows(14)
                        ->autosize()
                        ->required(),
                ])->columnSpan(8),

                // 右：公開・画像・スポンサー
                Section::make('公開・設定')->schema([
                    DateTimePicker::make('published_at')
                        ->label('公開日時')
                        ->seconds(false)
                        // ステータスが「公開」のときだけ必須
                        ->required(fn (Get $get) => $get('status') === 'published'),

                    Select::make('status')
                        ->label('ステータス')
                        ->options([
                            'draft'     => '下書き',
                            'review'    => 'レビュー',
                            'published' => '公開',
                        ])
                        ->default('draft')
                        ->required(),

                    Toggle::make('is_featured')->label('特集表示'),

                    Select::make('sponsor_company_id')
                        ->label('スポンサー企業')
                        ->options(fn () => Company::query()->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->native(false),

                    FileUpload::make('thumbnail_path')
                        ->label('サムネイル')
                        ->image()
                        ->directory('posts/thumbnails')
                        ->disk('public')
                        ->visibility('public')
                        ->imagePreviewHeight('180')
                        ->openable()
                        ->downloadable()
                        // 新規作成時は必須 / 既存レコードで空なら必須
                        ->required(function (Get $get) {
                            /** @var ?Post $record */
                            $record = $get('record');
                            return $record === null || blank($record->thumbnail_path);
                        }),

                    TextInput::make('reading_time')->label('推定読了分')->numeric(),

                    Section::make('SEO')->schema([
                        TextInput::make('seo_title')->label('SEOタイトル')->maxLength(255),
                        TextInput::make('seo_description')->label('SEOディスクリプション')->maxLength(255),
                    ]),
                ])->columnSpan(4),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail_path')
                    ->label('サムネ')
                    ->disk('public')
                    ->height(48)
                    ->square(),

                TextColumn::make('title')->label('タイトル')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('slug')->label('スラッグ')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sponsorCompany.name')->label('スポンサー'),

                IconColumn::make('is_featured')->label('特集')->boolean(),

                IconColumn::make('published_at')
                    ->label('公開')
                    ->boolean()
                    ->state(fn ($r) => !blank($r->published_at))
                    ->trueIcon('heroicon-o-check')
                    ->falseIcon('heroicon-o-x-mark'),

                TextColumn::make('published_at')->label('公開日時')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('updated_at')->label('更新')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('pub_state')
                    ->label('公開状態')
                    ->options([
                        'published' => '公開',
                        'draft'     => '未公開',
                    ])
                    ->placeholder('すべて')
                    ->query(function (Builder $query, array $data): void {
                        $v = $data['value'] ?? null;
                        if ($v === 'published') {
                            $query->whereNotNull('published_at');
                        } elseif ($v === 'draft') {
                            $query->whereNull('published_at');
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('編集'),
                Tables\Actions\DeleteAction::make()->label('削除'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('一括削除'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit'   => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
