@extends('front.layout')
@section('title', ($job->title ?? '求人').'への応募')

@section('content')
<div class="max-w-[840px] mx-auto my-8">
  <h1 class="text-2xl font-bold mb-6">{{ $job->title ?? '求人' }} への応募</h1>

  <form method="POST"
        action="{{ route('front.jobs.apply.submit', ['slugOrId'=>$job->slug ?? $job->id]) }}"
        enctype="multipart/form-data"
        class="space-y-6">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="font-semibold">お名前 <span class="text-red-500">*</span></label>
        <input name="name" value="{{ old('name') }}" required class="w-full border rounded p-2">
      </div>
      <div>
        <label class="font-semibold">フリガナ</label>
        <input name="kana" value="{{ old('kana') }}" class="w-full border rounded p-2">
      </div>
      <div>
        <label class="font-semibold">メールアドレス <span class="text-red-500">*</span></label>
        <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded p-2">
      </div>
      <div>
        <label class="font-semibold">電話番号（任意）</label>
        <input name="phone" value="{{ old('phone') }}" class="w-full border rounded p-2">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="font-semibold">現在の状況</label>
        <select name="current_status" class="w-full border rounded p-2">
          <option value="">選択してください</option>
          @foreach (['就業中','離職中','在学中','フリーランス','その他'] as $opt)
            <option value="{{ $opt }}" @selected(old('current_status')===$opt)>{{ $opt }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="font-semibold">希望する雇用形態</label>
        <select name="employment_type" class="w-full border rounded p-2">
          <option value="">選択してください</option>
          @foreach (['正社員','契約社員','業務委託','アルバイト','インターン'] as $opt)
            <option value="{{ $opt }}" @selected(old('employment_type')===$opt)>{{ $opt }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div>
      <label class="font-semibold">志望動機</label>
      <textarea name="motivation" rows="5" class="w-full border rounded p-2">{{ old('motivation') }}</textarea>
    </div>

    <div>
      <label class="font-semibold">自己PR / 自由記述</label>
      <textarea name="pr" rows="6" class="w-full border rounded p-2">{{ old('pr') }}</textarea>
    </div>

    <div>
      <label class="font-semibold">履歴書・職務経歴書など（任意 / PDF, Doc, 画像）</label>
      <input type="file" name="resume" class="block mt-1">
    </div>

    <div class="flex items-start gap-2">
      <input id="agree" type="checkbox" name="agree" value="1" class="mt-1" {{ old('agree') ? 'checked' : '' }} required>
      <label for="agree">個人情報の取り扱いに同意します <span class="text-red-500">*</span></label>
    </div>

    <div class="pt-2 text-center">
      <button class="px-6 py-3 rounded bg-black text-white font-semibold">
        確認して応募する
      </button>
    </div>
  </form>
</div>
@endsection
