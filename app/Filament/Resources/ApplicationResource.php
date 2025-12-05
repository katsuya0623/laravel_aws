<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Models\Application;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\RecruitJobResource;


class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    // 左メニュー＆タイトル周り
    protected static ?string $navigationIcon   = 'heroicon-o-inbox-stack';
    protected static ?string $navigationLabel  = '応募一覧';
    protected static ?string $pluralModelLabel = '応募一覧（管理者）';
    protected static ?string $navigationGroup  = 'Management'; // 企業一覧と合わせる
    protected static ?int    $navigationSort   = 40; // ★ これを追加

    public static function getNavigationSort(): ?int   // ← これを追加
    {
        return static::$navigationSort ?? 40;
    }



    public static function form(Form $form): Form
    {
        // 応募は閲覧専用なら空でOK
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // === 元の Blade と同じ並び＆内容 ===
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('応募日時')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('応募者')
                    ->default('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('メール')
                    ->default('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('job.title')
                    ->label('求人')
                    ->placeholder('（削除済み求人）')
                    ->url(function ($record) {
                        // job が無い場合はリンクなし
                        if (! $record->job) {
                            return null;
                        }

                        // slug があれば slug、なければ ID を使う
                        $slugOrId = $record->job->slug ?? $record->job_id;

                        // フロントの求人詳細ページ
                        return route('front.jobs.show', ['slugOrId' => $slugOrId]);
                    })
                    ->openUrlInNewTab(), // ← カンマにする



                Tables\Columns\TextColumn::make('job_id')
                    ->label('求人ID')
                    ->default('—'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('ステータス')
                    ->colors([
                        'primary' => 'new',
                        'gray'    => 'viewed',
                        'warning' => 'interview',
                        'success' => 'offer',
                        'danger'  => 'rejected',
                        'info'    => 'withdrawn',
                    ])
                    ->formatStateUsing(fn(?string $state) => $state ?: '—'),
            ])

            // === 元フォームの「絞り込み」を Filament の Filters に移植 ===
            ->filters([
                // キーワード：応募者名 / メール / 求人タイトル
                Tables\Filters\Filter::make('q')
                    ->label('キーワード')
                    ->form([
                        Forms\Components\TextInput::make('q')
                            ->placeholder('応募者名 / メール / 求人タイトル'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $keyword = $data['q'] ?? null;
                        if (! filled($keyword)) {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($keyword) {
                            $q->where('name', 'like', "%{$keyword}%")
                                ->orWhere('email', 'like', "%{$keyword}%")
                                ->orWhereHas('job', function (Builder $q2) use ($keyword) {
                                    $q2->where('title', 'like', "%{$keyword}%");
                                });
                        });
                    }),

                // ステータス（元の $statusOptions 相当）
                Tables\Filters\SelectFilter::make('status')
                    ->label('ステータス')
                    ->options([
                        ''          => 'すべて',
                        'new'       => 'new',
                        'viewed'    => 'viewed',
                        'interview' => 'interview',
                        'offer'     => 'offer',
                        'rejected'  => 'rejected',
                        'withdrawn' => 'withdrawn',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;
                        if ($status === null || $status === '') {
                            return $query;
                        }

                        return $query->where('status', $status);
                    }),

                // 求人ID
                Tables\Filters\Filter::make('job_id')
                    ->label('求人ID')
                    ->form([
                        Forms\Components\TextInput::make('job_id')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! filled($data['job_id'] ?? null)) {
                            return $query;
                        }

                        return $query->where('job_id', $data['job_id']);
                    }),

                // 会社ID（applications テーブルに company_id がある想定）
                Tables\Filters\Filter::make('company_id')
                    ->label('会社ID')
                    ->form([
                        Forms\Components\TextInput::make('company_id')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! filled($data['company_id'] ?? null)) {
                            return $query;
                        }

                        return $query->where('company_id', $data['company_id']);
                    }),
            ])

            // 行アクションはいったん無し（閲覧専用にしたい前提）
            ->actions([])

            // 一括アクションも無し
            ->bulkActions([])

            // 並び順：応募日時の新しい順
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            // create / edit は使わないならコメントアウト
            // 'create' => Pages\CreateApplication::route('/create'),
            // 'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
