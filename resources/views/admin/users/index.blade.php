<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">ユーザー一覧</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" class="mb-4 flex gap-2">
                <input name="q" value="{{ $q }}" class="border rounded px-3 py-2 w-64" placeholder="名前/メールで検索">
                <x-primary-button>検索</x-primary-button>
            </form>

            @if (session('status'))
                <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
                    @foreach ($errors->all() as $e)
                        <div>{{ $e }}</div>
                    @endforeach
                </div>
            @endif

            @php
                if (!isset($companies)) {
                    $companies = \App\Models\CompanyProfile::orderBy('company_name')->get(['id','company_name']);
                }
            @endphp

            <div class="bg-white shadow sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">名前</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">メール</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">管理者</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">有効</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">役割</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">会社割当</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($users as $u)
                            <tr>
                                <td class="px-4 py-2">{{ $u->id }}</td>
                                <td class="px-4 py-2">{{ $u->name }}</td>
                                <td class="px-4 py-2">{{ $u->email }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded text-xs {{ $u->is_admin ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $u->is_admin ? 'YES' : 'NO' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded text-xs {{ $u->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $u->is_active ? 'ON' : 'OFF' }}
                                    </span>
                                </td>

                                {{-- ▼ 役割変更 --}}
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800 font-medium">
                                            現在: {{ $u->role ?? 'enduser' }}
                                        </span>

                                        {{-- 企業にする --}}
                                        <form method="post" action="{{ route('admin.users.set_role', $u) }}">
                                            @csrf
                                            <input type="hidden" name="role" value="company">
                                            <button
                                                class="text-xs px-3 py-1.5 rounded border bg-gray-100 text-gray-800 hover:bg-gray-200 font-semibold">
                                                企業にする
                                            </button>
                                        </form>

                                        {{-- 一般に戻す --}}
                                        <form method="post" action="{{ route('admin.users.set_role', $u) }}">
                                            @csrf
                                            <input type="hidden" name="role" value="enduser">
                                            <button
                                                class="text-xs px-3 py-1.5 rounded border bg-gray-100 text-gray-800 hover:bg-gray-200">
                                                一般に戻す
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                {{-- ▼ 会社割当 --}}
                                <td class="px-4 py-2">
                                    <div class="flex flex-col gap-2">
                                        <form method="post" action="{{ route('admin.users.assign_company', $u) }}" class="flex items-center gap-2">
                                            @csrf
                                            <select name="company_profile_id" class="border rounded px-2 py-1 text-sm min-w-[220px]">
                                                @foreach($companies as $c)
                                                    <option value="{{ $c->id }}">{{ $c->company_name ?? ('ID:'.$c->id) }}</option>
                                                @endforeach
                                            </select>
                                            <label class="text-xs inline-flex items-center gap-1">
                                                <input type="checkbox" name="set_primary" value="1"> 代表にする
                                            </label>
                                            <button class="text-xs px-3 py-1.5 rounded border bg-gray-100 text-gray-800 hover:bg-gray-200">
                                                割り振り
                                            </button>
                                        </form>

                                        @php
                                            $attachedCompanies = $u->companyProfiles ?? collect();
                                        @endphp
                                        @if($attachedCompanies->count())
                                            <div class="flex items-center gap-2 flex-wrap">
                                                @foreach($attachedCompanies as $ac)
                                                    <form method="post" action="{{ route('admin.users.unassign_company', [$u, $ac]) }}" onsubmit="return confirm('解除しますか？')">
                                                        @csrf @method('delete')
                                                        <button class="text-xs px-3 py-1.5 rounded border text-red-600 hover:bg-red-50">
                                                            解除: {{ $ac->company_name ?? ('ID:'.$ac->id) }}
                                                        </button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500">割当なし</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('admin.users.edit',$u) }}" class="text-indigo-600 hover:underline">編集</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-8 text-center text-gray-500" colspan="8">ユーザーがいません</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="p-4">{{ $users->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
