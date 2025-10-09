<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Hash;

class ManageCompanies extends ManageRecords
{
    protected static string $resource = CompanyResource::class;

    /**
     * ヘッダーアクション（ダッシュボードへ / 作成）
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('ダッシュボードへ')
                ->icon('heroicon-o-home')
                ->url('/admin/dashboard'), // ← ここを修正

            Actions\CreateAction::make()
                ->label('作成')
                // Companyの基本項目 + 追加のアカウント項目
                ->form([
                    TextInput::make('name')
                        ->label('企業名')
                        ->required(),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->unique(ignoreRecord: true),
                    TextInput::make('description')
                        ->label('説明')
                        ->columnSpanFull(),

                    Section::make('ログインアカウント（任意）')->schema([
                        TextInput::make('account_email')
                            ->label('メールアドレス')
                            ->email()
                            ->helperText('同じメールが存在する場合はそのユーザーを関連付け／存在しなければ新規作成します。')
                            ->dehydrated(false),
                        TextInput::make('account_password')
                            ->label('パスワード')
                            ->password()
                            ->minLength(8)
                            ->dehydrated(false),
                    ])->columns(2),
                ])
                // Company + User 同期ロジック
                ->using(function (array $data): Company {
                    $email    = $data['account_email']    ?? null;
                    $password = $data['account_password'] ?? null;
                    unset($data['account_email'], $data['account_password']);

                    /** @var Company $company */
                    $company = Company::create($data);

                    if ($email) {
                        $user = User::firstOrNew(['email' => $email]);
                        if (! $user->exists) {
                            $user->name = $company->name;
                        }
                        if ($password) {
                            $user->password = Hash::make($password);
                        }
                        $user->save();

                        // Spatie/laravel-permission を使っていれば company 役割を付与
                        if (method_exists($user, 'assignRole')) {
                            try { $user->assignRole('company'); } catch (\Throwable) {}
                        }

                        // 会社⇔ユーザーの関連があれば（pivotなど）
                        if (method_exists($company, 'users')) {
                            try { $company->users()->syncWithoutDetaching([$user->id]); } catch (\Throwable) {}
                        }
                    }

                    return $company;
                }),
        ];
    }

    /**
     * 行アクション（編集／削除）
     */
    protected function getTableActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('編集')
                ->form([
                    TextInput::make('name')
                        ->label('企業名')
                        ->required(),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->unique(ignoreRecord: true),
                    TextInput::make('description')
                        ->label('説明')
                        ->columnSpanFull(),

                    Section::make('ログインアカウント（任意）')->schema([
                        TextInput::make('account_email')
                            ->label('メールアドレス')
                            ->email()
                            ->helperText('入力した場合はこのメールのユーザーを関連付けます（なければ作成）。')
                            ->dehydrated(false),
                        TextInput::make('account_password')
                            ->label('パスワード（変更時のみ）')
                            ->password()
                            ->minLength(8)
                            ->helperText('空のままならパスワードは変更しません。')
                            ->dehydrated(false),
                    ])->columns(2),
                ])
                ->using(function (Company $record, array $data): Company {
                    $email    = $data['account_email']    ?? null;
                    $password = $data['account_password'] ?? null;
                    unset($data['account_email'], $data['account_password']);

                    // 会社情報を先に更新
                    $record->update($data);

                    if ($email) {
                        $user = User::firstOrNew(['email' => $email]);
                        if (! $user->exists) {
                            $user->name = $record->name;
                        }
                        if ($password) {
                            $user->password = Hash::make($password);
                        }
                        $user->save();

                        if (method_exists($user, 'assignRole')) {
                            try { $user->assignRole('company'); } catch (\Throwable) {}
                        }
                        if (method_exists($record, 'users')) {
                            try { $record->users()->syncWithoutDetaching([$user->id]); } catch (\Throwable) {}
                        }
                    }

                    return $record;
                }),

            Actions\DeleteAction::make()->label('削除'),
        ];
    }
}
