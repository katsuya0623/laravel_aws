<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Middleware\EnsureCompanyProfileCompleted;

// Top / Front
use App\Http\Controllers\Front\LandingController;
use App\Http\Controllers\Front\CompanyController as FCompanyController;
use App\Http\Controllers\Front\ApplicationController;
use App\Http\Controllers\Front\FrontJobController;
use App\Http\Controllers\Front\PostController as FrontPostController;

// Admin
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ApplicationsController;
use App\Http\Controllers\Admin\CompanyUserAssignController;
use App\Http\Controllers\Admin\UserQuickAssignController;
use App\Http\Controllers\Auth\CompanyInviteAcceptController;
use App\Http\Controllers\Auth\NewPasswordController; // ← 追加


// 招待
use App\Http\Controllers\Admin\CompanyInvitationController;

// Auth / Profile
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\WebLoginController;
use App\Http\Controllers\Auth\PasswordController;

// Favorites
use App\Http\Controllers\FavoriteController;

// Users(企業側)
use App\Http\Controllers\Users\ApplicantController;
use App\Http\Controllers\Users\SponsoredArticleController;

// Filament Resources
use App\Filament\Resources\RecruitJobResource;
use App\Filament\Resources\PostResource;

// エンドユーザーメール認証
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\Invites\InviteAcceptController;

// ★ Onboarding / Edit 用（新規）
use App\Http\Controllers\OnboardingCompanyController;
use App\Http\Controllers\CompanyEditController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ===== Register（未ログインのみ） =====
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('guest')->get('/login', [WebLoginController::class, 'create'])->name('login');
Route::middleware('guest')->post('/login', [WebLoginController::class, 'store']);
Route::post('/logout', [WebLoginController::class, 'destroy'])->name('logout');

// ===== Front base =====
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::permanentRedirect('/blog', '/');

// ===== ★ Posts (public) =====
Route::get('/posts', [FrontPostController::class, 'index'])->name('front.posts.index');
Route::get('/posts/{slugOrId}', [FrontPostController::class, 'show'])->name('front.posts.show'); // ← 本命

// ===== Company profile (企業ユーザーのみ：編集専用) =====
Route::middleware(['auth:web', 'role:company'])->group(function () {
    // 2回目以降：編集画面
    Route::get('/company/edit', [CompanyEditController::class, 'edit'])->name('user.company.edit');

    // 更新（/company/edit に POST or PATCH）
    Route::match(['post', 'patch'], '/company/edit', [CompanyEditController::class, 'update'])
        ->name('user.company.update');

    // 互換：旧フォームが /company/update に POST していても動くように
    Route::post('/company/update', [CompanyEditController::class, 'update'])
        ->name('user.company.update.legacy');
});

// ===== Legacy redirects (301) =====
Route::permanentRedirect('/company/company', '/company');
Route::permanentRedirect('/jobs/jobs',       '/jobs');
Route::permanentRedirect('/companys',        '/company');
Route::permanentRedirect('/companies',       '/company');

// 旧 /jobs/{slugOrId} → 新 /recruit_jobs/{slugOrId}
Route::get('/jobs/{slugOrId}', fn(string $slugOrId) => redirect("/recruit_jobs/{$slugOrId}", 301))
    ->where('slugOrId', '^([A-Za-z0-9\-]+|\d+)$');
Route::permanentRedirect('/jobs', '/recruit_jobs');

// ===== ★ ログイン前 intended を明示セット =====
Route::get('/login-intended', function (Request $request) {
    if ($to = $request->query('redirect')) {
        session(['url.intended' => $to]);
    }
    return redirect()->route('login');
})->name('login.intended');

// ===== Password Reset (Breeze互換) =====
Route::middleware('guest')->group(function () {
    // リセット画面（メールの「Reset Password」リンクが来る先）
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    // 新しいパスワードを保存
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});


// ===== ★ 新規登録前 intended を明示セット =====
Route::get('/register-intended', function (Request $request) {
    if ($to = $request->query('redirect')) {
        session(['url.intended' => $to]);
    }
    return redirect()->route('register');
})->name('register.intended');

// ===== Front: Company =====
Route::prefix('company')->name('front.company.')->group(function () {
    Route::get('/', [FCompanyController::class, 'index'])->name('index');
    Route::get('/{slugOrId}', [FCompanyController::class, 'show'])
        ->where('slugOrId', '^([A-Za-z0-9\-]+|\d+)$')
        ->name('show');
});

