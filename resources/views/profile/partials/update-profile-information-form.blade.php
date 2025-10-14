{{-- PROFILE_FORM_V3 --}}
@php
  $p = $user->profile;

  // 都道府県
  $prefectures = ['北海道','青森県','岩手県','宮城県','秋田県','山形県','福島県','茨城県','栃木県','群馬県','埼玉県','千葉県','東京都','神奈川県','新潟県','富山県','石川県','福井県','山梨県','長野県','岐阜県','静岡県','愛知県','三重県','滋賀県','京都府','大阪府','兵庫県','奈良県','和歌山県','鳥取県','島根県','岡山県','広島県','山口県','徳島県','香川県','愛媛県','高知県','福岡県','佐賀県','長崎県','熊本県','大分県','宮崎県','鹿児島県','沖縄県'];

  // マスタ（必要に応じて管理画面化してOK）
  $positionsMaster = ['デザイナー','ディレクター','エンジニア','PM','マーケ','編集','営業'];
  $locationsMaster = ['東京','神奈川','千葉','埼玉','大阪','京都','兵庫','福岡','リモート'];
  $empTypesMaster  = ['正社員','契約社員','業務委託','アルバイト','インターン'];

  // 事前計算
  $gValue        = old('gender', optional($p)->gender ?? 'no_answer');
  $birthdayObj   = optional(optional($p)->birthday);
  $birthdayValue = old('birthday', $birthdayObj ? $birthdayObj->format('Y-m-d') : '');
  $prefValue     = old('prefecture', optional($p)->prefecture);
  $nameValue     = old('name', $user->name);
  $emailValue    = old('email', $user->email);

  $iv = fn(string $k, $d='') => old($k, $d);

  // JSON 初期値
  $educationsInit = old('educations', $p->educations ?? [
    ['school'=>'','faculty'=>'','department'=>'','period_from'=>'','period_to'=>'','status'=>'在学中']
  ]);
  $worksInit = old('work_histories', $p->work_histories ?? [
    ['company'=>'','from'=>'','to'=>'','employment_type'=>'','dept'=>'','position'=>'','tasks'=>'','achievements'=>'']
  ]);
  $desired = $p->desired ?? [];
@endphp

