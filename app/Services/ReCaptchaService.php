<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReCaptchaService
{
    /**
     * reCAPTCHA v3 を検証する
     *
     * @param  string|null  $token   フロントから送られてきたトークン
     * @param  string|null  $action  JS 側で指定した action（login など）
     * @param  float        $minScore 許可するスコアの下限（0.0〜1.0）
     * @return bool  true: OK / false: ブロック
     */
    public function verify(?string $token, ?string $action = null, float $minScore = 0.5): bool
    {
        // トークンが空なら即 NG
        if (empty($token)) {
            Log::warning('[reCAPTCHA] empty token');
            return false;
        }

        $secret = config('services.recaptcha.secret_key');

        // シークレットキーが設定されてない場合は NG（本番なので fail closed にする）
        if (empty($secret)) {
            Log::error('[reCAPTCHA] secret key not configured');
            return false;
        }

        // Google の verify API に投げる
        $response = Http::asForm()->post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => request()->ip(),
            ]
        );

        if (! $response->successful()) {
            Log::error('[reCAPTCHA] HTTP error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        $data = $response->json();

        // success フラグ
        if (! ($data['success'] ?? false)) {
            Log::warning('[reCAPTCHA] validation failed', $data);
            return false;
        }

        // action が指定されている場合は一致チェック（login とか）
        if ($action !== null && ($data['action'] ?? null) !== $action) {
            Log::warning('[reCAPTCHA] action mismatch', $data);
            return false;
        }

        // スコア判定（デフォ 0.5 以上なら OK）
        $score = (float) ($data['score'] ?? 0.0);

        if ($score < $minScore) {
            Log::warning('[reCAPTCHA] low score', [
                'score'     => $score,
                'minScore'  => $minScore,
                'action'    => $action,
            ]);
            return false;
        }

        // ここまで来れば OK
        Log::info('[reCAPTCHA] passed', [
            'score'  => $score,
            'action' => $action,
        ]);

        return true;
    }
}
