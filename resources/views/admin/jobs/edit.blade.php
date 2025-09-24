<!doctype html>
<meta charset="utf-8">
<title>求人を編集</title>
<script src="https://cdn.tailwindcss.com"></script>
<body class="bg-gray-50 p-6">
  <div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold">求人を編集 #{{ $job->id }}</h1>
      <a href="{{ route('admin.jobs.index') }}" class="text-sm text-slate-600 hover:underline">一覧へ戻る</a>
    </div>

    @if ($errors->any())
      <div class="mb-4 rounded border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm">
        <ul class="list-disc ml-5">
          @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('admin.jobs.update', $job) }}" class="space-y-6 bg-white rounded-xl p-6 shadow">
      @csrf @method('PUT')

      {{-- 基本 --}}
      <section>
        <h2 class="font-semibold mb-3">基本情報</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">タイトル <span class="text-rose-600">*</span></label>
            <input name="title" value="{{ old('title',$job->title) }}" required class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">スラッグ</label>
            <input name="slug" value="{{ old('slug',$job->slug) }}" class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">ステータス</label>
            <select name="status" class="w-full border rounded p-2">
              @php $st = old('status',$job->status); @endphp
              <option value="" @selected($st==='')>未設定</option>
              <option value="draft" @selected($st==='draft')>draft</option>
              <option value="published" @selected($st==='published')>published</option>
            </select>
          </div>
          <div>
            <label class="block text-sm mb-1">公開日</label>
            <input type="datetime-local" name="published_at"
              value="{{ old('published_at', optional($job->published_at)->format('Y-m-d\TH:i')) }}"
              class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">会社</label>
            <select name="company_id" class="w-full border rounded p-2">
              <option value="">未選択</option>
              @foreach(($companies ?? []) as $id => $name)
                <option value="{{ $id }}" @selected($id==old('company_id',$job->company_id))>{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-sm mb-1">画像URL</label>
            <input name="image_url" value="{{ old('image_url',$job->image_url) }}" class="w-full border rounded p-2">
          </div>
        </div>
        <div class="mt-3">
          <label class="block text-sm mb-1">抜粋</label>
          <textarea name="excerpt" rows="3" class="w-full border rounded p-2">{{ old('excerpt',$job->excerpt) }}</textarea>
        </div>
      </section>

      {{-- 募集情報 --}}
      <section>
        <h2 class="font-semibold mb-3">募集情報</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm mb-1">勤務地</label>
            <input name="location" value="{{ old('location',$job->location) }}" class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">雇用形態</label>
            @php $emp = old('employment_type',$job->employment_type); @endphp
            <select name="employment_type" class="w-full border rounded p-2">
              <option value="">未選択</option>
              @foreach(['fulltime'=>'正社員','parttime'=>'パート/アルバイト','contract'=>'業務委託/契約','intern'=>'インターン','other'=>'その他'] as $k=>$v)
                <option value="{{ $k }}" @selected($emp===$k)>{{ $v }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-sm mb-1">働き方</label>
            @php $ws = old('work_style',$job->work_style); @endphp
            <select name="work_style" class="w-full border rounded p-2">
              <option value="">未選択</option>
              @foreach(['onsite'=>'出社','hybrid'=>'ハイブリッド','remote'=>'フルリモート'] as $k=>$v)
                <option value="{{ $k }}" @selected($ws===$k)>{{ $v }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-sm mb-1">募集人数</label>
            <input type="number" min="1" name="openings" value="{{ old('openings',$job->openings) }}" class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">募集締切</label>
            <input type="date" name="application_deadline" value="{{ old('application_deadline', optional($job->application_deadline)->format('Y-m-d')) }}" class="w-full border rounded p-2">
          </div>
        </div>
      </section>

      {{-- 給与 --}}
      <section>
        <h2 class="font-semibold mb-3">給与</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm mb-1">下限</label>
            <input type="number" min="0" name="salary_min" value="{{ old('salary_min',$job->salary_min) }}" class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">上限</label>
            <input type="number" min="0" name="salary_max" value="{{ old('salary_max',$job->salary_max) }}" class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">単位 / 通貨</label>
            <div class="flex gap-2">
              @php $unit = old('salary_unit', $job->salary_unit ?? 'month'); @endphp
              <select name="salary_unit" class="border rounded p-2 grow">
                @foreach(['month'=>'月','year'=>'年','hour'=>'時'] as $k=>$v)
                  <option value="{{ $k }}" @selected($unit===$k)>{{ $v }}</option>
                @endforeach
              </select>
              <input name="salary_currency" value="{{ old('salary_currency', $job->salary_currency ?? 'JPY') }}" class="border rounded p-2 w-24">
            </div>
          </div>
        </div>
        <div class="mt-3">
          <label class="block text-sm mb-1">給与メモ</label>
          <input name="salary_notes" value="{{ old('salary_notes',$job->salary_notes) }}" class="w-full border rounded p-2">
        </div>
      </section>

      {{-- 応募要件 --}}
      <section>
        <h2 class="font-semibold mb-3">応募要件</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm mb-1">経験年数（最小）</label>
            <input type="number" min="0" name="experience_years_min" value="{{ old('experience_years_min',$job->experience_years_min) }}" class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">経験年数（最大）</label>
            <input type="number" min="0" name="experience_years_max" value="{{ old('experience_years_max',$job->experience_years_max) }}" class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">学歴</label>
            <input name="education" value="{{ old('education',$job->education) }}" class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">言語</label>
            <input name="languages" value="{{ old('languages',$job->languages) }}" class="w-full border rounded p-2">
          </div>
          <div class="flex items-center gap-2">
            <input type="checkbox" name="visa_support" value="1" @checked(old('visa_support',$job->visa_support))>
            <label>ビザサポートあり</label>
          </div>
          <div class="flex items-center gap-2">
            <input type="checkbox" name="relocation_support" value="1" @checked(old('relocation_support',$job->relocation_support))>
            <label>転居支援あり</label>
          </div>
        </div>
      </section>

      {{-- 就業条件 --}}
      <section>
        <h2 class="font-semibold mb-3">就業条件</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">勤務時間</label>
            <input name="work_hours" value="{{ old('work_hours',$job->work_hours) }}" class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">休日</label>
            <input name="holidays" value="{{ old('holidays',$job->holidays) }}" class="w-full border rounded p-2">
          </div>
        </div>
      </section>

      {{-- 詳細テキスト --}}
      <section>
        <h2 class="font-semibold mb-3">詳細</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">業務内容</label>
            <textarea name="responsibilities" rows="5" class="w-full border rounded p-2">{{ old('responsibilities',$job->responsibilities) }}</textarea>
          </div>
          <div>
            <label class="block text-sm mb-1">求めるスキル/経験</label>
            <textarea name="requirements" rows="5" class="w-full border rounded p-2">{{ old('requirements',$job->requirements) }}</textarea>
          </div>
          <div>
            <label class="block text-sm mb-1">福利厚生</label>
            <textarea name="benefits" rows="4" class="w-full border rounded p-2">{{ old('benefits',$job->benefits) }}</textarea>
          </div>
          <div>
            <label class="block text-sm mb-1">選考プロセス</label>
            <textarea name="selection_process" rows="4" class="w-full border rounded p-2">{{ old('selection_process',$job->selection_process) }}</textarea>
          </div>
          <div>
            <label class="block text-sm mb-1">提出書類</label>
            <textarea name="documents_required" rows="4" class="w-full border rounded p-2">{{ old('documents_required',$job->documents_required) }}</textarea>
          </div>
          <div>
            <label class="block text-sm mb-1">技術スタック</label>
            <textarea name="tech_stack" rows="4" class="w-full border rounded p-2">{{ old('tech_stack',$job->tech_stack) }}</textarea>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label class="block text-sm mb-1">タグ（カンマ区切り）</label>
            <input name="tags" value="{{ old('tags',$job->tags) }}" class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm mb-1">外部リンク</label>
            <input name="external_link_url" value="{{ old('external_link_url',$job->external_link_url) }}" class="w-full border rounded p-2">
          </div>
        </div>
      </section>

      <div class="pt-2 flex gap-2">
        <button class="bg-indigo-600 text-white px-4 py-2 rounded">更新</button>
        <a href="{{ route('admin.jobs.index') }}" class="border px-4 py-2 rounded">キャンセル</a>
      </div>
    </form>
  </div>
</body>
