
@extends('front.layout')

@section('title','求人一覧')



@section('toolbar')

  <form method="GET" action="{{ route('front.jobs.index') }}" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">

    <input type="search" name="q" value="{{ $q ?? '' }}" placeholder="キーワード（スペースでAND）"

           style="flex:1; min-width:240px; padding:6px 10px; border:1px solid #e5e7eb; border-radius:8px;">

    <select name="status" style="padding:6px 10px; border:1px solid #e5e7eb; border-radius:8px;">

      <option value="">すべて</option>

      <option value="published" @selected(($status ?? '')==='published')>published</option>

      <option value="draft" @selected(($status ?? '')==='draft')>draft</option>

    </select>

    <button style="padding:6px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#f9fafb;">検索</button>

    <a href="{{ route('front.jobs.index') }}" style="padding:6px 12px; border:1px solid #e5e7eb; border-radius:8px;">クリア</a>

    @auth

      <a href="{{ route('admin.jobs.index') }}" style="margin-left:8px; padding:6px 12px; background:#111827; color:#fff; border-radius:8px; text-decoration:none;">

        管理の「求人一覧」へ

      </a>

    @endauth

  </form>

@endsection



@section('content')

  <h1 style="font-size:20px; font-weight:700; margin:0 0 12px;">求人一覧</h1>



  @if(isset($jobs) && $jobs->count() === 0)

    <p style="color:#6b7280;">該当する求人はありません。</p>

  @elseif(isset($jobs))

    <ul style="list-style:none; margin:0; padding:0; display:grid; gap:12px;">

      @foreach($jobs as $job)

        @php

          $statusText = $job->status ?? '';

          $slug       = $job->slug ?? $job->id;

          $detailUrl  = url('/jobs/'.$slug); // 詳細は後で実装

        @endphp



        <li style="border:1px solid #e5e7eb; border-radius:10px; padding:12px;">

          <div style="display:flex; gap:12px; align-items:flex-start;">

            <div style="width:72px; height:72px; border:1px solid #e5e7eb; border-radius:8px; display:grid; place-items:center; background:#f8fafc; flex-shrink:0;">

              <span style="color:#94a3b8; font-size:12px;">NO IMAGE</span>

            </div>

            <div style="flex:1;">

              <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">

                <a href="{{ $detailUrl }}" style="font-weight:700; color:#111827; text-decoration:none;">

                  {{ $job->title ?? '(無題)' }}

                </a>

                @if($statusText !== '')

                  <span style="padding:2px 8px; font-size:12px; border:1px solid #e5e7eb; border-radius:999px; background:#f9fafb;">

                    {{ $statusText }}

                  </span>

                @endif

              </div>

              <div style="margin-top:6px; color:#6b7280; font-size:12px;">

                <span>slug: /jobs/{{ $slug }}</span>

              </div>

              @auth

                <div style="margin-top:6px; font-size:12px;">

                  <a href="{{ route('admin.jobs.index') }}" style="color:#4f46e5;">（管理）求人一覧へ</a>

                </div>

              @endauth

            </div>

          </div>

        </li>

      @endforeach

    </ul>

    <div style="margin-top:16px;">

      {{ $jobs->links() }}

    </div>

  @endif

@endsection