<section class="grid gap-8">

  {{-- 完成度バー（簡易） --}}
  @isset($progress)
    <div class="mb-2">
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-600">プロフィール完成度</span>
        <span class="text-sm font-semibold">{{ $progress }}%</span>
      </div>
      <progress class="progress progress-success w-full" value="{{ $progress }}" max="100"></progress>
    </div>
  @endisset

  {{-- 基本情報 + 画像 --}}
  <div class="card bg-base-100 shadow-sm">
    <div class="card-body">
      <h2 class="card-title">基本情報</h2>

      <form method="post" action="{{ route('profile.update') }}" class="grid gap-6" enctype="multipart/form-data">
        @csrf @method('patch')

        {{-- アバター --}}
        <div class="grid md:grid-cols-[120px,1fr] items-center gap-4">
          <div class="avatar">
            <div class="w-24 rounded-full ring ring-base-300">
              <img src="{{ $p?->avatar_path ? asset('storage/'.$p->avatar_path) : 'https://placehold.co/120x120?text=Avatar' }}" alt="avatar">
            </div>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text">プロフィール画像</span></label>
            <input type="file" name="avatar" class="file-input file-input-bordered w-full max-w-md" accept="image/*">
            <p class="text-xs text-gray-500 mt-1">JPG/PNG/WEBP 5MBまで</p>
            @error('avatar')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
          </div>
        </div>

        {{-- users: name/email --}}
        <div class="grid md:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text">表示名</span></label>
            <input name="name" type="text" value="{{ $nameValue }}" class="input input-bordered w-full" required />
            @error('name')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text">メールアドレス</span></label>
            <input name="email" type="email" value="{{ $emailValue }}" class="input input-bordered w-full" required />
            @error('email')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
          </div>
        </div>

        {{-- 氏名/カナ --}}
        <div class="grid md:grid-cols-2 gap-4">
          <div class="form-control"><label class="label"><span class="label-text">姓</span></label>
            <input name="last_name" value="{{ $iv('last_name', $p->last_name ?? '') }}" class="input input-bordered" />
          </div>
          <div class="form-control"><label class="label"><span class="label-text">名</span></label>
            <input name="first_name" value="{{ $iv('first_name', $p->first_name ?? '') }}" class="input input-bordered" />
          </div>
          <div class="form-control"><label class="label"><span class="label-text">セイ</span></label>
            <input name="last_name_kana" value="{{ $iv('last_name_kana', $p->last_name_kana ?? '') }}" class="input input-bordered" />
          </div>
          <div class="form-control"><label class="label"><span class="label-text">メイ</span></label>
            <input name="first_name_kana" value="{{ $iv('first_name_kana', $p->first_name_kana ?? '') }}" class="input input-bordered" />
          </div>
        </div>

        {{-- 性別/誕生日/電話 --}}
        <div class="grid md:grid-cols-3 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text">性別</span></label>
            <select name="gender" class="select select-bordered">
              <option value="no_answer" @selected($gValue==='no_answer')>未回答</option>
              <option value="male" @selected($gValue==='male')>男性</option>
              <option value="female" @selected($gValue==='female')>女性</option>
              <option value="other" @selected($gValue==='other')>その他</option>
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text">生年月日</span></label>
            <input name="birthday" type="date" value="{{ $birthdayValue }}" class="input input-bordered" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text">電話番号</span></label>
            <input name="phone" value="{{ $iv('phone', $p->phone ?? '') }}" class="input input-bordered" />
          </div>
        </div>

        {{-- 住所 --}}
        <div class="grid md:grid-cols-3 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text">郵便番号</span></label>
            <input name="postal_code" value="{{ $iv('postal_code', $p->postal_code ?? '') }}" class="input input-bordered" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text">都道府県</span></label>
            <select name="prefecture" class="select select-bordered">
              <option value="">選択してください</option>
              @foreach($prefectures as $pref)
                <option value="{{ $pref }}" @selected($prefValue === $pref)>{{ $pref }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text">市区町村</span></label>
            <input name="city" value="{{ $iv('city', $p->city ?? '') }}" class="input input-bordered" />
          </div>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
          <div class="form-control"><label class="label"><span class="label-text">番地・建物</span></label>
            <input name="address1" value="{{ $iv('address1', $p->address1 ?? '') }}" class="input input-bordered" />
          </div>
          <div class="form-control"><label class="label"><span class="label-text">建物名・部屋番号</span></label>
            <input name="address2" value="{{ $iv('address2', $p->address2 ?? '') }}" class="input input-bordered" />
          </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
          <div class="form-control"><label class="label"><span class="label-text">最寄り駅</span></label>
            <input name="nearest_station" value="{{ $iv('nearest_station', $p->nearest_station ?? '') }}" class="input input-bordered" />
          </div>
          <div class="form-control"><label class="label"><span class="label-text">ポートフォリオURL</span></label>
            <input name="portfolio_url" type="url" value="{{ $iv('portfolio_url', $p->portfolio_url ?? '') }}" class="input input-bordered" />
          </div>
          <div class="form-control"><label class="label"><span class="label-text">Webサイト</span></label>
            <input name="website_url" type="url" value="{{ $iv('website_url', $p->website_url ?? '') }}" class="input input-bordered" />
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
          <div class="form-control"><label class="label"><span class="label-text">X（旧Twitter）</span></label>
            <input name="sns_x" value="{{ $iv('sns_x', $p->sns_x ?? $p->x_url ?? '') }}" class="input input-bordered" />
          </div>
          <div class="form-control"><label class="label"><span class="label-text">Instagram</span></label>
            <input name="sns_instagram" value="{{ $iv('sns_instagram', $p->sns_instagram ?? $p->instagram_url ?? '') }}" class="input input-bordered" />
          </div>
        </div>

        <div class="form-control">
          <label class="label"><span class="label-text">自己紹介 / PR</span></label>
          <textarea name="bio" rows="4" class="textarea textarea-bordered">{{ $iv('bio', $p->bio ?? '') }}</textarea>
        </div>

        <div class="card-actions justify-end">
          <button class="btn btn-primary">保存</button>
          @if (session('status') === 'profile-updated')
            <span class="text-sm text-gray-500">Saved.</span>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- 学歴（既存） --}}
  <div class="card bg-base-100 shadow-sm" x-data="eduForm(@js($educationsInit))">
    <div class="card-body">
      <h2 class="card-title">学歴</h2>
      <form method="post" action="{{ route('profile.update') }}">
        @csrf @method('patch')
        <template x-for="(row, i) in rows" :key="i">
          <div class="grid md:grid-cols-5 gap-4 mb-2">
            <div class="form-control md:col-span-2">
              <label class="label"><span class="label-text">学校名</span></label>
              <input class="input input-bordered" :name="`educations[${i}][school]`" x-model="row.school">
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">学部</span></label>
              <input class="input input-bordered" :name="`educations[${i}][faculty]`" x-model="row.faculty">
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">学科</span></label>
              <input class="input input-bordered" :name="`educations[${i}][department]`" x-model="row.department">
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">在籍状況</span></label>
              <select class="select select-bordered" :name="`educations[${i}][status]`" x-model="row.status">
                <option>在学中</option><option>卒業</option><option>中退</option>
              </select>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">入学</span></label>
              <input type="date" class="input input-bordered" :name="`educations[${i}][period_from]`" x-model="row.period_from">
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">卒業</span></label>
              <input type="date" class="input input-bordered" :name="`educations[${i}][period_to]`" x-model="row.period_to">
            </div>
            <div class="md:col-span-5 flex justify-end">
              <button type="button" class="btn btn-ghost btn-sm" @click="remove(i)" x-show="rows.length>1">削除</button>
            </div>
            <div class="divider md:col-span-5"></div>
          </div>
        </template>
        <div class="card-actions justify-between">
          <button type="button" class="btn btn-outline btn-sm" @click="add()">＋ 行を追加</button>
          <button class="btn btn-primary">学歴を保存</button>
        </div>
      </form>
    </div>
  </div>

  {{-- 職歴（既存） --}}
  <div class="card bg-base-100 shadow-sm" x-data="workForm(@js($worksInit))">
    <div class="card-body">
      <h2 class="card-title">職歴</h2>
      <form method="post" action="{{ route('profile.update') }}">
        @csrf @method('patch')
        <template x-for="(row, i) in rows" :key="i">
          <div class="grid gap-4 mb-2">
            <div class="grid md:grid-cols-4 gap-4">
              <div class="form-control md:col-span-2">
                <label class="label"><span class="label-text">会社名</span></label>
                <input class="input input-bordered" :name="`work_histories[${i}][company]`" x-model="row.company">
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text">入社</span></label>
                <input type="date" class="input input-bordered" :name="`work_histories[${i}][from]`" x-model="row.from">
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text">退社</span></label>
                <input type="date" class="input input-bordered" :name="`work_histories[${i}][to]`" x-model="row.to">
              </div>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
              <div class="form-control">
                <label class="label"><span class="label-text">雇用形態</span></label>
                <input class="input input-bordered" :name="`work_histories[${i}][employment_type]`" x-model="row.employment_type">
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text">部署</span></label>
                <input class="input input-bordered" :name="`work_histories[${i}][dept]`" x-model="row.dept">
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text">役職</span></label>
                <input class="input input-bordered" :name="`work_histories[${i}][position]`" x-model="row.position">
              </div>
            </div>

            <div class="form-control">
              <label class="label"><span class="label-text">担当業務</span></label>
              <textarea class="textarea textarea-bordered" rows="3" :name="`work_histories[${i}][tasks]`" x-model="row.tasks"></textarea>
            </div>

            <div class="form-control">
              <label class="label"><span class="label-text">実績</span></label>
              <textarea class="textarea textarea-bordered" rows="3" :name="`work_histories[${i}][achievements]`" x-model="row.achievements"></textarea>
            </div>

            <div class="flex justify-end">
              <button type="button" class="btn btn-ghost btn-sm" @click="remove(i)" x-show="rows.length>1">削除</button>
            </div>
            <div class="divider"></div>
          </div>
        </template>
        <div class="card-actions justify-between">
          <button type="button" class="btn btn-outline btn-sm" @click="add()">＋ 行を追加</button>
          <button class="btn btn-primary">職歴を保存</button>
        </div>
      </form>
    </div>
  </div>

  {{-- 希望条件（チェックボックス + 第一/第二 + 希望時期） --}}
  <div class="card bg-base-100 shadow-sm">
    <div class="card-body">
      <h2 class="card-title">希望条件</h2>

      <form method="post" action="{{ route('profile.update') }}" class="grid gap-6">
        @csrf @method('patch')

        {{-- 職種（複数） --}}
        <div>
          <label class="label"><span class="label-text font-semibold">希望職種（複数選択可）</span></label>
          <div class="flex flex-wrap gap-3">
            @php $selected = old('desired.positions', data_get($desired,'positions',[])); @endphp
            @foreach($positionsMaster as $opt)
              <label class="label cursor-pointer gap-2">
                <input type="checkbox" class="checkbox" name="desired[positions][]" value="{{ $opt }}" @checked(in_array($opt,$selected))>
                <span class="label-text">{{ $opt }}</span>
              </label>
            @endforeach
          </div>
        </div>

        {{-- 勤務地（複数） --}}
        <div>
          <label class="label"><span class="label-text font-semibold">希望勤務地（複数選択可）</span></label>
          <div class="flex flex-wrap gap-3">
            @php $selected = old('desired.locations', data_get($desired,'locations',[])); @endphp
            @foreach($locationsMaster as $opt)
              <label class="label cursor-pointer gap-2">
                <input type="checkbox" class="checkbox" name="desired[locations][]" value="{{ $opt }}" @checked(in_array($opt,$selected))>
                <span class="label-text">{{ $opt }}</span>
              </label>
            @endforeach
          </div>
        </div>

        {{-- 雇用形態（複数） --}}
        <div>
          <label class="label"><span class="label-text font-semibold">希望雇用形態（複数選択可）</span></label>
          <div class="flex flex-wrap gap-3">
            @php $selected = old('desired.employment_types', data_get($desired,'employment_types',[])); @endphp
            @foreach($empTypesMaster as $opt)
              <label class="label cursor-pointer gap-2">
                <input type="checkbox" class="checkbox" name="desired[employment_types][]" value="{{ $opt }}" @checked(in_array($opt,$selected))>
                <span class="label-text">{{ $opt }}</span>
              </label>
            @endforeach
          </div>
        </div>

        {{-- 第一/第二希望 --}}
        <div class="grid md:grid-cols-2 gap-4">
          <div class="grid gap-2">
            <span class="font-semibold">第一希望</span>
            <select name="desired[first_choice][position]" class="select select-bordered">
              <option value="">職種を選択</option>
              @foreach($positionsMaster as $opt)
                <option value="{{ $opt }}" @selected(old('desired.first_choice.position', data_get($desired,'first_choice.position'))===$opt)>{{ $opt }}</option>
              @endforeach
            </select>
            <select name="desired[first_choice][location]" class="select select-bordered">
              <option value="">勤務地を選択</option>
              @foreach($locationsMaster as $opt)
                <option value="{{ $opt }}" @selected(old('desired.first_choice.location', data_get($desired,'first_choice.location'))===$opt)>{{ $opt }}</option>
              @endforeach
            </select>
          </div>

          <div class="grid gap-2">
            <span class="font-semibold">第二希望</span>
            <select name="desired[second_choice][position]" class="select select-bordered">
              <option value="">職種を選択</option>
              @foreach($positionsMaster as $opt)
                <option value="{{ $opt }}" @selected(old('desired.second_choice.position', data_get($desired,'second_choice.position'))===$opt)>{{ $opt }}</option>
              @endforeach
            </select>
            <select name="desired[second_choice][location]" class="select select-bordered">
              <option value="">勤務地を選択</option>
              @foreach($locationsMaster as $opt)
                <option value="{{ $opt }}" @selected(old('desired.second_choice.location', data_get($desired,'second_choice.location'))===$opt)>{{ $opt }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- 希望時期 / 就業可能日 / 年収 / 備考 --}}
        <div class="grid md:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text">希望時期</span></label>
            @php $hope = old('desired.hope_timing', data_get($desired,'hope_timing')); @endphp
            <select name="desired[hope_timing]" class="select select-bordered">
              <option value="">選択してください</option>
              @foreach(['即日','1ヶ月以内','3ヶ月以内','応相談'] as $opt)
                <option value="{{ $opt }}" @selected($hope===$opt)>{{ $opt }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text">就業可能日</span></label>
            <input type="date" class="input input-bordered" name="desired[available_from]" value="{{ old('desired.available_from', data_get($desired,'available_from')) }}">
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label"><span class="label-text">希望年収（万円）</span></label>
            <input type="number" min="0" step="10" class="input input-bordered" name="desired[salary_min]" value="{{ old('desired.salary_min', data_get($desired,'salary_min')) }}">
          </div>
        </div>

        <div class="form-control">
          <label class="label"><span class="label-text">備考</span></label>
          <textarea class="textarea textarea-bordered" rows="3" name="desired[remarks]">{{ old('desired.remarks', data_get($desired,'remarks')) }}</textarea>
        </div>

        <div class="card-actions justify-end">
          <button class="btn btn-primary">希望条件を保存</button>
        </div>
      </form>
    </div>
  </div>
</section>

{{-- Alpine ヘルパ（学歴/職歴のみ。希望条件は純粋フォーム送信） --}}
<script>
  function eduForm(initial){ return {
    rows: (Array.isArray(initial) && initial.length) ? initial : [{school:'',faculty:'',department:'',period_from:'',period_to:'',status:'在学中'}],
    add(){ this.rows.push({school:'',faculty:'',department:'',period_from:'',period_to:'',status:'在学中'}) },
    remove(i){ this.rows.splice(i,1) },
  }}
  function workForm(initial){ return {
    rows: (Array.isArray(initial) && initial.length) ? initial : [{company:'',from:'',to:'',employment_type:'',dept:'',position:'',tasks:'',achievements:''}],
    add(){ this.rows.push({company:'',from:'',to:'',employment_type:'',dept:'',position:'',tasks:'',achievements:''}) },
    remove(i){ this.rows.splice(i,1) },
  }}
</script>