// ===== Front: Jobs =====
Route::prefix('recruit_jobs')->group(function () {

    // 一覧（公開）
    Route::get('/', [FrontJobController::class, 'index'])->name('front.jobs.index');

    // 企業ユーザー専用（作成・更新系）→ プロフィール未完了なら強制オンボーディング
    Route::middleware(['auth:web', 'role:company', EnsureCompanyProfileCompleted::class])->group(function () {
        Route::get('/create',       [FrontJobController::class, 'create'])->name('front.jobs.create');
        Route::post('/',            [FrontJobController::class, 'store'])->name('front.jobs.store');

        Route::get('/{job}/edit',   [FrontJobController::class, 'edit'])
            ->whereNumber('job')->name('front.jobs.edit');
        Route::patch('/{job}',      [FrontJobController::class, 'update'])
            ->whereNumber('job')->name('front.jobs.update');
        Route::delete('/{job}',     [FrontJobController::class, 'destroy'])
            ->whereNumber('job')->name('front.jobs.destroy');
    });

    // ★お気に入り→応募へ（公開）
    Route::match(['get', 'post'], '/{slugOrId}/favorite-apply', [FavoriteController::class, 'favoriteAndApply'])
        ->where('slugOrId', '^([A-Za-z0-9\-]+|\d+)$')
        ->name('front.jobs.favorite_apply');


    // =========================
    // 新：応募フォーム（別ページ）
    // =========================

    // slug / id どちらでも Job を解決
    Route::bind('job', function ($value) {
        $q = \App\Models\Job::query();
        if (\Illuminate\Support\Facades\Schema::hasColumn((new \App\Models\Job)->getTable(), 'slug')) {
            $q->where('slug', $value);
        }
        return $q->orWhere('id', $value)->firstOrFail();
    });

    // 表示（公開） … create を呼ぶ
    Route::get('/{job}/apply', [ApplicationController::class, 'create'])
        ->name('front.jobs.apply_form');

    // 送信（公開） … store を呼ぶ
    Route::post('/{job}/apply', [ApplicationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('front.jobs.apply_store');

    // お気に入り（エンドユーザー専用）
    Route::middleware(['auth:web', 'role:enduser'])->group(function () {
        Route::post('/{job}/favorite',        [FavoriteController::class, 'store'])
            ->whereNumber('job')
            ->name('favorites.store');

        Route::delete('/{job}/favorite',      [FavoriteController::class, 'destroy'])
            ->whereNumber('job')
            ->name('favorites.destroy');

        Route::post('/{job}/favorite/toggle', [FavoriteController::class, 'toggle'])
            ->whereNumber('job')
            ->name('favorites.toggle');
    });


    // ★ 動的ルートは最後。create を除外して衝突回避
    Route::get('/{slugOrId}', [FrontJobController::class, 'show'])
        ->where('slugOrId', '^(?!create$)([A-Za-z0-9\-]+|\d+)$')
        ->name('front.jobs.show');
});

/* ===== MYPAGE（エンドユーザーのみ） ===== */
Route::middleware(['auth:web', 'role:enduser'])->prefix('mypage')->name('mypage.')->group(function () {
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])
        ->whereNumber('application')->name('applications.show');

    Route::get('/favorite', [FavoriteController::class, 'index'])->name('favorites.index');
});

/* ===== USERS（企業側のみ） ===== */
Route::middleware(['auth:web', 'role:company', EnsureCompanyProfileCompleted::class])
    ->prefix('users')->name('users.')->group(function () {
        Route::get('/applicants', [ApplicantController::class, 'index'])->name('applicants.index');
        Route::get('/applicants/{application}', [ApplicantController::class, 'show'])
            ->whereNumber('application')->name('applicants.show');
        Route::patch('/applicants/{application}/status', [ApplicantController::class, 'updateStatus'])
            ->whereNumber('application')->name('applicants.status');

        Route::get('/sponsored-articles', [SponsoredArticleController::class, 'index'])
            ->name('sponsored_articles.index');
    });

/* ===== ロール共通ダッシュボード（中身で出し分け） ===== */
Route::get('/dashboard', function () {
    if (session()->has('url.intended')) {
        $to = session('url.intended');
        session()->forget('url.intended');
        return redirect()->to($to);
    }
    $role = \App\Support\RoleResolver::resolve(auth()->user());
    return view('dashboard', compact('role'));
})
    // ★ 企業ユーザーが未完了ならここで捕捉してオンボーディングへ
    ->middleware(['auth:web', 'role:enduser,company', 'verified', EnsureCompanyProfileCompleted::class])
    ->name('dashboard');

