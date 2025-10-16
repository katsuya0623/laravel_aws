<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Livewire v3 Config
    |--------------------------------------------------------------------------
    */

    'temporary_file_upload' => [
        // ← 既定（local）を使う。ここを null にしておくのが一番安全
        'disk'       => null,

        // ← Livewire v3 の推奨場所
        //     storage/framework/livewire-tmp を使います
        'directory'  => 'framework/livewire-tmp',

        'middleware' => null,
        'rules'      => null,

        'preview_mimes' => [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        ],

        'max_upload_time' => 5,     // 分

        // 古い一時ファイルの自動削除
        'cleanup'              => true,
        'cleanup_probability'  => 1,
        'cleanup_seconds'      => 3600,
    ],

    'assets_url'            => null,
    'legacy_model_binding'  => false,
    'inject_assets'         => true,
    'navigate'              => true,
];
