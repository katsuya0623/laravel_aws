<?php

namespace App\Filament\Resources;

use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required(),

                Forms\Components\FileUpload::make('thumbnail_path')
                    ->image()
                    ->disk('public')          // 必須
                    ->directory('posts')      // storage/app/public/posts
                    ->visibility('public')
                    ->preserveFilenames(),    // ← imageEditor() を削除

                Forms\Components\Textarea::make('excerpt'),
                Forms\Components\RichEditor::make('body'),
            ]);
    }

    // …table() など他の既存コード…
}
