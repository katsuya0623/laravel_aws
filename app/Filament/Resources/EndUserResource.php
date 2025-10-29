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

// Infolists
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

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
        return parent::getEloquentQuery()
            ->where('role', 'enduser')
            ->with('profile');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('氏名')->required()->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('Email')->email()->required()
                ->unique(table: User::class, ignorable: fn (?User $record) => $record),

            Forms\Components\TextInput::make('password')
                ->label('パスワード')->password()->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->minLength(8)
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('氏名')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('詳細'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    /** ViewRecord 用 Infolist */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            // 基本情報
            Section::make('基本情報')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('name')->label('氏名'),
                    TextEntry::make('email')->label('Email'),
                    TextEntry::make('email_verified_at')->dateTime('Y-m-d H:i:s')->label('メール確認')->placeholder('—'),
                    TextEntry::make('created_at')->dateTime('Y-m-d H:i:s')->label('登録日時'),
                ]),
            ])->collapsible(),

            // プロフィール
            Section::make('プロフィール')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('profile.display_name')->label('表示名')->placeholder('—')
                        ->state(fn (User $record) => optional($record->loadMissing('profile')->profile)->display_name),

                    TextEntry::make('profile.gender')->label('性別')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->gender),

                    TextEntry::make('profile.birthday')->label('生年月日')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->birthday),

                    TextEntry::make('profile.phone')->label('電話番号')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->phone),

                    TextEntry::make('profile.last_name')->label('姓')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->last_name),
                    TextEntry::make('profile.first_name')->label('名')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->first_name),

                    TextEntry::make('profile.last_name_kana')->label('セイ')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->last_name_kana),
                    TextEntry::make('profile.first_name_kana')->label('メイ')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->first_name_kana),

                    TextEntry::make('profile.bio')->label('自己紹介 / PR')->columnSpanFull()->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->bio),
                ]),
            ])->collapsed(),

            // 住所
            Section::make('住所')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('profile.postal_code')->label('郵便番号')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->postal_code),
                    TextEntry::make('profile.prefecture')->label('都道府県')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->prefecture),

                    TextEntry::make('profile.city')->label('市区町村')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->city),
                    TextEntry::make('profile.address1')->label('番地')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->address1),

                    TextEntry::make('profile.address2')->label('建物名・部屋番号')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->address2)->columnSpanFull(),

                    TextEntry::make('profile.nearest_station')->label('最寄り駅')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->nearest_station),
                    TextEntry::make('profile.location')->label('現在地（自由入力）')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->location),
                ]),
            ])->collapsed(),

            // リンク / SNS
            Section::make('リンク / SNS')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('profile.portfolio_url')->label('ポートフォリオURL')
                        ->state(fn (User $record) => optional($record->profile)->portfolio_url)
                        ->url(fn ($state) => blank($state) ? null : (preg_match('~^https?://~i', $state) ? $state : 'https://' . $state))
                        ->openUrlInNewTab()->placeholder('—'),

                    TextEntry::make('profile.website_url')->label('Webサイト')
                        ->state(fn (User $record) => optional($record->profile)->website_url)
                        ->url(fn ($state) => blank($state) ? null : (preg_match('~^https?://~i', $state) ? $state : 'https://' . $state))
                        ->openUrlInNewTab()->placeholder('—'),

                    TextEntry::make('profile.x_url')->label('X (URL)')
                        ->state(fn (User $record) => optional($record->profile)->x_url)
                        ->url(fn ($state) => blank($state) ? null : (preg_match('~^https?://~i', $state) ? $state : 'https://' . $state))
                        ->openUrlInNewTab()->placeholder('—'),

                    TextEntry::make('profile.instagram_url')->label('Instagram (URL)')
                        ->state(fn (User $record) => optional($record->profile)->instagram_url)
                        ->url(fn ($state) => blank($state) ? null : (preg_match('~^https?://~i', $state) ? $state : 'https://' . $state))
                        ->openUrlInNewTab()->placeholder('—'),

                    TextEntry::make('profile.sns_x')->label('X（ハンドル）')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->sns_x),

                    TextEntry::make('profile.sns_instagram')->label('Instagram（ハンドル）')->placeholder('—')
                        ->state(fn (User $record) => optional($record->profile)->sns_instagram),
                ]),
            ])->collapsed(),

// 学歴
Section::make('学歴')->schema([
    RepeatableEntry::make('profile.educations')
        ->label('学歴')
        ->state(fn (User $record) => $record->loadMissing('profile')->profile?->educations ?? [])
        ->schema([
            Grid::make(4)->schema([
                TextEntry::make('school')->label('学校名')->placeholder('—'),
                TextEntry::make('faculty')->label('学部')->placeholder('—'),
                TextEntry::make('department')->label('学科')->placeholder('—'),
                TextEntry::make('status')->label('在籍状況')->placeholder('—'),
                TextEntry::make('period_from')->label('入学')->placeholder('—'),
                TextEntry::make('period_to')->label('卒業')->placeholder('—'),
            ]),
        ])
        ->columns(1),
])->collapsible(),


