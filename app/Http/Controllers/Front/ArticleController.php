<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $query = Post::query();
        if (method_exists(Post::class, 'published')) {
            $query->published();
        }

        $articles = $query
            ->when($q !== '', fn($qq) => $qq->where('title','like',"%{$q}%"))
            ->latest('published_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('front.articles.index', compact('articles','q'));
    }

    public function show(string $slug)
    {
        $query = Post::query();
        if (method_exists(Post::class, 'published')) {
            $query->published();
        }

        $article = $query->where('slug', $slug)->firstOrFail();

        return view('front.articles.show', compact('article'));
    }
}
