@extends('layouts.app')
@section('title', $job->title ?? '求人情報')

@section('content')
<div class="container" style="max-width:920px;margin:24px auto;">
  <h1 style="font-size:24px;margin-bottom:12px;">{{ $job->title ?? '求人タイトル不明' }}</h1>

  <div style="color:#666;margin:8px 0 16px;">
    @if(!empty($job->company))
      <a href="{{ route('front.company.show', $job->company->slug ?? $job->company->id) }}">{{ $job->company->name }}</a>
    @endif
    @if(!empty($job->location)) <span style="margin-left:8px;">{{ $job->location }}</span>@endif
    @if(!empty($job->employment_type)) <span style="margin-left:8px;">{{ $job->employment_type }}</span>@endif
    @if(!empty($job->salary_label)) <span style="margin-left:8px;">{{ $job->salary_label }}</span>@endif
  </div>

  @if(!empty($job->thumbnail_path))
    <div style="margin:8px 0 16px">
      <img src="{{ Storage::url($job->thumbnail_path) }}" alt="thumb" style="max-width:100%;height:auto;border:1px solid #eee;border-radius:6px;">
    </div>
  @endif

  @if(!empty($job->body))
    <article style="line-height:1.9;white-space:pre-wrap;">{!! nl2br(e($job->body)) !!}</article>
  @endif
</div>
@endsection
