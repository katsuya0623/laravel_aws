<x-app-layout>
  <x-slot name="header"><h2 class="font-semibold text-xl">ユーザー追加</h2></x-slot>
  <div class="p-6">
    <form method="POST" action="{{ route('admin.users.store') }}">
      @include('admin.users._form')
      <x-primary-button class="mt-4">作成</x-primary-button>
    </form>
  </div>
</x-app-layout>
