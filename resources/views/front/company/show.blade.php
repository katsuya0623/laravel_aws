{{-- resources/views/front/company/show.blade.php --}}
@extends('front.layout')

@section('title', ($company->name ?? '企業'))

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">

  {{-- 一覧に戻る --}}
  <a href="{{ (Illuminate\Support\Facades\Route::has('front.company.index') ? route('front.company.index') : url('/company')) }}"
    class="text-sm text-indigo-600 hover:underline">&larr; 企業一覧へ</a>

 {{-- 会社名（企業プロフィールを正） --}}
<h1 class="mt-3 text-3xl font-bold">
  {{ $profile->company_name ?? $company->name ?? '企業名' }}
</h1>


  {{-- カナ（company_profiles.company_name_kana） --}}
  @if(!empty($profile?->company_name_kana))
  <p class="mt-1 text-sm text-gray-600">
    {{ $profile->company_name_kana }}
  </p>
  @endif

  {{-- ロゴ --}}
  <div class="mt-4">
    <img
      src="{{ $logoUrl }}"
      alt="{{ $company->name }} のロゴ"
      class="h-16 w-auto object-contain"
      loading="lazy">
  </div>

{{-- 事業内容 / 紹介 --}}
@if(!empty($profile?->description))

  <style>
    /* 企業詳細：WYSIWYG表示（このページ限定） */
    .company-desc ol {
      list-style: decimal !important;
      padding-left: 1.5rem !important;
      margin: 0.75rem 0 !important;
    }
    .company-desc ul {
      list-style: disc !important;
      padding-left: 1.5rem !important;
      margin: 0.75rem 0 !important;
    }
    .company-desc li {
      margin: 0.25rem 0 !important;
    }
    .company-desc strong,
    .company-desc b {
      font-weight: 700 !important;
    }
    .company-desc h2 {
      font-size: 1.25rem !important;
      font-weight: 700 !important;
      margin: 1rem 0 0.5rem !important;
    }
    .company-desc h3 {
      font-size: 1.1rem !important;
      font-weight: 700 !important;
      margin: 0.75rem 0 0.5rem !important;
    }
    .company-desc p {
      margin: 0.5rem 0 !important;
    }
    .company-desc a {
      color: #4f46e5 !important; /* indigo-600相当 */
      text-decoration: underline !important;
    }
    .company-desc blockquote {
      border-left: 4px solid #e5e7eb !important;
      padding-left: 1rem !important;
      color: #374151 !important;
      margin: 0.75rem 0 !important;
    }
  </style>

  <div class="company-desc mt-6">
    {!! $profile->description !!}
  </div>
@endif




  {{-- 企業情報 詳細 --}}
  <div class="mt-8 border-t border-gray-200 pt-6">
    <h2 class="text-xl font-semibold mb-4">企業情報</h2>

    <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2 text-sm">

      {{-- Webサイト --}}
      <div>
        <dt class="text-gray-500 text-xs font-semibold">Webサイト</dt>
        <dd class="mt-1">
          @if(!empty($profile?->website_url))
          <a href="{{ $profile->website_url }}"
            target="_blank"
            rel="noopener"
            class="text-indigo-600 hover:underline">
            {{ $profile->website_url }}
          </a>
          @else
          ー
          @endif
        </dd>
      </div>

      {{-- 代表メール --}}
      <div>
        <dt class="text-gray-500 text-xs font-semibold">代表メール</dt>
        <dd class="mt-1 text-gray-900">{{ $profile->email ?? 'ー' }}</dd>
      </div>

      {{-- 電話番号 --}}
      <div>
        <dt class="text-gray-500 text-xs font-semibold">電話番号</dt>
        <dd class="mt-1 text-gray-900">{{ $profile->tel ?? 'ー' }}</dd>
      </div>

      {{-- 所在地 --}}
      <div class="sm:col-span-2">
        <dt class="text-gray-500 text-xs font-semibold">所在地</dt>
        <dd class="mt-1 text-gray-900">
          @if(!empty($profile?->postal_code))
          〒{{ $profile->postal_code }}<br>
          @endif
          {{ $profile->prefecture ?? '' }}
          {{ $profile->city ?? '' }}
          {{ $profile->address1 ?? '' }}
          {{ $profile->address2 ?? '' }}
        </dd>
      </div>

      {{-- 業種 --}}
      <div>
        <dt class="text-gray-500 text-xs font-semibold">業種</dt>
        <dd class="mt-1 text-gray-900">{{ $profile->industry ?? 'ー' }}</dd>
      </div>

      {{-- 従業員数 --}}
      <div>
        <dt class="text-gray-500 text-xs font-semibold">従業員数</dt>
        <dd class="mt-1 text-gray-900">
          {{ !empty($profile?->employees) ? $profile->employees.'名' : 'ー' }}
        </dd>
      </div>

      {{-- 設立日 --}}
      <div>
        <dt class="text-gray-500 text-xs font-semibold">設立日</dt>
        <dd class="mt-1 text-gray-900">
          @if(!empty($profile?->founded_on))
          {{ \Illuminate\Support\Carbon::parse($profile->founded_on)->format('Y年n月j日') }}
          @else
          ー
          @endif
        </dd>
      </div>

    </dl>
  </div>

  {{-- 募集中の求人（あればそのまま） --}}
  @if(!empty($jobs) && count($jobs))
  <h2 class="mt-10 mb-4 text-xl font-semibold">募集中の求人</h2>
  <ul class="space-y-3">
    @foreach($jobs as $job)
    <li>
      <a href="{{ route('front.jobs.show', ['slugOrId' => $job->id]) }}"
        class="text-indigo-700 hover:underline font-medium">
        {{ $job->title ?? ('求人ID: '.$job->id) }}
      </a>
    </li>
    @endforeach
  </ul>
  @endif

</div>
@endsection