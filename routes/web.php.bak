<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


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
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\ApplicationsController;  // 管理者：応募一覧
use App\Http\Controllers\Admin\CompanyUserAssignController; // ★追加：担当者割り振り
use App\Http\Controllers\Admin\UserQuickAssignController;   // ★追加：ユーザー行内操作（役割変更/会社割当/解除）

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
// TOPは / に統一（以前 /blog だった内容を表示）
Route::get('/', [LandingController::class, 'index'])->name('home');
// 旧 /blog → / へ301（SEO/ブクマ対策）
Route::permanentRedirect('/blog', '/');

/*
| ===== Front posts (slug or id) =====
| ※ ここでは定義しない（front_public.php 側にある想定／重複回避）
*/

// ===== Company profile (企業ユーザーのみ) =====
// ★ IMPORTANT: role は company
Route::middleware(['auth:web', 'role:company'])->group(function () {
    Route::get('/company/edit',    [\App\Http\Controllers\CompanyProfileController::class, 'edit'])->name('user.company.edit');
    Route::post('/company/update', [\App\Http\Controllers\CompanyProfileController::class, 'update'])->name('user.company.update');
});

// ===== Legacy redirects (301) =====
Route::permanentRedirect('/company/company', '/company');
Route::permanentRedirect('/jobs/jobs',       '/jobs');
Route::permanentRedirect('/companys',        '/company');
Route::permanentRedirect('/companies',       '/company');

// ★ 旧 /jobs/{slugOrId} → 新 /recruit_jobs/{slugOrId}（パラメータ対応の301）
Route::get('/jobs/{slugOrId}', function (string $slugOrId) {
    return redirect("/recruit_jobs/{$slugOrId}", 301);
})->where('slugOrId', '^([A-Za-z0-9\-]+|\d+)$');

// 旧 /jobs 一覧を新URLへ
Route::permanentRedirect('/jobs', '/recruit_jobs');

// ===== Front: Company (list/detail) =====
Route::prefix('company')->name('front.company.')->group(function () {
    Route::get('/', [FCompanyController::class, 'index'])->name('index');
    // slug でも id でも OK
    Route::get('/{slugOrId}', [FCompanyController::class, 'show'])
        ->where('slugOrId', '^([A-Za-z0-9\-]+|\d+)$')
        ->name('show');
});

// ===== Front: Jobs (list/detail/apply) =====
Route::prefix('recruit_jobs')->group(function () {
    // 応募送信：エンドユーザーのみ
    Route::post('/{job:slug}/apply', [ApplicationController::class, 'store'])
        ->middleware(['auth:web', 'role:enduser'])
        ->name('front.jobs.apply');

    Route::get('/', [FrontJobController::class, 'index'])->name('front.jobs.index');

    Route::get('/{slugOrId}', [FrontJobController::class, 'show'])
        ->where('slugOrId', '^([A-Za-z0-9\-]+|\d+)$')
        ->name('front.jobs.show');

    // お気に入り：エンドユーザーのみ
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
        ->whereNumber('application')
        ->name('applications.show');

    Route::get('/favorite', [FavoriteController::class, 'index'])->name('favorites.index');
});

/* ===== USERS（企業側のみ） ===== */
// ★ IMPORTANT: role は company
Route::middleware(['auth:web', 'role:company'])->prefix('users')->name('users.')->group(function () {
    // 応募者一覧
    Route::get('/applicants', [ApplicantController::class, 'index'])->name('applicants.index');
    Route::get('/applicants/{application}', [ApplicantController::class, 'show'])
        ->whereNumber('application')
        ->name('applicants.show');
    Route::patch('/applicants/{application}/status', [ApplicantController::class, 'updateStatus'])
        ->whereNumber('application')
        ->name('applicants.status');

    // スポンサー記事一覧
    Route::get('/sponsored-articles', [SponsoredArticleController::class, 'index'])
        ->name('sponsored_articles.index');
});

// ===== Dashboard（一般ログイン専用：admin では入れない） =====
// - auth:web を明示
// - 役割は enduser / company のみ（将来ロール追加時はここで制御）
Route::view('/dashboard', 'dashboard')
    ->middleware(['auth:web', 'role:enduser,company'])
    ->name('dashboard');

// ===== Admin（管理者のみ：auth:admin に統一） =====
Route::prefix('admin')->middleware(['auth:admin'])->name('admin.')->group(function () {
    // Posts
    if (class_exists(PostController::class)) {
        Route::resource('posts', PostController::class);
    } else {
        Route::get('posts', fn () => response('posts index (stub)', 200))->name('posts.index');
        Route::get('posts/create', fn () => response('posts create (stub)', 200))->name('posts.create');
        Route::post('posts', fn () => abort(501));
    }

    // Users
    if (class_exists(UserController::class)) {
        Route::resource('users', UserController::class)->except(['show']);
    } else {
        Route::get('users', fn () => response('users index (stub)', 200))->name('users.index');
        Route::get('users/create', fn () => response('users create (stub)', 200))->name('users.create');
    }

    // ★追加：ユーザー行内操作（役割変更 / 会社割当 / 解除）
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

    // Companies
    Route::resource('companies', AdminCompanyController::class)->except(['show']);

    // ★追加：会社担当者の割り振り（単一/複数対応）
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

    // 管理配下: アップロード疎通テスト（安全のため admin のみ）
    Route::match(['get','post'], 'posts/__upload-test', function (Request $r) {
        if ($r->isMethod('post')) {
            if (!$r->hasFile('f')) return 'no file';
            $f = $r->file('f');
            if (!$f->isValid()) return 'err: '.$f->getError();
            $p = $f->store('thumbnails', 'public');
            return 'stored: '.Storage::url($p);
        }
        return '<form method="post" enctype="multipart/form-data">'.csrf_field().'<input type="file" name="f" accept="image/*"><button>send</button></form>';
    })->name('posts.uploadtest'); // admin. はグループで付与済み
});

// ===== Breeze Profile =====
Route::middleware('auth:web')->group(function () {
    if (class_exists(ProfileController::class)) {
        Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('/profile/edit', fn () => redirect()->route('profile.edit'))->name('user.profile.edit');
    }
});

// ===== Auth routes include =====
if (file_exists(__DIR__.'/auth.php')) {
    require __DIR__.'/auth.php';
}

// ===== Admin auth routes include（/admin/login など） =====
if (file_exists(__DIR__.'/admin.php')) {
    require __DIR__.'/admin.php';
}

// ===== Front public include =====
if (file_exists(__DIR__.'/front_public.php')) {
    require __DIR__.'/front_public.php';
}

/* ===== DEBUG / DIAG ===== */
Route::get('/__ping', fn() => 'pong');

// デバッグ系は **管理者のみ** に寄せる
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

    Route::get('/__role-test', fn() => 'ok'); // すでに auth:admin
});

// 事前アップロードAPI（要一般ログイン）
// ※ フロントから使う想定なら admin ではなく web に限定
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
