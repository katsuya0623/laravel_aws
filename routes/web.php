<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// Top / Front
use App\Http\Controllers\Front\LandingController;
use App\Http\Controllers\Front\PostController as FrontPostController;
use App\Http\Controllers\Front\CompanyController as FCompanyController;
use App\Http\Controllers\Front\JobController as FrontJobController;
use App\Http\Controllers\Front\ApplicationController;

// Admin
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\JobController as AdminJobController;
// use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
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
Route::get('/jobs/{slugOrId}', function (string $slugOrId) {
    return redirect("/recruit_jobs/{$slugOrId}", 301);
})->where('slugOrId', '^([A-Za-z0-9\-]+|\d+)$');
Route::permanentRedirect('/jobs', '/recruit_jobs');

// ===== Front: Company (list/detail) =====
Route::prefix('company')->name('front.company.')->group(function () {
    Route::get('/', [FCompanyController::class, 'index'])->name('index');
    Route::get('/{slugOrId}', [FCompanyController::class, 'show'])
        ->where('slugOrId', '^([A-Za-z0-9\-]+|\d+)$')
        ->name('show');
});

// ===== Front: Jobs (list/detail/apply) =====
Route::prefix('recruit_jobs')->group(function () {
    Route::post('/{job:slug}/apply', [ApplicationController::class, 'store'])
        ->middleware(['auth:web', 'role:enduser'])
        ->name('front.jobs.apply');

    Route::get('/', [FrontJobController::class, 'index'])->name('front.jobs.index');
    Route::get('/{slugOrId}', [FrontJobController::class, 'show'])
        ->where('slugOrId', '^([A-Za-z0-9\-]+|\d+)$')
        ->name('front.jobs.show');

    Route::middleware(['auth:web', 'role:enduser'])->group(function () {
        Route::post('/{job}/favorite',        [FavoriteController::class, 'store'])->whereNumber('job')->name('favorites.store');
        Route::delete('/{job}/favorite',      [FavoriteController::class, 'destroy'])->whereNumber('job')->name('favorites.destroy');
        Route::post('/{job}/favorite/toggle', [FavoriteController::class, 'toggle'])->whereNumber('job')->name('favorites.toggle');
    });
});

/* ===== MYPAGE: Applications & Favorites（エンドユーザーのみ） ===== */
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
Route::view('/dashboard', 'dashboard')
    ->middleware(['auth:web', 'role:enduser,company'])
    ->name('dashboard');

/* ------------------------------------------------------------------
| Admin（管理者のみ：auth:admin） + Filament ダッシュボードのエイリアス
|-------------------------------------------------------------------*/

// /admin/dashboard → Filament のダッシュボードへ
Route::middleware(['auth:admin'])->get('/admin/dashboard', function () {
    return redirect()->route('filament.admin.pages.dashboard');
})->name('admin.dashboard');

Route::prefix('admin')->middleware(['auth:admin'])->name('admin.')->group(function () {

    // Posts（Blade 版は継続）
    if (class_exists(App\Http\Controllers\Admin\PostController::class)) {
        Route::resource('posts', App\Http\Controllers\Admin\PostController::class);
    } else {
        Route::get('posts', fn () => response('posts index (stub)', 200))->name('posts.index');
        Route::get('posts/create', fn () => response('posts create (stub)', 200))->name('posts.create');
        Route::post('posts', fn () => abort(501));
    }

    // Users は Filament へ移行のため停止

    // 行内操作API
    Route::post  ('users/{user}/set-role',                 [UserQuickAssignController::class,'setRole'])
        ->whereNumber('user')->name('users.set_role');
    Route::post  ('users/{user}/assign-company',           [UserQuickAssignController::class,'assignCompany'])
        ->whereNumber('user')->name('users.assign_company');
    Route::delete('users/{user}/assign-company/{company}', [UserQuickAssignController::class,'unassignCompany'])
        ->whereNumber(['user','company'])->name('users.unassign_company');

    // Recruit Jobs
    Route::resource('recruit_jobs', AdminJobController::class)
        ->parameters(['recruit_jobs' => 'job'])
        ->names([
            'index'   => 'jobs.index',
            'create'  => 'jobs.create',
            'store'   => 'jobs.store',
            'show'    => 'jobs.show',
            'edit'    => 'jobs.edit',
            'update'  => 'jobs.update',
            'destroy' => 'jobs.destroy',
        ]);

    // Companies（Blade版停止／Filament委譲）

    // 会社担当者の割り振り
    Route::get   ('companies/{company}/assign-user',                [CompanyUserAssignController::class,'edit'])
        ->whereNumber('company')->name('companies.assign_user');
    Route::post  ('companies/{company}/assign-user',                [CompanyUserAssignController::class,'assignExisting'])
        ->whereNumber('company')->name('companies.assign_user.post');
    Route::post  ('companies/{company}/assign-user/create',         [CompanyUserAssignController::class,'createAndAssign'])
        ->whereNumber('company')->name('companies.assign_user.create');
    Route::delete('companies/{company}/assign-user/{user}',         [CompanyUserAssignController::class,'unassign'])
        ->whereNumber(['company','user'])->name('companies.assign_user.delete');
    Route::patch ('companies/{company}/assign-user/{user}/primary', [CompanyUserAssignController::class,'setPrimary'])
        ->whereNumber(['company','user'])->name('companies.assign_user.primary');

    // Admin: 応募一覧 & Export
    Route::get('applications', [ApplicationsController::class, 'index'])->name('applications.index');
    Route::get('applications/export', [ApplicationsController::class, 'export'])->name('applications.export');

    // アップロード疎通テスト
    Route::match(['get','post'], 'posts/__upload-test', function (Request $r) {
        if ($r->isMethod('post')) {
            if (!$r->hasFile('f')) return 'no file';
            $f = $r->file('f');
            if (!$f->isValid()) return 'err: '.$f->getError();
            $p = $f->store('thumbnails', 'public');
            return 'stored: '.Storage::url($p);
        }
        return '<form method="post" enctype="multipart/form-data">'.csrf_field().'<input type="file" name="f" accept="image/*"><button>send</button></form>';
    })->name('posts.uploadtest');
});

