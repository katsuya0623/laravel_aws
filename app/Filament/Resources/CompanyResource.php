<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Fieldset;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    /** 共通フォーム（UI + サーバーバリデーション） */
    protected static function formSchema(): array
    {
        return [
            Fieldset::make('会社情報')->schema([
                TextInput::make('name')
                    ->label('企業名')
                    ->required()
                    // → ブラウザで自動カットさせないため maxLength は外す
                    ->rule('max:30')               // 保存時に必ず弾く（サーバ側）
                    ->validationMessages([
                        'max' => '企業名は30文字以内で入力してください。',
                    ])
                    ->validationAttribute('企業名')
                    ->live()                        // 入力中に即エラー表示したい場合（任意）
                    ->helperText('30文字まで'),

                TextInput::make('slug')
                    ->label(new HtmlString('Slug <span class="text-red-500">*</span>'))
                    ->required()
                    ->rule('alpha_dash')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('description')
                    ->label('説明')
                    ->columnSpanFull(),
            ]),

            Fieldset::make('ログインアカウント（任意）')
                ->columns(2)
                ->schema([
                    TextInput::make('account_email')
                        ->label('メールアドレス')
                        ->email()
                        ->helperText('入力すると、このメールで会社用ユーザーを作成/更新して会社に紐付けます。')
                        ->dehydrated(false)
                        ->required(fn (string $context) => $context === 'create'),

                    TextInput::make('account_password')
                        ->label('パスワード')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->helperText('編集時は空のままにするとパスワードは変更されません。')
                        ->dehydrated(false)
                        ->required(fn (string $context) => $context === 'create'),
                ]),
        ];
    }

    /** Create / Edit 共通フォーム */
    public static function form(Form $form): Form
    {
        return $form->schema(self::formSchema());
    }

    /** 一覧テーブル */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('企業名')->searchable(),
                TextColumn::make('slug')->label('Slug')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('更新日')->date('Y-m-d'),
            ])
            ->defaultSort('id', 'desc')

            ->headerActions([
                CreateAction::make()
                    ->label('作成')
                    ->form(self::formSchema())
                    // 保存直前でも中断（最終防衛）
                    ->mutateFormDataUsing(function (array $data): array {
                        if (isset($data['name']) && mb_strlen((string) $data['name']) > 30) {
                            throw Halt::make()->withValidationErrors([
                                'name' => '企業名は30文字以内で入力してください。',
                            ]);
                        }
                        return $data;
                    })
                    ->after(function (Company $record, array $data) {
                        self::upsertCompanyAccount($record, $data);
                    }),
            ])

            ->actions([
                EditAction::make()
                    ->form(self::formSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        if (isset($data['name']) && mb_strlen((string) $data['name']) > 30) {
                            throw Halt::make()->withValidationErrors([
                                'name' => '企業名は30文字以内で入力してください。',
                            ]);
                        }
                        return $data;
                    })
                    ->after(function (Company $record, array $data) {
                        self::upsertCompanyAccount($record, $data);
                    }),

                DeleteAction::make()
                    ->label('削除')
                    ->requiresConfirmation()
                    ->modalHeading('企業を削除')
                    ->modalDescription('この操作は取り消せません。関連データがある場合は削除できないことがあります。')
                    ->modalSubmitActionLabel('削除する'),
            ]);
    }

    /**
     * 会社アカウント（User）を作成 / 更新し、会社に紐付け
     */
    public static function upsertCompanyAccount(Company $company, array $data): void
    {
        $email    = trim((string)($data['account_email'] ?? request()->input('account_email', '')));
        $password = (string)($data['account_password'] ?? request()->input('account_password', ''));

        if ($email === '' && $password === '') return;
        if ($email === '') return;

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name     = $company->name;
            $user->password = \Hash::make($password !== '' ? $password : str()->random(16));
        } else {
            if ($password !== '') {
                $user->password = \Hash::make($password);
            }
        }

        if (method_exists($user, 'assignRole')) {
            try { $user->assignRole('company'); } catch (\Throwable $e) {}
        }
        $user->role      = 'company';
        $user->is_active = true;
        $user->save();

        if (method_exists($company, 'users')) {
            if (! $company->users()->where('users.id', $user->id)->exists()) {
                $company->users()->attach($user->id);
            }
        } elseif (isset($company->user_id)) {
            $company->user_id = $user->id;
            $company->save();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanies::route('/'),
        ];
    }
}
