@extends('front.layout')
@section('title','企業一覧')

@section('content')
  <div style="max-width:1000px; margin:32px auto; padding:0 16px;">
    <h1 style="font-size:22px; font-weight:700; margin:0 0 16px;">企業一覧</h1>

    @php
      // コントローラからは Collection/stdClass の配列で来る想定
      $list = collect($companies ?? []);
    @endphp

    @if($list->isEmpty())
      <p style="color:#6b7280;">企業データは準備中です。</p>
    @else
      <div style="overflow-x:auto; background:#fff; border:1px solid #e5e7eb; border-radius:8px;">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
          <thead>
            <tr style="background:#f9fafb; color:#6b7280; text-align:left;">
              <th style="padding:12px 16px; border-bottom:1px solid #e5e7eb; width:80px;">ID</th>
              <th style="padding:12px 16px; border-bottom:1px solid #e5e7eb; width:120px;">ロゴ</th>
              <th style="padding:12px 16px; border-bottom:1px solid #e5e7eb;">企業名</th>
              <th style="padding:12px 16px; border-bottom:1px solid #e5e7eb; width:30%;">スラッグ</th>
              <th style="padding:12px 16px; border-bottom:1px solid #e5e7eb; width:180px;">更新日</th>
            </tr>
          </thead>
          <tbody>
          @foreach($list as $c)
            @php
              // コントローラで付与済みの値をそのまま使う
              $id       = $c->id      ?? null;
              $name     = $c->name    ?? '(名称未設定)';
              $slug     = $c->slug    ?? null;
              $updated  = $c->updated_at ?? null;
              $logoUrl  = $c->logoUrl ?? null;                 // ← これが詳細と同じロゴURL
              $showKey  = $c->showKey ?? ($slug ?: $id);       // /company/{slugOrId}

              // 日付表示
              $updatedText = '-';
              try {
                if ($updated) { $updatedText = \Illuminate\Support\Carbon::parse($updated)->format('Y年n月j日'); }
              } catch (\Throwable $e) { $updatedText = (string)$updated; }

              // 念のためフォールバック
              if (empty($logoUrl)) { $logoUrl = asset('images/noimage.svg'); }
            @endphp

            <tr style="border-top:1px solid #e5e7eb;">
              <td style="padding:10px 16px;">{{ $id }}</td>
              <td style="padding:10px 16px;">
                <img src="{{ $logoUrl }}" alt="{{ $name }}"
                     style="width:100px; height:80px; object-fit:contain; border:1px solid #e5e7eb; background:#fff; border-radius:4px;"
                     loading="lazy" onerror="this.src='{{ asset('images/noimage.svg') }}'">
              </td>
              <td style="padding:10px 16px;">
                @if($showKey !== null)
                  <a href="{{ \Route::has('front.company.show') ? route('front.company.show', $showKey) : url('/company/'.$showKey) }}"
                     style="color:#4f46e5; text-decoration:none;">
                    {{ $name }}
                  </a>
                @else
                  {{ $name }}
                @endif
              </td>
              <td style="padding:10px 16px;">{{ $slug ?? '-' }}</td>
              <td style="padding:10px 16px;">{{ $updatedText }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection
