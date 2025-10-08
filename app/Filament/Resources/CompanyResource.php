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

// 追加: 行アクション（編集・削除）
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

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

            // ▼ 会社ログイン用アカウント（任意入力）
            Fieldset::make('ログインアカウント（任意）')
                ->columns(2)
                ->schema([
                    TextInput::make('account_email')
                        ->label('メールアドレス')
                        ->email()
                        ->helperText('入力すると、このメールで会社用ユーザーを作成/更新して会社に紐付けます。')
                        ->dehydrated(false) // Companyテーブルには保存しない
                        ->required(fn (string $context) => $context === 'create'), // 新規作成時は必須にするなら true
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
            ->actions([
                // 編集後にユーザー作成/紐付けを走らせる
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
        $email    = trim((string)($data['account_email'] ?? ''));
        $password = (string)($data['account_password'] ?? '');

        // 何も入力が無ければスキップ
        if ($email === '' && $password === '') {
            return;
        }
        if ($email === '') {
            // メールなしでパスワードだけ入ったケースはスキップ
            return;
        }

        // 既存ユーザーがいれば更新、いなければ作成
        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user->name = $company->name; // とりあえず会社名を表示名に
            $user->password = bcrypt($password !== '' ? $password : str()->random(16));
        } else {
            if ($password !== '') {
                $user->password = bcrypt($password);
            }
        }
        // 役割があれば company に
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('company');
        } elseif (property_exists($user, 'role') && empty($user->role)) {
            $user->role = 'company';
        }
        $user->save();

        // 会社に紐付け（多対多を想定）
        if (method_exists($company, 'users')) {
            if (! $company->users()->where('users.id', $user->id)->exists()) {
                $company->users()->attach($user->id);
            }
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanies::route('/'),
        ];
    }
}
