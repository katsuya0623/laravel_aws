<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LogoStorage
{
    /**
     * 会社ロゴを保存して相対パスを返す（disk=public）
     * 対応拡張子: jpg/jpeg/png/webp/svg/svgz
     * サイズ上限: 10MB（バリデーション側で担保）
     * 簡易サニタイズ: script/foreignObject/on* 属性 / javascript: / data:text/html を除去
     */
    public function store(UploadedFile $file): string
    {
        $ext  = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $name = uniqid('logo_', true) . '.' . $ext;
        $path = 'company_logos/' . $name;

        if (in_array($ext, ['svg', 'svgz'], true)) {
            $svg = file_get_contents($file->getRealPath());
            // 危険タグ除去
            $svg = preg_replace('~</?(script|foreignObject)\b[^>]*>~i', '', $svg);
            // on* イベント属性除去
            $svg = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg);
            // javascript: / data:text/html を無効化
            $svg = preg_replace('/(xlink:href|href)\s*=\s*("|\')\s*(javascript:|data:text\/html)/i', '$1=$2#', $svg);
            // XML宣言/DOCTYPE削除
            $svg = preg_replace('/<\?xml.*?\?>/is', '', $svg);
            $svg = preg_replace('/<!DOCTYPE.*?>/is', '', $svg);

            Storage::disk('public')->put($path, $svg);
        } else {
            // ラスタ画像はそのまま保存（必要に応じて後でリサイズ追加）
            $file->storeAs('company/logo', $name, 'public');
        }
        return $path;
    }
}