/* ===== 迷子防止：企業/一般向けログイン導線（エイリアス） ===== */
Route::get('/company/login', function (Request $r) {
    return redirect()->route('login.intended', ['redirect' => route('dashboard')]);
})->name('company.login');

Route::get('/mypage/login', function (Request $r) {
    return redirect()->route('login.intended', ['redirect' => route('dashboard')]);
})->name('mypage.login');

/* ===========================================================
| 管理者ログイン（未認証専用）
|===========================================================*/
if (! \Illuminate\Support\Facades\Route::has('admin.login')) {
    Route::prefix('admin')->name('admin.')->middleware(['web', 'guest:admin'])->group(function () {
        Route::get('login', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'show'])
            ->name('login');
        Route::post('login', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'login'])
            ->name('login.post');
    });
}
// ★ 管理者ログイン済みで /admin を踏んだら /admin/dashboard へ
Route::middleware('auth:admin')->get('/admin', fn() => redirect('/admin/dashboard'));

/* ------------------------------------------------------------------
| Admin（auth:admin）
|-------------------------------------------------------------------*/
Route::prefix('admin')->middleware(['auth:admin'])->name('admin.')->group(function () {

    // 招待
    Route::post('/companies/invite', [CompanyInvitationController::class, 'store'])->name('companies.invite.store');
    Route::post('/companies/invite/{invitation}/resend', [CompanyInvitationController::class, 'resend'])->name('companies.invite.resend');
    Route::post('/companies/invite/{invitation}/cancel', [CompanyInvitationController::class, 'cancel'])->name('companies.invite.cancel');

    // 管理画面からパスワードリセットメール送信
    Route::post('/users/{user}/send-reset', function (\App\Models\User $user) {
        \Illuminate\Support\Facades\Password::broker('users')->sendResetLink(['email' => $user->email]);
        return back()->with('status', 'パスワードリセットメールを送信しました。');
    })->name('users.send_reset');

    // Blade→Filament のエイリアス
    Route::get('__alias/filament-posts', fn() => redirect(PostResource::getUrl('index', panel: 'admin')))->name('posts.index');
    Route::get('__alias/filament-posts/create', fn() => redirect(PostResource::getUrl('create', panel: 'admin')))->name('posts.create');
    Route::get('__alias/filament-posts/{post}/edit', fn($post) => redirect(PostResource::getUrl('edit', ['record' => $post], panel: 'admin')))->whereNumber('post')->name('posts.edit');

    // 旧Blade AJAX の保険
    Route::post('/preupload', [AdminPostController::class, 'preupload'])->name('preupload');

    // 行内操作API
    Route::post('users/{user}/set-role',                   [UserQuickAssignController::class, 'setRole'])->whereNumber('user')->name('users.set_role');
    Route::post('users/{user}/assign-company',             [UserQuickAssignController::class, 'assignCompany'])->whereNumber('user')->name('users.assign_company');
    Route::delete('users/{user}/assign-company/{company}', [UserQuickAssignController::class, 'unassign'])->whereNumber(['user', 'company'])->name('users.unassign_company');

    /* Recruit Jobs（Filament に委譲） */
    $recruitSlug = method_exists(RecruitJobResource::class, 'getSlug') ? RecruitJobResource::getSlug() : 'recruit-jobs';

    Route::get('recruit_jobs', function () use ($recruitSlug) {
        $name = "filament.admin.resources.$recruitSlug.index";
        return redirect()->to(\Illuminate\Support\Facades\Route::has($name)
            ? \App\Filament\Resources\RecruitJobResource::getUrl('index')
            : url('/admin/recruit_jobs'));
    })->name('jobs.index');

    Route::get('recruit_jobs/create', function () use ($recruitSlug) {
        $name = "filament.admin.resources.$recruitSlug.create";
        return redirect()->to(\Illuminate\Support\Facades\Route::has($name)
            ? \App\Filament\Resources\RecruitJobResource::getUrl('create')
            : url('/admin/recruit_jobs'));
    })->name('jobs.create');

    Route::get('recruit_jobs/{job}', function ($job) use ($recruitSlug) {
        $name = "filament.admin.resources.$recruitSlug.edit";
        return redirect()->to(\Illuminate\Support\Facades\Route::has($name)
            ? \App\Filament\Resources\RecruitJobResource::getUrl('edit', ['record' => $job])
            : url("/admin/recruit_jobs/{$job}/edit"));
    })->whereNumber('job')->name('jobs.show');

    Route::get('recruit_jobs/{job}/edit', function ($job) use ($recruitSlug) {
        $name = "filament.admin.resources.$recruitSlug.edit";
        return redirect()->to(\Illuminate\Support\Facades\Route::has($name)
            ? \App\Filament\Resources\RecruitJobResource::getUrl('edit', ['record' => $job])
            : url("/admin/recruit_jobs/{$job}/edit"));
    })->whereNumber('job')->name('jobs.edit');

    // 応募一覧（Filament に委譲）
    Route::get('__alias/filament-applications', function () {
        // Filament の ApplicationResource 一覧にリダイレクト
        return redirect()->route('filament.admin.resources.applications.index');
    })->name('applications.index');

    // CSV エクスポートは今まで通りコントローラで処理
    Route::get('applications/export', [ApplicationsController::class, 'export'])
        ->name('applications.export');


    // アップロード疎通テスト
    Route::match(['get', 'post'], 'posts/__upload-test', function (Request $r) {
        if ($r->isMethod('post')) {
            if (!$r->hasFile('f')) return 'no file';
            $f = $r->file('f');
            if (!$f->isValid()) return 'err: ' . $f->getError();
            $p = $f->store('thumbnails', 'public');
            return 'stored: ' . Storage::url($p);
        }
        return '<form method="post" enctype="multipart/form-data">' . csrf_field() . '<input type="file" name="f" accept="image/*"><button>send</button></form>';
    })->name('posts.uploadtest');
});

