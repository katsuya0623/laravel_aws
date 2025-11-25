<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecruitJobResource\Pages;
use App\Models\Job;
use Closure;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class RecruitJobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static ?string $navigationGroup   = 'Management';
    protected static ?string $navigationIcon    = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel   = '求人一覧';
    protected static ?string $modelLabel        = '求人';
    protected static ?string $pluralModelLabel  = '求人';
    protected static ?string $slug              = 'recruit-jobs';

    /** 必須ラベル（赤い※ + 文言） */
    protected static function req(string $label): HtmlString
    {
        return new HtmlString('<span class="text-red-500">※</span> ' . e($label));
    }

    protected static function has(string $col): bool
    {
        return Schema::hasColumn((new Job())->getTable(), $col);
    }

    public static function form(Form $form): Form
    {
        $hasStatus      = self::has('status');
        $hasIsPublished = self::has('is_published');

        return $form->schema([
            // ───────── 基本情報 ─────────
            Section::make('基本情報')->schema([
                Grid::make(12)->schema([
                    // タイトル（必須）
                    TextInput::make('title')
                        ->label(self::req('タイトル'))
                        ->required()
                        ->minLength(2)
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => 'タイトルは必須です。',
                            'min'      => 'タイトルは:min文字以上で入力してください。',
                            'max'      => 'タイトルは:max文字以内で入力してください。',
                        ])
                        ->columnSpan(8),

                    // 企業（company_id があれば必須）
                    Select::make('company_id')
                        ->label(self::has('company_id') ? self::req('企業') : '企業')
                        ->relationship('company', 'name')
                        ->searchable()
                        ->preload()
                        ->required(fn () => self::has('company_id'))
                        ->validationMessages([
                            'required' => '企業は必須です。',
                            'exists'   => '選択した企業が存在しません。',
                        ])
                        ->visible(fn () => self::has('company_id'))
                        ->columnSpan(4),

                    // 企業（company_name のみの場合は必須）
                    TextInput::make('company_name')
                        ->label((! self::has('company_id') && self::has('company_name')) ? self::req('企業') : '企業')
                        ->maxLength(255)
                        ->required(fn () => ! self::has('company_id') && self::has('company_name'))
                        ->validationMessages([
                            'required' => '企業は必須です。',
                            'max'      => '企業名は:max文字以内で入力してください。',
                        ])
                        ->visible(fn () => ! self::has('company_id') && self::has('company_name'))
                        ->columnSpan(4),
                ]),

                // 概要（必須）
                Textarea::make('excerpt')
                    ->label(self::has('excerpt') ? self::req('概要') : '概要')
                    ->rows(3)
                    ->required(fn () => self::has('excerpt'))
                    ->maxLength(1000)
                    ->validationMessages([
                        'required' => '概要は必須です。',
                        'max'      => '概要は:max文字以内で入力してください。',
                    ])
                    ->columnSpanFull()
                    ->visible(fn () => self::has('excerpt')),

                // 本文（必須）
                Textarea::make('description')
                    ->label(self::req('本文（仕事内容・必須スキル・歓迎・福利厚生など）'))
                    ->rows(10)
                    ->required()
                    ->minLength(30)
                    ->validationMessages([
                        'required' => '本文は必須です。',
                        'min'      => '本文は:min文字以上で入力してください。',
                    ])
                    ->columnSpanFull(),

                // 画像（任意）
                FileUpload::make('image_path')
                    ->label('求人画像')
                    ->image()
                    ->directory('recruit_jobs')
                    ->disk('public')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->helperText('対応: jpg / jpeg / png / webp、2MB以内')
                    ->validationMessages([
                        'image' => '画像ファイルを選択してください。',
                        'mimes' => '画像形式は jpg / jpeg / png / webp に対応しています。',
                        'max'   => '画像サイズは :max KB 以内にしてください。',
                    ])
                    ->columnSpanFull()
                    ->visible(fn () => self::has('image_path')),
            ])->columns(1),

            // ───────── 詳細 ─────────
            Section::make('詳細')->schema([
                Grid::make(12)->schema([
                    // 勤務地：必須 + 文字列 + 長さ
                    TextInput::make('location')
                        ->label(self::has('location') ? self::req('勤務地') : '勤務地')
                        ->required(fn () => self::has('location'))
                        ->rule('string')
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => '勤務地は必須です。',
                            'string'   => '勤務地は文字列で入力してください。',
                            'max'      => '勤務地は:max文字以内で入力してください。',
                        ])
                        ->visible(fn () => self::has('location'))
                        ->columnSpan(6),

                    // 雇用形態（必須）
                    Select::make('employment_type')
                        ->label(self::has('employment_type') ? self::req('雇用形態') : '雇用形態')
                        ->options([
                            '正社員'     => '正社員',
                            '契約社員'   => '契約社員',
                            '業務委託'   => '業務委託',
                            'アルバイト' => 'アルバイト',
                            'インターン' => 'インターン',
                        ])
                        ->native(false)
                        ->required(fn () => self::has('employment_type'))
                        ->validationMessages([
                            'required' => '雇用形態は必須です。',
                            'in'       => '雇用形態の値が不正です。',
                        ])
                        ->visible(fn () => self::has('employment_type'))
                        ->columnSpan(3),

                    // 働き方：必須 + 許可値のみ
                    Select::make('work_style')
                        ->label(self::has('work_style') ? self::req('働き方') : '働き方')
                        ->options([
                            '出社'         => '出社',
                            'フルリモート' => 'フルリモート',
                            'ハイブリッド' => 'ハイブリッド',
                        ])
                        ->native(false)
                        ->required(fn () => self::has('work_style'))
                        ->rules([Rule::in(['出社', 'フルリモート', 'ハイブリッド'])])
                        ->validationMessages([
                            'required' => '働き方は必須です。',
                            'in'       => '働き方の値が不正です。',
                        ])
                        ->visible(fn () => self::has('work_style'))
                        ->columnSpan(3),
                ]),

                Grid::make(12)->schema([
                    TextInput::make('salary_from')
                        ->label('給与（下限）')
                        ->numeric()
                        ->minValue(0)
                        ->validationMessages([
                            'numeric'  => '給与（下限）は数値で入力してください。',
                            'min'      => '給与（下限）は0以上で入力してください。',
                        ])
                        ->visible(fn () => self::has('salary_from'))
                        ->columnSpan(4),

                    TextInput::make('salary_to')
                        ->label('給与（上限）')
                        ->numeric()
                        ->minValue(0)
                        ->rule('gte:salary_from') // 上限 ≥ 下限
                        ->validationMessages([
                            'numeric' => '給与（上限）は数値で入力してください。',
                            'min'     => '給与（上限）は0以上で入力してください。',
                            'gte'     => '給与（上限）は下限以上にしてください。',
                        ])
                        ->visible(fn () => self::has('salary_to'))
                        ->columnSpan(4),

                    // 単位（必須）
                    Select::make('salary_unit')
                        ->label(self::has('salary_unit') ? self::req('単位') : '単位')
                        ->options([
                            '年収' => '年収',
                            '月収' => '月収',
                            '時給' => '時給',
                        ])
                        ->native(false)
                        ->required(fn () => self::has('salary_unit'))
                        ->validationMessages([
                            'required' => '単位は必須です。',
                            'in'       => '単位の値が不正です。',
                        ])
                        ->visible(fn () => self::has('salary_unit'))
                        ->columnSpan(4),
                ]),

                // タグ：必須 + スペース区切り
                TextInput::make('tags')
                    ->label(self::has('tags') ? self::req('タグ（スペース区切り）') : 'タグ（スペース区切り）')
                    ->placeholder('例：完全週休2日 服装自由 フレックス')
                    ->required(fn () => self::has('tags'))
                    ->maxLength(255)
                    // ここからバリデーション
                    ->rule('string')
                    ->rule(function (string $attribute, $value, Closure $fail) {
                        $raw = trim((string) $value);

                        if ($raw === '') {
                            $fail('タグを入力してください。');
                            return;
                        }

                        $tags = preg_split('/\s+/u', $raw);

                        if (count($tags) > 10) {
                            $fail('タグは最大10個までです。');
                            return;
                        }

                        foreach ($tags as $t) {
                            if (mb_strlen($t) > 20) {
                                $fail("タグ「{$t}」が長すぎます（最大20文字）。");
                                return;
                            }
                            if (preg_match('/[,\|、。]/u', $t)) {
                                $fail("タグ「{$t}」に区切り記号は使えません。スペースで区切ってください。");
                                return;
                            }
                        }
                    })
                    // 保存時：空白を正規化して1本化
                    ->dehydrateStateUsing(function ($state) {
                        $tags = array_filter(preg_split('/\s+/u', trim((string) $state)));
                        return implode(' ', $tags);
                    })
                    ->visible(fn () => self::has('tags')),
            ])->columns(1),

            // ───────── 公開設定 ─────────
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
                        ->visible(fn () => ! $hasStatus && $hasIsPublished)
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
                    ->validationMessages([
                        'max'        => 'スラッグは:max文字以内で入力してください。',
                        'alpha_dash' => 'スラッグは英数字・ハイフン・アンダースコアのみ使用できます。',
                        'unique'     => 'このスラッグは既に使用されています。',
                    ])
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
                ImageColumn::make('image_path')
                    ->label('画像')
                    ->disk('public')
                    ->size(40)
                    ->square()
                    ->toggleable()
                    ->visible(fn () => self::has('image_path')),
                TextColumn::make('title')->label('タイトル')->searchable()->wrap(),
                TextColumn::make('company.name')->label('企業')->toggleable()->sortable()
                    ->visible(fn () => self::has('company_id')),
                TextColumn::make('company_name')->label('企業')->toggleable()->sortable()
                    ->visible(fn () => ! self::has('company_id') && self::has('company_name')),
                TextColumn::make('location')->label('勤務地')->toggleable()
                    ->visible(fn () => self::has('location')),
                TextColumn::make('salary_from')->label('下限')->numeric()->toggleable()
                    ->visible(fn () => self::has('salary_from')),
                TextColumn::make('salary_to')->label('上限')->numeric()->toggleable()
                    ->visible(fn () => self::has('salary_to')),
                TextColumn::make('salary_unit')->label('単位')->toggleable()
                    ->visible(fn () => self::has('salary_unit')),
                $hasStatus
                    ? Tables\Columns\BadgeColumn::make('status')->label('状態')
                        ->colors([
                            'secondary' => 'draft',
                            'success'   => 'published',
                            'danger'    => 'closed',
                        ])
                    : Tables\Columns\BadgeColumn::make('is_published')->label('公開')
                        ->colors([
                            'secondary' => 0,
                            'success'   => 1,
                        ])
                        ->formatStateUsing(fn ($s) => $s ? '公開' : '下書き'),
                TextColumn::make('published_at')->label('公開')->dateTime('Y-m-d H:i')->sortable()
                    ->visible(fn () => self::has('published_at')),
                TextColumn::make('updated_at')->label('更新')->since()->sortable()->alignment(Alignment::End),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('状態')
                    ->options([
                        'draft'     => '下書き',
                        'published' => '公開',
                        'closed'    => '募集停止',
                    ])
                    ->visible(fn () => $hasStatus),
                Tables\Filters\SelectFilter::make('is_published')->label('公開')
                    ->options([
                        1 => '公開',
                        0 => '下書き',
                    ])
                    ->visible(fn () => ! $hasStatus && $hasIsPublished),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('編集'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('削除'),
            ])
            ->defaultSort('id', 'desc');
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
