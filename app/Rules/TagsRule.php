<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TagsRule implements ValidationRule
{
    /**
     * バリデーションロジック
     *
     * @param  string  $attribute  フィールド名
     * @param  mixed   $value      入力値
     * @param  \Closure(string): void  $fail  エラー時にメッセージを登録
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            $fail('タグを入力してください。');
            return;
        }

        $tags = preg_split('/\s+/u', $raw);

        if (count($tags) > 10) {
            $fail('タグは最大10個までです。');
            return;
        }

        foreach ($tags as $t) {
            if (mb_strlen($t) > 20) {
                $fail("タグ「{$t}」が長すぎます（最大20文字）。");
                return;
            }

            if (preg_match('/[,\|、。]/u', $t)) {
                $fail("タグ「{$t}」に区切り記号は使えません。スペースで区切ってください。");
                return;
            }
        }
    }
}