/* ===== Filament 暫定エイリアス ===== */
if (!Route::has('filament.admin.resources.posts.index') && Route::has('admin.posts.index')) {
    Route::middleware(['auth:admin'])->get('/admin/__alias/filament-posts', function () {
        return redirect()->route('admin.posts.index');
    })->name('filament.admin.resources.posts.index');
}
if (!Route::has('filament.admin.resources.posts.create') && Route::has('admin.posts.create')) {
    Route::middleware(['auth:admin'])->get('/admin/__alias/filament-posts/create', function () {
        return redirect()->route('admin.posts.create');
    })->name('filament.admin.resources.posts.create');
}

/* Logout（Filament が参照するルート名に合わせる） */
if (!Route::has('filament.admin.auth.logout')) {
    Route::middleware(['web', 'auth:admin'])->post('/admin/__alias/filament-logout', function (Request $r) {
        Auth::guard('admin')->logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect('/admin/login');
    })->name('filament.admin.auth.logout');
}

/* ===== Breeze Profile ===== */
Route::middleware('auth:web')->group(function () {
    if (class_exists(ProfileController::class)) {
        Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('/profile/edit', fn () => redirect()->route('profile.edit'))->name('user.profile.edit');
    }
});

// ===== includes =====
if (file_exists(__DIR__.'/auth.php'))  { require __DIR__.'/auth.php'; }
if (file_exists(__DIR__.'/admin.php')) { require __DIR__.'/admin.php'; }
if (file_exists(__DIR__.'/front_public.php')) { require __DIR__.'/front_public.php'; }

/* ===== DEBUG / DIAG ===== */
Route::get('/__ping', fn() => 'pong');

Route::middleware(['auth:admin'])->group(function () {
    Route::get('/__create-plain', function () {
        $post = new \App\Models\Post(); $post->published_at = now();
        $categories = \App\Models\Category::orderBy('name')->get();
        $tags = \App\Models\Tag::orderBy('name')->get();
        return view('admin.posts.debug_create', compact('post','categories','tags'));
    });

    Route::get('/debug-create', function () {
        $post = new \App\Models\Post(); $post->published_at = now();
        $categories = \App\Models\Category::orderBy('name')->get();
        $tags = \App\Models\Tag::orderBy('name')->get();
        return view('admin.posts.debug_create', compact('post','categories','tags'));
    });

    Route::get('/__role-test', fn() => 'ok');
});

// 事前アップロードAPI（要一般ログイン）
Route::post('/__preupload', function (Request $r) {
    if (!$r->hasFile('thumbnail')) {
        return response()->json(['ok' => false, 'msg' => 'no file'], 422);
    }
    $f = $r->file('thumbnail');
    if (!$f->isValid()) {
        return response()->json(['ok' => false, 'msg' => 'err: '.$f->getError()], 422);
    }
    if ($f->getSize() > 40 * 1024 * 1024) {
        return response()->json(['ok' => false, 'msg' => 'too large'], 413);
    }
    $path = $f->store('thumbnails', 'public');

    return response()->json([
        'ok'  => true,
        'path'=> $path,
        'url' => Storage::url($path),
    ]);
})->middleware(['auth:web'])->name('preupload');
