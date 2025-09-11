<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">ユーザー編集</h2>
    </x-slot>

    @if (session('status'))
        <div class="max-w-2xl mx-auto mt-4 text-sm text-green-700 bg-green-100 rounded p-3">
            {{ session('status') }}
        </div>
    @endif

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.users.update',$user) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="名前"/>
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name',$user->name) }}" required/>
                        <x-input-error :messages="$errors->get('name')" class="mt-2"/>
                    </div>

                    <div>
                        <x-input-label for="email" value="メール"/>
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email',$user->email) }}" required/>
                        <x-input-error :messages="$errors->get('email')" class="mt-2"/>
                    </div>

                    <div>
                        <x-input-label for="password" value="パスワード（変更時のみ）"/>
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"/>
                        <x-input-error :messages="$errors->get('password')" class="mt-2"/>
                    </div>

                    <div class="flex gap-6">
                        <label class="inline-flex items-center">
                            <input type="hidden" name="is_admin" value="0">
                            <input type="checkbox" name="is_admin" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_admin',$user->is_admin))>
                            <span class="ml-2">管理者</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_active',$user->is_active))>
                            <span class="ml-2">有効</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <x-primary-button>保存</x-primary-button>

                        <form method="POST" action="{{ route('admin.users.destroy',$user) }}" onsubmit="return confirm('削除しますか？');">
                            @csrf @method('DELETE')
                            <x-danger-button>削除</x-danger-button>
                        </form>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
