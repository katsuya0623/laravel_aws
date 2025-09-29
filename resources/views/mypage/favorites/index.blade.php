@extends('layouts.front')

@section('title','お気に入り一覧')

@section('content')
  <div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">お気に入り一覧</h1>

    @if($favorites->isEmpty())
      <p class="text-gray-500">お気に入りはまだありません。</p>
    @else
      <ul class="grid gap-4">
        @foreach($favorites as $job)
          <li class="rounded-lg border bg-white p-4 flex items-start justify-between">
            <div>
              <a class="font-semibold hover:underline"
                 href="{{ \Illuminate\Support\Facades\Route::has('front.jobs.show') ? route('front.jobs.show',$job->id) : url('/recruit_jobs/'.$job->id) }}">
                {{ $job->title }}
              </a>
              @if(!empty($job->company->name ?? null))
                <div class="text-sm text-gray-500 mt-1">{{ $job->company->name }}</div>
              @endif
            </div>
            <x-favorite-toggle :job="$job" />
          </li>
        @endforeach
      </ul>
    @endif
  </div>
@endsection

@push('scripts') {{-- componentの@onceでまとめているので何もなくてOK --}} @endpush
