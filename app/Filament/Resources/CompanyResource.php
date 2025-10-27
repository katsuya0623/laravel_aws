<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withInviteState();
    }

    /** 共通フォーム（Company本体＋CompanyProfile編集用の項目） */
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

                Textarea::make('description')->label('説明')->columnSpanFull(),
            ]),

            // ▼ company_profiles を編集するためのフォーム群（profile_* として受ける）
            Fieldset::make('企業プロフィール')
                ->columns(2)
                ->schema([
                    TextInput::make('profile_company_kana')
                        ->label('会社名（カナ）')
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('company_name_kana')
                                : null;
                        }),

                    Textarea::make('profile_intro')
                        ->label('事業内容 / 紹介')
                        ->columnSpanFull()
                        ->rows(4)
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('description')
                                : null;
                        }),

                    FileUpload::make('profile_logo_path')
                        ->label('ロゴ画像')
                        ->directory('company_logos')
                        ->disk('public')
                        ->image()
                        ->imageEditor()
                        ->openable()
                        ->downloadable()
                        ->preserveFilenames()
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('logo_path')
                                : null;
                        }),

                    TextInput::make('profile_website_url')
                        ->label('Webサイト')
                        ->placeholder('https://example.com')
                        ->url()
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('website_url')
                                : null;
                        }),

                    TextInput::make('profile_email')
                        ->label('代表メール')
                        ->email()
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('email')
                                : null;
                        }),

                    TextInput::make('profile_phone')
                        ->label('電話番号')
                        ->placeholder('03-1234-5678')
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('tel')
                                : null;
                        }),

                    TextInput::make('profile_postal_code')
                        ->label('郵便番号')
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('postal_code')
                                : null;
                        }),

                    TextInput::make('profile_prefecture')
                        ->label('都道府県')
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('prefecture')
                                : null;
                        }),

                    TextInput::make('profile_city')
                        ->label('市区町村')
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('city')
                                : null;
                        }),

                    TextInput::make('profile_address1')
                        ->label('番地・建物名')
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('address1')
                                : null;
                        }),

                    TextInput::make('profile_address2')
                        ->label('部屋番号など')
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('address2')
                                : null;
                        }),

                    TextInput::make('profile_industry')
                        ->label('業種')
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('industry')
                                : null;
                        }),

                    TextInput::make('profile_employees')
                        ->label('従業員数')
                        ->numeric()
                        ->default(function (?Company $record) {
                            return $record
                                ? DB::table('company_profiles')->where('company_id', $record->id)->value('employees')
                                : null;
                        }),

                    DatePicker::make('profile_founded_at')
                        ->label('設立日')
                        ->displayFormat('Y/m/d')
                        ->default(function (?Company $record) {
                            if (! $record) return null;
                            $val = DB::table('company_profiles')->where('company_id', $record->id)->value('founded_on');
                            // SQLite でも 'YYYY-MM-DD' ならそのまま返す
                            return $val ?: null;
                        }),
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
                        self::upsertCompanyProfile($record, $data);
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
                        self::upsertCompanyProfile($record, $data);
                    }),

                // 以降は既存のパスワードリセット系そのまま
                Action::make('quick_send_reset_link')
                    ->label('パスワードリセットを送信（自動）')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->modalHeading('パスワードリセットリンクを送信')
                    ->modalDescription(function (Company $record) {
                        $email = self::guessResetEmail($record);
                        return $email
                            ? "下記のメールに送信します。\n\n送信先: {$email}"
                            : "送信先メールアドレスを特定できませんでした。下の「パスワードリセットリンクを送信」からメールを入力してください。";
                    })
                    ->modalSubmitActionLabel('送信する')
                    ->action(function (Company $record) {
                        $email = self::guessResetEmail($record);

                        if (!$email) {
                            Notification::make()
                                ->title('送信先を特定できません')
                                ->body('「パスワードリセットリンクを送信」からメールを入力して送信してください。')
                                ->danger()->send();
                            return;
                        }

                        $user   = self::ensureUserForEmail($record, $email);
                        $status = self::sendReset($user->email);

                        if ($status === Password::RESET_LINK_SENT) {
                            Notification::make()
                                ->title('パスワードリセットリンクを送信しました')
                                ->body('送信先: '.$user->email)
                                ->success()->send();
                        } else {
                            Notification::make()
                                ->title('リセットリンクの送信に失敗しました')
                                ->body('ステータス: '.$status)
                                ->danger()->send();
                        }
                    }),

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
                            'company_id'      => $record->id,
                            'company_email'   => $record->email ?? null,
                            'company_user_id' => $record->user_id ?? null,
                        ]);

                        // ① 既存紐付けユーザー
                        $user = self::resolveUserForCompany($record);

                        // ② 入力メールで確実に作成/更新
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

                        $status = self::sendReset($user->email);

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

    /** 編集保存時：メールからユーザー作成＋会社に紐付け（verified 済みにする） */
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

        if (Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
            $user->email_verified_at = now();
        }

        $user->save();

        self::attachUserToCompany($company, $user);
    }

    /**
     * company_profiles を upsert（存在すれば更新、無ければ作成）
     * - 実テーブル: company_profiles
     * - カラム名: company_name_kana / description / tel / founded_on などに統一
     * - 既存行判定は company_id → user_id の順で厳密に
     */
    protected static function upsertCompanyProfile(Company $company, array $data): void
    {
        if (! Schema::hasTable('company_profiles')) {
            return;
        }

        // 受け取りキー → DBカラムのマッピング
        $map = [
            'profile_company_kana' => 'company_name_kana',
            'profile_intro'        => 'description',
            'profile_logo_path'    => 'logo_path',
            'profile_website_url'  => 'website_url',
            'profile_email'        => 'email',
            'profile_phone'        => 'tel',
            'profile_postal_code'  => 'postal_code',
            'profile_prefecture'   => 'prefecture',
            'profile_city'         => 'city',
            'profile_address1'     => 'address1',
            'profile_address2'     => 'address2',
            'profile_industry'     => 'industry',
            'profile_employees'    => 'employees',
            'profile_founded_at'   => 'founded_on',
        ];

        $payload = [];
        foreach ($map as $formKey => $col) {
            if (! array_key_exists($formKey, $data)) continue;
            if (! Schema::hasColumn('company_profiles', $col)) continue;
            $payload[$col] = $data[$formKey];
        }

        if (Schema::hasColumn('company_profiles', 'company_id')) {
            $payload['company_id'] = $company->id;
        }

        // user_id（NOT NULL想定）も頑張って解決
        $resolvedUserId = null;
        if (Schema::hasColumn('company_profiles', 'user_id')) {
            $user = self::resolveUserForCompany($company);
            if ($user?->id) {
                $resolvedUserId = (int) $user->id;
            } elseif (!empty($company->user_id)) {
                $resolvedUserId = (int) $company->user_id;
            }
            if ($resolvedUserId) {
                $payload['user_id'] = $resolvedUserId;
            }
        }

        if (empty($payload)) return;

        $now = now();
        if (Schema::hasColumn('company_profiles', 'updated_at')) {
            $payload['updated_at'] = $now;
        }

        // 既存行を厳密に探索：company_id 優先、無ければ user_id
        $existing = null;
        if (Schema::hasColumn('company_profiles', 'company_id')) {
            $existing = DB::table('company_profiles')
                ->where('company_id', $company->id)
                ->first();
        }
        if (! $existing && Schema::hasColumn('company_profiles', 'user_id') && $resolvedUserId) {
            $existing = DB::table('company_profiles')
                ->where('user_id', $resolvedUserId)
                ->first();
        }

        if ($existing) {
            DB::table('company_profiles')->where('id', $existing->id)->update($payload);
            return;
        }

        // 新規 INSERT
        if (Schema::hasColumn('company_profiles', 'created_at')) {
            $payload['created_at'] = $now;
        }
        if (Schema::hasColumn('company_profiles', 'user_id') && !isset($payload['user_id'])) {
            Log::warning('Skip insert company_profiles: user_id unresolved', ['company_id' => $company->id]);
            return;
        }

        DB::table('company_profiles')->insert($payload);
    }

    /** 招待テーブルから最新のメールを推定（accepted 含む／フォールバックあり） */
    private static function pickInvitationEmail(Company $company): ?string
    {
        if (! Schema::hasTable('company_invitations')) return null;

        $emailCols = array_values(array_filter([
            Schema::hasColumn('company_invitations', 'email')           ? 'email'           : null,
            Schema::hasColumn('company_invitations', 'invited_email')   ? 'invited_email'   : null,
            Schema::hasColumn('company_invitations', 'invitee_email')   ? 'invitee_email'   : null,
            Schema::hasColumn('company_invitations', 'recipient_email') ? 'recipient_email' : null,
        ]));
        if (empty($emailCols)) return null;

        $base = DB::table('company_invitations');
        if (Schema::hasColumn('company_invitations', 'company_id')) {
            $base->where('company_id', $company->id);
        } elseif (Schema::hasColumn('company_invitations', 'company_name')) {
            $base->where('company_name', $company->name);
        }

        $order = function ($q) {
            if (Schema::hasColumn('company_invitations', 'created_at')) {
                $q->orderByDesc('created_at');
            } else {
                $q->orderByDesc('id');
            }
        };

        $preferred = clone $base;
        if (Schema::hasColumn('company_invitations', 'status')) {
            $preferred->whereIn('status', ['pending', 'sent', 'invited', 'accepted']);
        }
        $order($preferred);

        $row = (array) ($preferred->first() ?? []);

        if (empty($row)) {
            $fallback = clone $base;
            $order($fallback);
            $row = (array) ($fallback->first() ?? []);
        }

        foreach ($emailCols as $col) {
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

    /** 送信先メールを自動推定 */
    private static function guessResetEmail(Company $company): ?string
    {
        if ($u = self::resolveUserForCompany($company)) {
            if (filled($u->email)) return $u->email;
        }

        if ($inv = self::pickInvitationEmail($company)) {
            return $inv;
        }

        if (isset($company->email) && filled($company->email)) {
            return $company->email;
        }

        if (Schema::hasTable('company_profiles') && Schema::hasColumn('company_profiles', 'email')) {
            $profileEmail = DB::table('company_profiles')->where('company_id', $company->id)->value('email');
            if ($profileEmail) return $profileEmail;
        }

        return null;
    }

    /** 指定メールのユーザーを用意し、会社に紐付けて返す */
    private static function ensureUserForEmail(Company $company, string $email): User
    {
        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name     = $company->name ?? 'Company User';
            $user->password = bcrypt(Str::random(24));
        }

        if (method_exists($user, 'assignRole')) {
            try { $user->assignRole('company'); } catch (\Throwable $e) {}
        }
        if (property_exists($user, 'role')) {
            $user->role = 'company';
        }
        if (property_exists($user, 'is_active')) {
            $user->is_active = true;
        }
        if (Schema::hasColumn($user->getTable(), 'email_verified_at') && empty($user->email_verified_at)) {
            $user->email_verified_at = now();
        }

        $user->save();

        self::attachUserToCompany($company, $user);

        return $user;
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

    /** Password リセットを broker('users') で送信（統一ヘルパ） */
    private static function sendReset(string $email): string
    {
        $broker = config('auth.defaults.passwords', 'users'); // 念のため設定値に追従
        return Password::broker($broker)->sendResetLink(['email' => $email]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCompanies::route('/')];
    }
}
