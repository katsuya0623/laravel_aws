<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

// 追加
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Http\Controllers\Admin\CompanyInvitationController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
        // ▼ これを追加！
    protected static bool $shouldRegisterNavigation = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withInviteState();
    }

    /** 共通フォーム */
    protected static function formSchema(): array
    {
        return [
            Fieldset::make('会社情報')->schema([
                TextInput::make('name')
                    ->label('企業名')->required()->rule('max:30')
                    ->validationMessages(['max' => '企業名は30文字以内で入力してください。'])
                    ->validationAttribute('企業名')
                    ->live()->helperText('30文字まで'),

                TextInput::make('slug')
                    ->label(new HtmlString('Slug <span class="text-red-500">*</span>'))
                    ->required()->rule('alpha_dash')->unique(ignoreRecord: true)->maxLength(255),

                TextInput::make('description')->label('説明')->columnSpanFull(),
            ]),

            Fieldset::make('ログインアカウント（任意）')
                ->columns(2)
                ->schema([
                    TextInput::make('account_email')
                        ->label('メールアドレス')->email()
                        ->helperText('このメールのユーザーを作成/更新して会社に紐付けます。パスワードは直接変更不可（リセットメールで変更）。')
                        ->dehydrated(false)
                        ->required(fn (string $context) => $context === 'create'),
                ]),
        ];
    }

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
                TextColumn::make('status')
                    ->label('状態')->badge()
                    ->state(fn ($record) => $record->has_pending_invite ? '招待中' : 'アクティブ')
                    ->color(fn (string $state) => $state === '招待中' ? 'warning' : 'success'),
                TextColumn::make('slug')->label('Slug')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('更新日')->date('Y-m-d')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordClasses(fn ($record) => $record->has_pending_invite ? 'bg-amber-50' : 'bg-emerald-50/40')

            ->headerActions([
                CreateAction::make()
                    ->label('作成')->form(self::formSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        if (isset($data['name']) && mb_strlen((string) $data['name']) > 30) {
                            throw Halt::make()->withValidationErrors(['name' => '企業名は30文字以内で入力してください。']);
                        }
                        return $data;
                    })
                    ->after(function (Company $record, array $data) {
                        self::upsertCompanyAccount($record, $data);
                    }),

                Action::make('invite')
                    ->label('企業を招待')->icon('heroicon-o-paper-airplane')
                    ->modalHeading('企業を招待')->modalSubmitActionLabel('招待メールを送信')
                    ->form([
                        TextInput::make('email')->label('メールアドレス')->email()->required(),
                        TextInput::make('company_name')->label('企業名')->required(),
                    ])
                    ->action(function (array $data): void {
                        request()->merge($data);
                        app(CompanyInvitationController::class)->store(request());
                        Notification::make()->title('招待メールを送信しました')->success()->send();
                    }),
            ])

            ->filters([
                SelectFilter::make('招待状態')
                    ->options([
                        'invited' => '招待中のみ',
                        'active'  => 'アクティブのみ',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        return match ($value) {
                            'invited' => $query->where('has_pending_invite', true),
                            'active'  => $query->where('has_pending_invite', false),
                            default   => $query,
                        };
                    }),
            ])

            ->actions([
                EditAction::make()
                    ->form(self::formSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        if (isset($data['name']) && mb_strlen((string) $data['name']) > 30) {
                            throw Halt::make()->withValidationErrors(['name' => '企業名は30文字以内で入力してください。']);
                        }
                        return $data;
                    })
                    ->after(function (Company $record, array $data) {
                        self::upsertCompanyAccount($record, $data);
                    }),

                /**
                 * パスワードリセット送信
                 */
                Action::make('send_reset_link')
                    ->label('パスワードリセットリンクを送信')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->modalHeading('パスワードリセットリンクを送信')
                    ->modalSubmitActionLabel('送信する')
                    ->form([
                        TextInput::make('email')
                            ->label('送信先メールアドレス')
                            ->email()
                            ->required()
                            ->helperText('既にユーザーが紐づいている場合はその登録メールに送信します。未紐付けの場合はここで指定したメールのユーザーを自動作成して会社に紐付け、送信します。')
                            ->default(function ($record) {
                                /** @var \App\Models\Company $record */
                                return CompanyResource::pickInvitationEmail($record) ?? ($record->email ?? '');
                            }),
                    ])
                    ->action(function (Company $record, array $data) {
                        Log::info('PW_RESET start', [
                            'company_id' => $record->id,
                            'company_email' => $record->email ?? null,
                            'company_user_id' => $record->user_id ?? null,
                        ]);

                        // ① 既存紐付けユーザー
                        $user = self::resolveUserForCompany($record);

                        // ② 入力メールで確実に作成/更新（メールが異なる場合も上書き）
                        $targetEmail = trim((string)($data['email'] ?? ''));
                        if (! $user || ($user && strcasecmp($user->email ?? '', $targetEmail) !== 0)) {
                            if ($targetEmail !== '') {
                                $user = User::firstOrNew(['email' => $targetEmail]);
                                if (! $user->exists) {
                                    $user->name     = $record->name ?? 'Company User';
                                    $user->password = bcrypt(Str::random(24));
                                }
                                if (method_exists($user, 'assignRole')) {
                                    try { $user->assignRole('company'); } catch (\Throwable $e) {}
                                }
                                $user->role      = 'company';
                                $user->is_active = true;

                                // ★ 企業はメール認証不要 → 最初から検証済みにする
                                if (Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
                                    $user->email_verified_at = now();
                                }

                                $user->save();

                                self::attachUserToCompany($record, $user);
                                Log::info('PW_RESET ensured user & attached', ['user_id' => $user->id, 'email' => $user->email]);
                            }
                        }

                        if (! $user || ! $user->email) {
                            Notification::make()
                                ->title('送信先ユーザーが見つかりません')
                                ->body('メールアドレスを入力して再実行してください。')
                                ->danger()->send();
                            Log::warning('PW_RESET failed: user unresolved after ensure', ['company_id' => $record->id]);
                            return;
                        }

                        $status = Password::sendResetLink(['email' => $user->email]);

                        if ($status === Password::RESET_LINK_SENT) {
                            Notification::make()
                                ->title('パスワードリセットリンクを送信しました')
                                ->body('送信先: ' . $user->email)
                                ->success()->send();
                            Log::info('PW_RESET sent', ['email' => $user->email]);
                        } else {
                            Notification::make()
                                ->title('リセットリンクの送信に失敗しました')
                                ->body('ステータス: ' . $status)
                                ->danger()->send();
                            Log::warning('PW_RESET status not ok', ['status' => $status, 'email' => $user->email]);
                        }
                    }),

                DeleteAction::make()
                    ->label('削除')->requiresConfirmation()
                    ->modalHeading('企業を削除')
                    ->modalDescription('この操作は取り消せません。関連データがある場合は削除できないことがあります。')
                    ->modalSubmitActionLabel('削除する'),
            ]);
    }

    /** 編集保存時：メールからユーザー作成＋会社に紐付け（企業は認証不要として verified 済みにする） */
    public static function upsertCompanyAccount(Company $company, array $data): void
    {
        $email = trim((string)($data['account_email'] ?? request()->input('account_email', '')));
        if ($email === '') return;

        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user->name     = $company->name;
            $user->password = bcrypt(Str::random(24));
        }
        if (method_exists($user, 'assignRole')) {
            try { $user->assignRole('company'); } catch (\Throwable $e) {}
        }
        $user->role      = 'company';
        $user->is_active = true;

        // ★ 企業はメール認証不要
        if (Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
            $user->email_verified_at = now();
        }

        $user->save();

        self::attachUserToCompany($company, $user);
    }

    /** 招待テーブルから最新のメールを推定（カラム名差異に対応） */
    private static function pickInvitationEmail(Company $company): ?string
    {
        if (! Schema::hasTable('company_invitations')) return null;
        if (! Schema::hasColumn('company_invitations', 'company_id')) return null;

        // よくあるメールカラム名のいずれかを拾う
        $emailCandidates = array_values(array_filter([
            Schema::hasColumn('company_invitations', 'email') ? 'email' : null,
            Schema::hasColumn('company_invitations', 'invited_email') ? 'invited_email' : null,
            Schema::hasColumn('company_invitations', 'invitee_email') ? 'invitee_email' : null,
            Schema::hasColumn('company_invitations', 'recipient_email') ? 'recipient_email' : null,
        ]));

        if (empty($emailCandidates)) return null;

        $q = DB::table('company_invitations')->where('company_id', $company->id);

        if (Schema::hasColumn('company_invitations', 'status')) {
            $q->whereIn('status', ['pending', 'sent', 'invited']);
        }

        // created_at が無ければ id で降順
        if (Schema::hasColumn('company_invitations', 'created_at')) {
            $q->orderByDesc('created_at');
        } else {
            $q->orderByDesc('id');
        }

        $row = (array) $q->first();
        foreach ($emailCandidates as $col) {
            if (!empty($row[$col])) {
                return trim((string) $row[$col]);
            }
        }
        return null;
    }

    /** 既存の紐付けを広めに探索して特定 */
    private static function resolveUserForCompany(Company $company): ?User
    {
        if (isset($company->user_id) && $company->user_id) {
            if ($u = User::find($company->user_id)) return $u;
        }

        if (isset($company->email) && filled($company->email)) {
            if ($u = User::where('email', $company->email)->first()) return $u;
        }

        if (Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'company_id')
            && Schema::hasColumn('company_user', 'user_id')) {
            $userId = DB::table('company_user')->where('company_id', $company->id)->value('user_id');
            if ($userId && ($u = User::find($userId))) return $u;
        }

        if (Schema::hasTable('company_profiles')
            && Schema::hasTable('company_user')
            && Schema::hasColumn('company_profiles', 'company_id')
            && Schema::hasColumn('company_user', 'company_profile_id')) {
            $profileId = DB::table('company_profiles')->where('company_id', $company->id)->value('id');
            if ($profileId) {
                $userId = DB::table('company_user')->where('company_profile_id', $profileId)->value('user_id');
                if ($userId && ($u = User::find($userId))) return $u;
            }
        }

        if (Schema::hasTable('company_profiles')
            && Schema::hasColumn('company_profiles', 'user_id')
            && Schema::hasColumn('company_profiles', 'company_id')) {
            $profileUserId = DB::table('company_profiles')->where('company_id', $company->id)->value('user_id');
            if ($profileUserId && ($u = User::find($profileUserId))) return $u;
        }

        if (Schema::hasTable('company_profiles')
            && Schema::hasColumn('company_profiles', 'email')
            && Schema::hasColumn('company_profiles', 'company_id')) {
            $profileEmail = DB::table('company_profiles')->where('company_id', $company->id)->value('email');
            if ($profileEmail) {
                if ($u = User::where('email', $profileEmail)->first()) return $u;
            }
        }

        return null;
    }

    /** 会社とユーザーの紐付けを安全に作成 */
    private static function attachUserToCompany(Company $company, User $user): void
    {
        if (Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'company_id')
            && Schema::hasColumn('company_user', 'user_id')) {

            $exists = DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->exists();

            if (! $exists) {
                DB::table('company_user')->insert([
                    'company_id' => $company->id,
                    'user_id'    => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return;
        }

        if (Schema::hasTable('company_user')
            && Schema::hasColumn('company_user', 'company_profile_id')
            && Schema::hasTable('company_profiles')
            && Schema::hasColumn('company_profiles', 'company_id')) {

            $profileId = DB::table('company_profiles')->where('company_id', $company->id)->value('id');

            if ($profileId) {
                $exists = DB::table('company_user')
                    ->where('company_profile_id', $profileId)
                    ->where('user_id', $user->id)
                    ->exists();

                if (! $exists) {
                    DB::table('company_user')->insert([
                        'company_profile_id' => $profileId,
                        'user_id'            => $user->id,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
                return;
            }
        }

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'user_id')) {
            if ((int) ($company->user_id ?? 0) !== (int) $user->id) {
                $company->user_id = $user->id;
                $company->save();
            }
        }
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCompanies::route('/')];
    }
}
