@extends('layouts.app')
@section('title','求人一覧')
@section('content')
<div class="container" style="max-width:920px;margin:24px auto;">
  <h1 style="font-size:24px;margin-bottom:12px;">求人一覧</h1>
  @if($jobs->count())
    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:8px;">
      @foreach($jobs as $j)
        <li>
          <a href="{{ route('front.jobs.show', $j->slug ?? $j->id) }}"
             style="display:flex;gap:12px;align-items:center;padding:12px;border:1px solid #eee;border-radius:8px;text-decoration:none;color:inherit;">
             <div style="font-weight:700;">{{ $j->title }}</div>
             <div style="color:#666;margin-left:8px;">
               @if(!empty($j->location)) {{ $j->location }} @endif
               @if(!empty($j->employment_type)) <span style="margin-left:8px;">{{ $j->employment_type }}</span>@endif
             </div>
          </a>
        </li>
      @endforeach
    </ul>
  @else
    <p>求人データは準備中です。</p>
  @endif
</div>
@endsection
