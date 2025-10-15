<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Fieldset;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Support\Facades\Hash;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Fieldset::make('会社情報')->schema([
                TextInput::make('name')
                    ->label('企業名')
                    ->required(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->unique(ignoreRecord: true),

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
        ]);
    }

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
                    ->after(function (Company $record, array $data) {
                        self::upsertCompanyAccount($record, $data);
                    }),
            ])

            ->actions([
                EditAction::make()
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
        // ▼ フォームデータ or リクエストからメール/パスワードを取得
        $email    = trim((string)($data['account_email'] ?? request()->input('account_email', '')));
        $password = (string)($data['account_password'] ?? request()->input('account_password', ''));

        // 何も入力がなければスキップ
        if ($email === '' && $password === '') {
            return;
        }
        if ($email === '') {
            return;
        }

        // 既存ユーザーがいれば更新、いなければ作成
        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $company->name;
            $user->password = Hash::make($password !== '' ? $password : str()->random(16));
        } else {
            if ($password !== '') {
                $user->password = Hash::make($password);
            }
        }

        // --- ロール付与を確実に ---
        if (method_exists($user, 'assignRole')) {
            try { $user->assignRole('company'); } catch (\Throwable $e) { /* ignore */ }
        }

        $user->role = 'company';
        $user->is_active = true;
        // $user->email_verified_at = now(); // メール認証をスキップしたい場合はコメント解除

        $user->save();

        // --- 会社との紐付け ---
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
