<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PostController extends Controller
{
    /** 一覧 */
    public function index(Request $request)
    {
        $categoryId = $request->integer('category');
        $q        = $request->string('q')->toString();
        $featured = $request->boolean('featured');
        $status   = $request->string('status')->toString();
        $from     = $request->date('from');
        $to       = $request->date('to');

        $posts = Post::query()
            ->with(['category','user'])
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($status === 'published', fn($q) =>
                $q->whereNotNull('published_at')->where('published_at','<=', now())
            )
            ->when($status === 'draft', fn($q) =>
                $q->where(fn($w) => $w->whereNull('published_at')->orWhere('published_at','>', now()))
            )
            ->when($featured, fn($q) => $q->where('is_featured', true))
            ->when($from, fn($q) => $q->whereDate('published_at','>=',$from))
            ->when($to, fn($q) => $q->whereDate('published_at','<=',$to))
            ->when($q, function ($query) use ($q) {
                $tokens = preg_split('/\s+/u', trim($q));
                foreach ($tokens as $t) {
                    $like = '%'.$t.'%';
                    $query->where(function ($qq) use ($like) {
                        $qq->where('title','like',$like)
                           ->orWhere('excerpt','like',$like)
                           ->orWhere('body','like',$like)
                           ->orWhere('slug','like',$like)
                           ->orWhere('seo_title','like',$like)
                           ->orWhere('seo_description','like',$like);
                    });
                }
            })
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        $categories = Category::orderBy('name')->get(['id','name']);

        return view('admin.posts.index', compact('posts','categories'));
    }

    /** 新規作成フォーム */
    public function create()
    {
        $post = new Post();
        $post->published_at = now();
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        return view('admin.posts.create', compact('post','categories','tags'));
    }

    /** 編集フォーム */
    public function edit(Post $post)
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        return view('admin.posts.edit', compact('post','categories','tags'));
    }

    /** 保存（新規） */
    public function store(Request $request)
    {
        Log::debug('thumb pre-validate', [
            'route'   => optional($request->route())->getName(),
            'hasFile' => $request->hasFile('thumbnail'),
            'pre'     => $request->input('preuploaded_thumbnail_path'),
        ]);

        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'excerpt'          => 'nullable|string|max:200',
            'body'             => 'nullable|string',
            'published_at'     => 'nullable|date',
            'slug'             => ['nullable','string','regex:/^[A-Za-z0-9\-_.]+$/'],
            'seo_title'        => 'nullable|string|max:70',
            'seo_description'  => 'nullable|string|max:160',
            'is_featured'      => 'nullable|boolean',
            'reading_time'     => 'nullable|integer|min:1|max:32767',
            'category_id'      => 'nullable|integer|exists:categories,id',
            'tags'             => 'nullable|array',
            'tags.*'           => 'integer|exists:tags,id',
            'thumbnail'        => 'nullable|file|max:40960',
            'preuploaded_thumbnail_path' => 'nullable|string',
        ]);

        $data['body'] = $request->has('body') && $request->input('body') !== null
                        ? (string)$request->input('body') : '';

        // slug生成（ユニーク化）
        $raw  = trim((string)$request->input('slug', ''));
        $slug = Str::slug($raw !== '' ? $raw : $data['title']);
        if ($slug === '') $slug = 'post-'.Str::random(8);
        $base = $slug; $i = 2;
        while (Post::where('slug', $slug)->exists()) $slug = $base.'-'.$i++;
        $data['slug'] = $slug;

        if ($request->filled('published_at')) {
            $data['published_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $request->input('published_at'), config('app.timezone'));
        }
        $data['is_featured'] = $request->boolean('is_featured');

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $data['user_id'] = $request->user()?->id ?? auth()->id();
        $post = Post::create($data);

        $this->handleUploadAndPersist($request, $post);

        if (!empty($data['category_id'])) {
            $post->category()->associate($data['category_id']);
            $post->save();
        }
        if ($tags) $post->tags()->sync($tags);

        return redirect()->route('admin.posts.edit', $post)->with('status', 'created');
    }

    /** 更新 */
    public function update(Request $request, Post $post)
    {
        Log::debug('thumb pre-validate', [
            'route'   => optional($request->route())->getName(),
            'post_id' => $post->id,
            'hasFile' => $request->hasFile('thumbnail'),
            'pre'     => $request->input('preuploaded_thumbnail_path'),
        ]);

        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'excerpt'          => 'nullable|string|max:200',
            'body'             => 'nullable|string',
            'published_at'     => 'nullable|date',
            'slug'             => ['nullable','string','regex:/^[A-Za-z0-9\-_.]+$/','unique:posts,slug,' . $post->id],
            'seo_title'        => 'nullable|string|max:70',
            'seo_description'  => 'nullable|string|max:160',
            'is_featured'      => 'nullable|boolean',
            'reading_time'     => 'nullable|integer|min:1|max:32767',
            'category_id'      => 'nullable|integer|exists:categories,id',
            'tags'             => 'nullable|array',
            'tags.*'           => 'integer|exists:tags,id',
            'thumbnail'        => 'nullable|file|max:40960',
            'thumbnail_remove' => 'nullable|boolean',
            'preuploaded_thumbnail_path' => 'nullable|string',
        ]);

        $data['body'] = $request->has('body') && $request->input('body') !== null
                        ? (string)$request->input('body') : ($post->body ?? '');

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        if (array_key_exists('slug', $data)) {
            $raw = trim((string)($data['slug'] ?? ''));
            if ($raw === '') {
                unset($data['slug']);
            } else {
                $slug = Str::slug($raw) ?: 'post-'.Str::random(8);
                $base = $slug; $i = 2;
                while (Post::where('slug', $slug)->where('id','!=',$post->id)->exists()) $slug = $base.'-'.$i++;
                $data['slug'] = $slug;
            }
        }

        if ($request->filled('published_at')) {
            $data['published_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $request->input('published_at'), config('app.timezone'));
        }
        $data['is_featured'] = $request->boolean('is_featured');

        $post->fill($data);

        // 削除フラグ
        $remove = $request->boolean('thumbnail_remove') || $request->boolean('remove_thumbnail');
        if ($remove) {
            $this->deleteOldThumb($post);
            $this->setThumbColumn($post, null);
        }

        $this->handleUploadAndPersist($request, $post);

        if (array_key_exists('category_id', $data)) {
            $post->category()->associate($data['category_id'] ?: null);
        }

        $post->save();
        if ($tags) $post->tags()->sync($tags);

        return back()->with('status', 'updated');
    }

    /** 削除 */
    public function destroy(Post $post)
    {
        $this->deleteOldThumb($post);
        $post->tags()->detach();
        $post->delete();
        return redirect()->route('admin.posts.index')->with('status', 'deleted');
    }

    /* ===== Helper ===== */

    /**
     * アップロード処理＋DB反映（強力フォールバック）
     * 保存先は public ディスク配下の posts/ に統一
     */
    private function handleUploadAndPersist(Request $request, Post $post): void
    {
        $pre = trim((string)$request->input('preuploaded_thumbnail_path', ''));
        if ($pre === 'false' || $pre === '0') $pre = '';

        if ($pre !== '') {
            if (str_starts_with($pre, '/storage/')) {
                $pre = ltrim(str_replace('/storage/', '', $pre), '/');
            }
            if (Storage::disk('public')->exists($pre)) {
                $current = $this->getThumbPath($post);
                if ($current && $current !== $pre) {
                    Storage::disk('public')->delete($current);
                    Log::debug('thumb deleted (preupload adopt)', ['post_id' => $post->id, 'path' => $current]);
                }
                $this->setThumbColumn($post, $pre);
                $post->save();
                Log::debug('thumb attached from preupload', ['post_id' => $post->id, 'path' => $pre]);
                return;
            }
            Log::warning('preuploaded path not found', ['path' => $pre]);
        }

        // ▼ multipart
        Log::debug('thumb diag', [
            'route'   => optional($request->route())->getName(),
            'post_id' => $post->id ?? null,
            'hasFile' => $request->hasFile('thumbnail'),
            'error'   => $request->file('thumbnail')?->getError(),
            'size'    => $request->file('thumbnail')?->getSize(),
            'name'    => $request->file('thumbnail')?->getClientOriginalName(),
            'mtype'   => $request->file('thumbnail')?->getMimeType(),
            'pre'     => $pre ?: 'false',
        ]);

        if (!$request->hasFile('thumbnail')) {
            $current = $this->getThumbPath($post);
            if ($current === '0' || $current === '' || $current === null) {
                $this->setThumbColumn($post, null);
                $post->save();
                Log::debug('thumb set to null (no upload/preupload)', ['post_id' => $post->id]);
            }
            return;
        }

        $f = $request->file('thumbnail');
        if (!$f->isValid()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'thumbnail' => 'アップロードに失敗しました（エラーコード: '.$f->getError().'）。'
            ]);
        }

        // 保存先確保（posts）
        Storage::disk('public')->makeDirectory('posts');

        // tmp の実在確認（デバッグ用）
        $tmp = method_exists($f, 'getPathname') ? $f->getPathname() : null;
        Log::debug('thumb tmp check', [
            'tmp'      => $tmp,
            'is_file'  => $tmp ? is_file($tmp) : null,
            'readable' => $tmp ? is_readable($tmp) : null,
            'is_up'    => $tmp ? is_uploaded_file($tmp) : null,
        ]);

        $prevThrow = config('filesystems.disks.public.throw');
        config(['filesystems.disks.public.throw' => true]);

        $path = false;

        // 1) putFile（public/posts）
        try {
            $path = Storage::disk('public')->putFile('posts', $f);
            Log::debug('thumb putFile result', [
                'path'   => $path,
                'exists' => $path ? Storage::disk('public')->exists($path) : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('thumb putFile exception', ['post_id' => $post->id, 'msg' => $e->getMessage()]);
            $path = false;
        }

        // 2) move() フォールバック（絶対パスへ）
        if (!$path) {
            try {
                $ext = strtolower($f->getClientOriginalExtension() ?: ($f->extension() ?: ($f->guessExtension() ?: 'bin')));
                $destRel = 'posts/'.Str::uuid()->toString().'.'.$ext;
                $destAbs = storage_path('app/public/'.$destRel);

                if (!is_dir(dirname($destAbs))) {
                    @mkdir(dirname($destAbs), 0775, true);
                }
                $moved = $f->move(dirname($destAbs), basename($destAbs)); // Symfony UploadedFile::move
                if ($moved && is_file($destAbs)) {
                    $path = $destRel;
                }
                Log::debug('thumb move() result', [
                    'dest_rel' => $destRel,
                    'dest_abs' => $destAbs,
                    'moved'    => (bool) $moved,
                    'exists'   => is_file($destAbs),
                ]);
            } catch (\Throwable $e) {
                Log::error('thumb move() exception', ['post_id' => $post->id, 'msg' => $e->getMessage()]);
                $path = false;
            }
        }

        // 3) stream コピー（Storage::put）
        if (!$path) {
            try {
                $ext = strtolower($f->getClientOriginalExtension() ?: ($f->extension() ?: ($f->guessExtension() ?: 'bin')));
                $dest = 'posts/'.Str::uuid()->toString().'.'.$ext;
                $streamPath = method_exists($f, 'getRealPath') ? $f->getRealPath() : $f->getPathname();
                $ok = false;
                if ($streamPath && is_readable($streamPath)) {
                    $stream = fopen($streamPath, 'r');
                    if ($stream) {
                        $ok = Storage::disk('public')->put($dest, $stream);
                        fclose($stream);
                    }
                }
                if ($ok && Storage::disk('public')->exists($dest)) {
                    $path = $dest;
                }
                Log::debug('thumb writeStream result', [
                    'dest'   => $dest ?? null,
                    'ok'     => $ok ?? null,
                    'exists' => isset($dest) ? Storage::disk('public')->exists($dest) : null,
                ]);
            } catch (\Throwable $e) {
                Log::error('thumb writeStream exception', ['post_id' => $post->id, 'msg' => $e->getMessage()]);
                $path = false;
            }
        }

        config(['filesystems.disks.public.throw' => $prevThrow]);

        // 最終判定
        if (!$path || !Storage::disk('public')->exists($path)) {
            Log::error('thumb store failed', [
                'post_id' => $post->id,
                'path'    => $path,
                'tmp'     => $tmp,
            ]);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'thumbnail' => '画像の保存に失敗しました。権限/ディレクトリ/設定を確認してください。'
            ]);
        }

        // 成功 → 旧画像削除
        $current = $this->getThumbPath($post);
        if ($current && $current !== $path) {
            Storage::disk('public')->delete($current);
            Log::debug('thumb deleted (after store)', ['post_id' => $post->id, 'path' => $current]);
        }

        // DB反映
        if (Schema::hasColumn('posts', 'thumbnail_path')) {
            $post->thumbnail_path = $path;
        } elseif (Schema::hasColumn('posts', 'thumbnail')) {
            $post->thumbnail = $path;
        }
        $post->save();

        Log::debug('thumb stored', ['post_id' => $post->id, 'path' => $path]);
    }

    private function deleteOldThumb(Post $post): void
    {
        $current = $this->getThumbPath($post);
        if ($current) {
            Storage::disk('public')->delete($current);
            Log::debug('thumb deleted', ['post_id' => $post->id, 'path' => $current]);
        }
    }

    private function getThumbPath(Post $post): ?string
    {
        if (Schema::hasColumn('posts', 'thumbnail_path') && !empty($post->thumbnail_path)) {
            return $post->thumbnail_path;
        }
        if (Schema::hasColumn('posts', 'thumbnail') && !empty($post->thumbnail)) {
            return $post->thumbnail;
        }
        return null;
    }

    private function setThumbColumn(Post $post, ?string $value): void
    {
        if (Schema::hasColumn('posts', 'thumbnail_path')) {
            $post->thumbnail_path = $value;
        }
        if (Schema::hasColumn('posts', 'thumbnail')) {
            $post->thumbnail = $value;
        }
    }
}