// 職歴
Section::make('職歴')->schema([
    RepeatableEntry::make('profile.work_histories')
        ->label('職歴')
        ->state(fn (User $record) => $record->loadMissing('profile')->profile?->work_histories ?? [])
        ->schema([
            Grid::make(4)->schema([
                TextEntry::make('company')->label('会社名')->placeholder('—'),
                TextEntry::make('dept')->label('部署')->placeholder('—'),
                TextEntry::make('position')->label('役職')->placeholder('—'),
                TextEntry::make('employment_type')->label('雇用形態')->placeholder('—'),
                TextEntry::make('from')->label('開始')->placeholder('—'),
                TextEntry::make('to')->label('終了')->placeholder('—'),
                TextEntry::make('tasks')->label('業務内容')->columnSpanFull()->placeholder('—'),
                TextEntry::make('achievements')->label('実績')->columnSpanFull()->placeholder('—'),
            ]),
        ])
        ->columns(1),
])->collapsible(),


            // 希望条件
            Section::make('希望条件')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('desired.positions')->label('希望職種')->placeholder('—')
                        ->state(function (User $record) {
                            $d = self::toAssoc(optional($record->loadMissing('profile')->profile)->desired);
                            return implode(' / ', array_map('strval', (array) data_get($d, 'positions', [])));
                        }),

                    TextEntry::make('desired.employment_types')->label('希望雇用形態')->placeholder('—')
                        ->state(function (User $record) {
                            $d = self::toAssoc(optional($record->profile)->desired);
                            return implode(' / ', array_map('strval', (array) data_get($d, 'employment_types', [])));
                        }),

                    TextEntry::make('desired.locations')->label('希望勤務地')->placeholder('—')->columnSpanFull()
                        ->state(function (User $record) {
                            $d = self::toAssoc(optional($record->profile)->desired);
                            return implode(' / ', array_map('strval', (array) data_get($d, 'locations', [])));
                        }),

                    TextEntry::make('desired.first_choice')->label('第一希望')->placeholder('—')->columnSpanFull()
                        ->state(function (User $record) {
                            $d = self::toAssoc(optional($record->profile)->desired);
                            $p = data_get($d, 'first_choice.position');
                            $l = data_get($d, 'first_choice.location');
                            return trim(($p ?: '—') . ' / ' . ($l ?: '—'));
                        }),

                    TextEntry::make('desired.second_choice')->label('第二希望')->placeholder('—')->columnSpanFull()
                        ->state(function (User $record) {
                            $d = self::toAssoc(optional($record->profile)->desired);
                            $p = data_get($d, 'second_choice.position');
                            $l = data_get($d, 'second_choice.location');
                            return trim(($p ?: '—') . ' / ' . ($l ?: '—'));
                        }),

                    TextEntry::make('desired.hope_timing')->label('希望時期')->placeholder('—')
                        ->state(fn (User $record) => data_get(self::toAssoc(optional($record->profile)->desired), 'hope_timing')),
                    TextEntry::make('desired.available_from')->label('稼働開始')->placeholder('—')
                        ->state(fn (User $record) => data_get(self::toAssoc(optional($record->profile)->desired), 'available_from')),
                    TextEntry::make('desired.salary_min')->label('希望年収（最小）')->placeholder('—')
                        ->state(fn (User $record) => data_get(self::toAssoc(optional($record->profile)->desired), 'salary_min')),
                    TextEntry::make('desired.remarks')->label('備考')->placeholder('—')->columnSpanFull()
                        ->state(fn (User $record) => data_get(self::toAssoc(optional($record->profile)->desired), 'remarks')),
                ]),
            ])->collapsed(),

            // ── デバッグ（中身の確認用） ─────────────────────────────
            Section::make('デバッグ')
                ->schema([
                    TextEntry::make('debug.educations_count')
                        ->label('educations 件数')
                        ->state(fn (User $record) => is_array($record->loadMissing('profile')->profile?->educations ?? null)
                            ? count($record->profile->educations)
                            : (filled($record->profile?->educations) ? '（文字列）' : 0)
                        ),

                    TextEntry::make('debug.educations_raw')
                        ->label('educations（生データ）')
                        ->state(fn (User $record) => json_encode($record->loadMissing('profile')->profile?->educations, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))
                        ->columnSpanFull()
                        ->copyable(),

                    TextEntry::make('debug.work_histories_count')
                        ->label('work_histories 件数')
                        ->state(fn (User $record) => is_array($record->loadMissing('profile')->profile?->work_histories ?? null)
                            ? count($record->profile->work_histories)
                            : (filled($record->profile?->work_histories) ? '（文字列）' : 0)
                        ),

                    TextEntry::make('debug.work_histories_raw')
                        ->label('work_histories（生データ）')
                        ->state(fn (User $record) => json_encode($record->loadMissing('profile')->profile?->work_histories, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))
                        ->columnSpanFull()
                        ->copyable(),
                ])
                ->collapsible(),
        ]);
    }

    /** JSON文字列/配列/NULLを常に配列へ */
    private static function toArray(mixed $raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /** JSON文字列/配列/NULLを常に連想配列へ */
    private static function toAssoc(mixed $raw): array
    {
        $arr = self::toArray($raw);
        return is_array($arr) ? $arr : [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEndUsers::route('/'),
            'view'  => Pages\ViewEndUser::route('/{record}'),
        ];
    }
}
