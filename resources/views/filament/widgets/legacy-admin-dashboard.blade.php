<x-filament::section>
    <x-slot name="heading">🚀 ダッシュボード</x-slot>

    <div class="mb-6 rounded-2xl bg-gradient-to-r from-indigo-500 via-sky-500 to-cyan-400 p-1">
        <div class="rounded-2xl bg-white/70 p-4 backdrop-blur"></div>
    </div>

    @php
        $isAdmin = auth('admin')->check()
            || (method_exists(auth()->user() ?? null,'hasRole') && auth()->user()->hasRole('admin'));

        // すべて固定URL（route() や Route::has() を使わないので安全）
        $postsUrl      = url('/admin/posts');
        $endUsersUrl   = url('/admin/users');
        $companiesUrl  = url('/admin/companies');
        $jobsUrl       = url('/admin/recruit_jobs');   // web.php 側で Filament に転送
        $adminsUrl     = url('/admin/admins');
    @endphp

    @if ($isAdmin)
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">

            <a href="{{ $postsUrl }}"
               class="group rounded-2xl border-2 border-indigo-200 bg-white p-7 shadow transition hover:-translate-y-0.5 hover:shadow-lg hover:border-indigo-400">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-700">📄</div>
                    <div class="min-w-0">
                        <h3 class="font-semibold">記事一覧</h3>
                        <p class="text-sm text-gray-600">公開・下書き・編集管理</p>
                    </div>
                </div>
                <div class="mt-4 text-right text-xs text-indigo-500">→</div>
            </a>

            <a href="{{ $endUsersUrl }}"
               class="group rounded-2xl border-2 border-indigo-200 bg-white p-7 shadow transition hover:-translate-y-0.5 hover:shadow-lg hover:border-indigo-400">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-700">👤</div>
                    <div class="min-w-0">
                        <h3 class="font-semibold">エンドユーザー管理</h3>
                        <p class="text-sm text-gray-600">エンドユーザーの管理</p>
                    </div>
                </div>
                <div class="mt-4 text-right text-xs text-indigo-500">→</div>
            </a>

            <a href="{{ $companiesUrl }}"
               class="group rounded-2xl border-2 border-indigo-200 bg-white p-7 shadow transition hover:-translate-y-0.5 hover:shadow-lg hover:border-indigo-400">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-700">🏢</div>
                    <div class="min-w-0">
                        <h3 class="font-semibold">企業一覧</h3>
                        <p class="text-sm text-gray-600">企業プロフィールの管理</p>
                    </div>
                </div>
                <div class="mt-4 text-right text-xs text-indigo-500">→</div>
            </a>

            <a href="{{ $jobsUrl }}"
               class="group rounded-2xl border-2 border-indigo-200 bg-white p-7 shadow transition hover:-translate-y-0.5 hover:shadow-lg hover:border-indigo-400">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-700">💼</div>
                    <div class="min-w-0">
                        <h3 class="font-semibold">求人一覧</h3>
                        <p class="text-sm text-gray-600">作成・編集・公開管理</p>
                    </div>
                </div>
                <div class="mt-4 text-right text-xs text-indigo-500">→</div>
            </a>

            <a href="{{ $adminsUrl }}"
               class="group rounded-2xl border-2 border-indigo-200 bg-white p-7 shadow transition hover:-translate-y-0.5 hover:shadow-lg hover:border-indigo-400">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-700">🛡️</div>
                    <div class="min-w-0">
                        <h3 class="font-semibold">メンバー管理</h3>
                        <p class="text-sm text-gray-600">管理者の一覧・追加</p>
                    </div>
                </div>
                <div class="mt-4 text-right text-xs text-indigo-500">→</div>
            </a>
        </div>
    @else
        <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 p-4">
            権限がありません。
        </div>
    @endif
</x-filament::section>
