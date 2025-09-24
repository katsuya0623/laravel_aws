@extends('front.layout')
@section('title','企業一覧')

@section('content')
  <div style="max-width:1000px; margin:32px auto; padding:0 16px;">
    <h1 style="font-size:22px; font-weight:700; margin:0 0 16px;">企業一覧</h1>

    @php
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
              <th style="padding:12px 16px; border-bottom:1px solid #e5e7eb;">企業名</th>
              <th style="padding:12px 16px; border-bottom:1px solid #e5e7eb; width:30%;">スラッグ</th>
              <th style="padding:12px 16px; border-bottom:1px solid #e5e7eb; width:180px;">更新日</th>
            </tr>
          </thead>
          <tbody>
            @foreach($list as $c)
              @php
                $id   = is_array($c) ? ($c['id']   ?? null) : ($c->id   ?? null);
                $name = is_array($c) ? ($c['name'] ?? null) : ($c->name ?? null);
                $slug = is_array($c) ? ($c['slug'] ?? null) : ($c->slug ?? null);
                $updated = is_array($c) ? ($c['updated_at'] ?? null) : ($c->updated_at ?? null);

                // slug があればslug、無ければIDへフォールバック
                $param = $slug ?: $id;

                // 更新日の見栄え整形（文字列でもCarbonでもOK）
                $updatedText = '-';
                if ($updated instanceof \Illuminate\Support\Carbon) {
                  $updatedText = $updated->format('Y年n月j日');
                } elseif ($updated) {
                  try { $updatedText = \Illuminate\Support\Carbon::parse($updated)->format('Y年n月j日'); }
                  catch (\Exception $e) { $updatedText = (string)$updated; }
                }
              @endphp

              <tr style="border-top:1px solid #e5e7eb;">
                <td style="padding:10px 16px;">{{ $id }}</td>
                <td style="padding:10px 16px;">
                  @if(!is_null($param))
                    <a href="{{ route('front.company.show', $param) }}" style="color:#4f46e5; text-decoration:none;">
                      {{ $name ?? '(名称未設定)' }}
                    </a>
                  @else
                    {{ $name ?? '(名称未設定)' }}
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
