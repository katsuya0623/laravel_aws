@extends('front.layout')
@section('title','nibiへようこそ')
@section('content')

  {{-- 最新記事 --}}
  @if(isset($latestArticles))
  <section class="mb-12">
    <div class="flex items-baseline justify-between mb-4">
      <h2 class="text-xl font-bold">最新記事</h2>
      <a href="{{ route('front.articles.index', [], false) }}" class="text-indigo-600 text-sm">記事一覧へ</a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      @forelse($latestArticles as $a)
        <a href="{{ route('front.articles.show', $a->slug, false) }}" class="block bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
          <div class="aspect-[16/9] bg-slate-100">
            @if(!empty($a->thumbnail_path)) <img src="{{ asset($a->thumbnail_path) }}" class="w-full h-full object-cover">@endif
          </div>
          <div class="p-4">
            <p class="text-xs text-slate-500">{{ optional($a->published_at)->format('Y/m/d') }}</p>
            <h3 class="font-semibold line-clamp-2 mt-1">{{ $a->title }}</h3>
          </div>
        </a>
      @empty
        <p class="text-sm text-slate-500">記事はまだありません。</p>
      @endforelse
    </div>
  </section>
  @endif

  {{-- 企業（新着/注目） --}}
  <section class="mb-12">
    <div class="flex items-baseline justify-between mb-4">
      <h2 class="text-xl font-bold">企業</h2>
      <a href="{{ route('front.company.index', [], false) }}" class="text-indigo-600 text-sm">企業一覧へ</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
      @forelse($featuredCompanies as $c)
        <a href="{{ route('front.company.show', $c->slug, false) }}" class="block bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
          <div class="aspect-square bg-slate-100 flex items-center justify-center">
            @if(!empty($c->logo_path)) <img src="{{ asset($c->logo_path) }}" class="max-h-16 object-contain">@else
              <span class="text-xs text-slate-400">no logo</span>
            @endif
          </div>
          <div class="p-3">
            <h3 class="text-sm font-semibold line-clamp-2">{{ $c->name }}</h3>
            @if(!empty($c->location))<p class="text-[11px] text-slate-500 mt-1">{{ $c->location }}</p>@endif
          </div>
        </a>
      @empty
        <p class="text-sm text-slate-500 col-span-full">企業データは準備中です。</p>
      @endforelse
    </div>
  </section>

  {{-- 求人（新着） --}}
  <section class="mb-4">
    <div class="flex items-baseline justify-between mb-4">
      <h2 class="text-xl font-bold">求人</h2>
      <a href="{{ route('front.jobs.index', [], false) }}" class="text-indigo-600 text-sm">求人一覧へ</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      @forelse($latestJobs as $j)
        <a href="{{ route('front.jobs.show', $j->slug, false) }}" class="block bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
          <div class="aspect-[16/9] bg-slate-100">
            @if(!empty($j->thumbnail_path)) <img src="{{ asset($j->thumbnail_path) }}" class="w-full h-full object-cover">@endif
          </div>
          <div class="p-4">
            <h3 class="font-semibold line-clamp-2">{{ $j->title }}</h3>
            <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-slate-600">
              @if(!empty($j->location))        <span class="px-2 py-0.5 bg-slate-100 rounded">{{ $j->location }}</span>@endif
              @if(!empty($j->employment_type)) <span class="px-2 py-0.5 bg-slate-100 rounded">{{ $j->employment_type }}</span>@endif
              @if(!empty($j->salary_label))    <span class="px-2 py-0.5 bg-slate-100 rounded">{{ $j->salary_label }}</span>@endif
            </div>
            @if(!empty($j->published_at))
              <p class="text-xs text-slate-500 mt-2">{{ optional($j->published_at)->format('Y/m/d') }} 公開</p>
            @endif
          </div>
        </a>
      @empty
        <p class="text-sm text-slate-500">求人データは準備中です。</p>
      @endforelse
    </div>
  </section>

@endsection
