@extends('layouts.app')

@section('title', $user->name . ' さんのプロフィール')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- ヘッダー -->
    <div class="border-b bg-white">
        <div class="mx-auto max-w-7xl px-6 py-6">
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $user->name }} さんのプロフィール
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                ユーザーID: {{ $user->id }} / Email: {{ $user->email }}
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 py-8 space-y-8">
        {{-- 基本情報 --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-800">基本情報</h2>
            </div>
            <div class="px-6 py-5">
                <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-gray-500">氏名</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $user->name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Email</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $user->email }}</dd>
                    </div>

                    @if($profile)
                        <div>
                            <dt class="text-sm text-gray-500">希望職種</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $profile->desired_position ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">希望勤務地</dt>
                            <dd class="mt-1 font-medium text-gray-900">{{ $profile->desired_location ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm text-gray-500">自己紹介</dt>
                            <dd class="mt-1 whitespace-pre-line text-gray-900">{{ $profile->bio ?: '—' }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-sm text-gray-500">メール認証</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            {{ $user->email_verified_at ? \Illuminate\Support\Carbon::parse($user->email_verified_at)->toDateTimeString() : '未認証' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">登録日時</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $user->created_at }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- 職歴 --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-800">職歴</h2>
            </div>
            <div class="px-6 py-5 space-y-4">
                @forelse($workHistories as $work)
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="font-semibold text-gray-900">
                                {{ $work->company_name ?: '（会社名未入力）' }}
                                @if($work->position)
                                    <span class="text-gray-500">（{{ $work->position }}）</span>
                                @endif
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ $work->start_date ?: '—' }} 〜
                                {{ $work->is_current ? '現在' : ($work->end_date ?: '—') }}
                            </p>
                        </div>
                        @if($work->description)
                            <p class="mt-2 whitespace-pre-line text-gray-700">{{ $work->description }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">職歴の登録はありません。</p>
                @endforelse
            </div>
        </div>

        {{-- プロフィール編集への導線 --}}
        <div class="flex justify-end">
            <a href="{{ route('profile.edit') }}"
               class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                プロフィールを編集
            </a>
        </div>
    </div>
</div>
@endsection
