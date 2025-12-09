<?php

namespace App\Filament\Widgets;

use App\Models\Job;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PopularJobsTable extends BaseWidget
{
    protected static ?string $heading = '人気の求人';

    // ダッシュボード上で横幅フルに使う
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Job::query()
                    // ★ 応募リレーションの名前に合わせて修正（例：entries, applications など）
                    ->withCount('applications')
                    // ★ 閲覧数カラム名に合わせて修正（view_count, views など）
                    ->orderByDesc('view_count')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('求人タイトル')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('閲覧数')
                    ->sortable(),

                Tables\Columns\TextColumn::make('applications_count')
                    ->label('応募数')
                    ->sortable(),
            ])
            ->defaultSort('view_count', 'desc');
    }
}
