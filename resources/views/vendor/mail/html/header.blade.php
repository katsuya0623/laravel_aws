@props(['url'])

@php
    // 絶対URL + 更新時刻でキャッシュバスター付与
    $logoPath = public_path('images/logo.png');
    $logoUrl  = rtrim(config('app.url'), '/') . '/images/logo.png';

    if (is_file($logoPath)) {
        $logoUrl .= '?v=' . filemtime($logoPath);
    }
@endphp

<tr>
<td class="header">
    <a href="{{ $url }}" style="display:inline-block;">
        <img src="{{ $logoUrl }}" alt="nibi ロゴ"
             style="height:48px;display:block;border:0;outline:none;text-decoration:none;">
    </a>
</td>
</tr>
