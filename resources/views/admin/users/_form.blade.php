@csrf
<div class="mb-4">
  <x-input-label for="name" value="名前" />
  <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
    :value="old('name', $user->name)" required />
  <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mb-4">
  <x-input-label for="email" value="メール" />
  <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
    :value="old('email', $user->email)" required />
  <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<div class="mb-4">
  <x-input-label for="password" value="パスワード（未入力なら自動ランダム）" />
  <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
  <x-input-error :messages="$errors->get('password')" class="mt-2" />
</div>

<div class="mb-4 flex items-center space-x-6">
  <label class="inline-flex items-center">
    <input type="hidden" name="is_admin" value="0">
    <input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user->is_admin)) class="mr-2">
    管理者
  </label>
  <label class="inline-flex items-center">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="mr-2">
    有効化
  </label>
</div>
