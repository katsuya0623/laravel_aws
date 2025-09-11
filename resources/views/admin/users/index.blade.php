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

            <div class="bg-white shadow sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">名前</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">メール</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">管理者</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">有効</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($users as $u)
                            <tr>
                                <td class="px-4 py-2">{{ $u->id }}</td>
                                <td class="px-4 py-2">{{ $u->name }}</td>
                                <td class="px-4 py-2">{{ $u->email }}</td>
                                <td class="px-4 py-2"><span class="px-2 py-1 rounded text-xs {{ $u->is_admin ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">{{ $u->is_admin ? 'YES' : 'NO' }}</span></td>
                                <td class="px-4 py-2"><span class="px-2 py-1 rounded text-xs {{ $u->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $u->is_active ? 'ON' : 'OFF' }}</span></td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('admin.users.edit',$u) }}" class="text-indigo-600 hover:underline">編集</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-8 text-center text-gray-500" colspan="6">ユーザーがいません</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="p-4">{{ $users->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
