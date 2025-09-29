{{-- resources/views/front/company/show.blade.php --}}
@extends('layouts.front')

@section('title', ($company->name ?? '企業'))

@section('content')
  <div class="max-w-4xl mx-auto py-10 px-4">
    <a href="{{ (Illuminate\Support\Facades\Route::has('front.company.index')?route('front.company.index'):url('/company')) }}"
       class="text-sm text-indigo-600 hover:underline">&larr; 企業一覧へ</a>

    <h1 class="mt-3 text-3xl font-bold">{{ $company->name ?? '企業名' }}</h1>

    {{-- ロゴ（Controller で解決済みの $logoUrl を使用） --}}
    <div class="mt-4">
      <img
        src="{{ $logoUrl }}"
        alt="{{ $company->name }} のロゴ"
        class="h-16 w-auto object-contain"
        loading="lazy">
    </div>

    @if(!empty($company->description))
      <div class="prose mt-6">
        {!! nl2br(e($company->description)) !!}
      </div>
    @endif

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
