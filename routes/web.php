<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// Top / Front
use App\Http\Controllers\Front\LandingController;
use App\Http\Controllers\Front\CompanyController as FCompanyController;
use App\Http\Controllers\Front\ApplicationController;
use App\Http\Controllers\Front\FrontJobController;

// Admin
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ApplicationsController;
use App\Http\Controllers\Admin\CompanyUserAssignController;
use App\Http\Controllers\Admin\UserQuickAssignController;

// Auth / Profile
use App\Http\Controllers\ProfileController;

// Favorites
use App\Http\Controllers\FavoriteController;

// Users(企業側)
use App\Http\Controllers\Users\ApplicantController;
use App\Http\Controllers\Users\SponsoredArticleController;

// Filament Resources
use App\Filament\Resources\RecruitJobResource;
use App\Filament\Resources\PostResource;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ===== Front base =====
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::permanentRedirect('/blog', '/');

// ===== Company profile (企業ユーザーのみ) =====
Route::middleware(['auth:web', 'role:company'])->group(function () {
    Route::get('/company/edit',    [\App\Http\Controllers\CompanyProfileController::class, 'edit'])->name('user.company.edit');
    Route::post('/company/update', [\App\Http\Controllers\CompanyProfileController::class, 'update'])->name('user.company.update');
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

    // 企業ユーザー専用（作成・更新系）
    Route::middleware(['auth:web', 'role:company'])->group(function () {
        Route::get('/create',       [FrontJobController::class, 'create'])->name('front.jobs.create');
        Route::post('/',            [FrontJobController::class, 'store'])->name('front.jobs.store');

        Route::get('/{job}/edit',   [FrontJobController::class, 'edit'])
            ->whereNumber('job')->name('front.jobs.edit');
        Route::patch('/{job}',      [FrontJobController::class, 'update'])
            ->whereNumber('job')->name('front.jobs.update');
        Route::delete('/{job}',     [FrontJobController::class, 'destroy'])
            ->whereNumber('job')->name('front.jobs.destroy');
    });

    // ★お気に入り→応募へ（ゲスト/ログイン両対応の入口）
    Route::post('/{slugOrId}/favorite-apply', [FavoriteController::class, 'favoriteAndApply'])
        ->where('slugOrId', '^([A-Za-z0-9\-]+|\d+)$')
        ->name('front.jobs.favorite_apply');

    // 応募（エンドユーザー専用／POST本体）
    Route::post('/{job:slug}/apply', [ApplicationController::class, 'store'])
        ->middleware(['auth:web', 'role:enduser'])
        ->name('front.jobs.apply');

    // ★ 応募ゲート（GET）
    Route::get('/{job:slug}/apply', function (\App\Models\Job $job) {
        return redirect()->route('front.jobs.show', $job->slug)->with('apply_intent', true);
    })
        ->middleware(['auth:web', \App\Http\Middleware\AutoFavorite::class])
        ->name('front.jobs.apply.gate');

    // お気に入り（エンドユーザー専用）
    Route::middleware(['auth:web', 'role:enduser'])->group(function () {
        Route::post('/{job}/favorite',        [FavoriteController::class, 'store'])->whereNumber('job')->name('favorites.store');
        Route::delete('/{job}/favorite',      [FavoriteController::class, 'destroy'])->whereNumber('job')->name('favorites.destroy');
        Route::post('/{job}/favorite/toggle', [FavoriteController::class, 'toggle'])->whereNumber('job')->name('favorites.toggle');
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
Route::middleware(['auth:web', 'role:company'])->prefix('users')->name('users.')->group(function () {
    Route::get('/applicants', [ApplicantController::class, 'index'])->name('applicants.index');
    Route::get('/applicants/{application}', [ApplicantController::class, 'show'])
        ->whereNumber('application')->name('applicants.show');
    Route::patch('/applicants/{application}/status', [ApplicantController::class, 'updateStatus'])
        ->whereNumber('application')->name('applicants.status');

    Route::get('/sponsored-articles', [SponsoredArticleController::class, 'index'])
        ->name('sponsored_articles.index');
});

/* ===== Dashboard（一般ログイン専用：admin では入れない） ===== */
Route::get('/dashboard', function () {
    if (session()->has('url.intended')) {
        $to = session('url.intended');
        session()->forget('url.intended');
        return redirect()->to($to);
    }
    return view('dashboard');
})->middleware(['auth:web', 'role:enduser,company'])
  ->name('dashboard');

/* ------------------------------------------------------------------
| Admin（auth:admin）
|-------------------------------------------------------------------*/
Route::prefix('admin')->middleware(['auth:admin'])->name('admin.')->group(function () {

    // ✅ Bladeの /admin/posts ルートは削除（Filament に任せる）
    Route::get(
        '__alias/filament-posts',
        fn() => redirect(PostResource::getUrl('index', panel: 'admin'))
    )->name('posts.index');

    Route::get(
        '__alias/filament-posts/create',
        fn() => redirect(PostResource::getUrl('create', panel: 'admin'))
    )->name('posts.create');

    Route::get(
        '__alias/filament-posts/{post}/edit',
        fn($post) => redirect(PostResource::getUrl('edit', ['record' => $post], panel: 'admin'))
    )->whereNumber('post')->name('posts.edit');

    // ★互換：旧Bladeの AJAX プレアップロードが残っていても落ちないように
    Route::post('/preupload', [PostController::class, 'preupload'])->name('preupload');

    // 行内操作API
    Route::post('users/{user}/set-role',                 [UserQuickAssignController::class, 'setRole'])
        ->whereNumber('user')->name('users.set_role');
    Route::post('users/{user}/assign-company',           [UserQuickAssignController::class, 'assignCompany'])
        ->whereNumber('user')->name('users.assign_company');
    Route::delete('users/{user}/assign-company/{company}', [UserQuickAssignController::class, 'unassign'])
        ->whereNumber(['user', 'company'])->name('users.unassign_company');

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

    // 応募一覧（Blade 版）
    Route::get('applications',        [ApplicationsController::class, 'index'])->name('applications.index');
    Route::get('applications/export', [ApplicationsController::class, 'export'])->name('applications.export');

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

/* ===== Filament 暫定エイリアス（保険） ===== */
if (!Route::has('filament.admin.resources.applications.index')) {
    Route::redirect('/admin/__alias/filament-applications', '/admin/applications', 302)
        ->name('filament.admin.resources.applications.index');
}

/* ★旧ルート名の救済（posts.index）— Resource未登録でも落ちない版 */
if (!Route::has('filament.admin.resources.posts.index')) {
    Route::get('/admin/__alias/filament-posts-fallback', function () {
        return redirect('/admin'); // 最低限ダッシュボードへ避難
    })->name('filament.admin.resources.posts.index');
}

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

/* ===== Breeze Profile ===== */
Route::middleware('auth:web')->group(function () {
    if (class_exists(ProfileController::class)) {
        Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update'); // 互換保持

        // ✅ 分割更新エンドポイント
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
