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

// ▼ 追加
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Http\Controllers\Admin\CompanyInvitationController;

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
                    ->rule('max:30')
                    ->validationMessages([
                        'max' => '企業名は30文字以内で入力してください。',
                    ])
                    ->validationAttribute('企業名')
                    ->live()
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

            // ★ 「作成」＋「企業を招待」ボタンをここに設置
            ->headerActions([
                CreateAction::make()
                    ->label('作成')
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

                // ✅ 追加：企業を招待ボタン
                Action::make('invite')
                    ->label('企業を招待')
                    ->icon('heroicon-o-paper-airplane')
                    ->modalHeading('企業を招待')
                    ->modalSubmitActionLabel('招待メールを送信')
                    ->form([
                        TextInput::make('email')->label('メールアドレス')->email()->required(),
                        TextInput::make('company_name')->label('企業名')->required(),
                    ])
                    ->action(function (array $data): void {
                        request()->merge($data);
                        app(CompanyInvitationController::class)->store(request());

                        Notification::make()
                            ->title('招待メールを送信しました')
                            ->success()
                            ->send();
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

    /** ユーザー紐付け処理（既存） */
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