/* ===== オンボーディング（初回作成のみ） ===== */
Route::middleware(['web', 'auth:web'])->group(function () {
    Route::get('/onboarding/company',  [OnboardingCompanyController::class, 'create'])
        ->name('onboarding.company.edit');    // ← ミドルウェアのホワイトリストと一致
    Route::post('/onboarding/company', [OnboardingCompanyController::class, 'store'])
        ->name('onboarding.company.update');
});


/* ★Filament互換のログアウト／ログイン ルートを救済 */
if (!Route::has('filament.admin.auth.logout')) {
    Route::match(['POST', 'GET'], '/admin/__alias/filament-logout', function (Request $request) {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    })->name('filament.admin.auth.logout');
}
if (!Route::has('filament.admin.auth.login')) {
    Route::get('/admin/__alias/filament-login', fn() => redirect('/admin/login'))
        ->name('filament.admin.auth.login');
}

/**
 * ============================
 * Email Verification
 * ============================
 */
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->intended('/');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');

Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:3,1'])->name('verification.send');

/* ===== Breeze Profile ===== */
Route::middleware('auth:web')->group(function () {
    if (class_exists(ProfileController::class)) {
        Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // 分割更新エンドポイント
        Route::patch('/profile/basics',     [ProfileController::class, 'updateBasics'])->name('profile.update.basics');
        Route::patch('/profile/educations', [ProfileController::class, 'updateEducations'])->name('profile.update.educations');
        Route::patch('/profile/works',      [ProfileController::class, 'updateWorks'])->name('profile.update.works');
        Route::patch('/profile/desired',    [ProfileController::class, 'updateDesired'])->name('profile.update.desired');

        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('/profile/edit', fn() => redirect()->route('profile.edit'))->name('user.profile.edit');
    }
});

// ===== includes =====
if (file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
}
if (file_exists(__DIR__ . '/admin.php')) {
    require __DIR__ . '/admin.php';
}
if (file_exists(__DIR__ . '/front_public.php')) {
    require __DIR__ . '/front_public.php';
}

/* ===== DEBUG ===== */
Route::get('/__ping', fn() => 'pong');
Route::middleware(['auth:admin'])->get('/__role-test', fn() => 'ok');

// パスワード更新（Breeze 既定）
Route::middleware('auth:web')->put('/password', [PasswordController::class, 'update'])
    ->name('password.update');

// ===== 招待受諾フロー =====
Route::prefix('invites')->name('invites.')->group(function () {
    // 受諾フォーム表示
    Route::get('/accept/{token}', [InviteAcceptController::class, 'show'])
        ->middleware(['throttle:20,1'])
        ->name('accept');

    // 受諾の完了（パスワード設定 & 紐付け）
    Route::post('/accept/{token}', [InviteAcceptController::class, 'accept'])
        ->middleware(['throttle:20,1'])
        ->name('accept.post');

    // 期限切れ表示
    Route::view('/expired', 'invites.expired')->name('expired');
});

Route::get('/invite/{token}', [CompanyInviteAcceptController::class, 'accept'])
    ->name('company.invite.accept')
    ->middleware('web');
