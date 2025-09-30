<?php
namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LandingController extends Controller
{
    public function index()
    {
        $posts = DB::table('posts')
            ->when(Schema::hasColumn('posts','published_at'), fn($q) => $q->orderByDesc('published_at'))
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('welcome', compact('posts'));
    }
}
