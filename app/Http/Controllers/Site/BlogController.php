<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): View
    {
        return $this->listing($request, null);
    }

    public function category(Request $request, string $category): View
    {
        return $this->listing($request, BlogCategory::where('slug', $category)->firstOrFail());
    }

    public function show(string $slug): View
    {
        $post = BlogPost::published()->with(['category', 'author'])->where('slug', $slug)->firstOrFail();

        // Same category first, then newest.
        $related = BlogPost::published()->with('category')
            ->whereKeyNot($post->id)
            ->orderByRaw('(blog_category_id = ?) desc', [$post->blog_category_id ?? 0])
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('site.blog.show', compact('post', 'related'));
    }

    private function listing(Request $request, ?BlogCategory $category): View
    {
        $q = trim((string) $request->query('q', ''));

        $posts = BlogPost::published()->with(['category', 'author'])
            ->when($category, fn ($query) => $query->where('blog_category_id', $category->id))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhere('excerpt', 'like', $like)
                    ->orWhere('body', 'like', $like));
            })
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Like the static design: the newest article leads the unfiltered first page.
        $featured = null;
        $items = $posts->getCollection();
        if (! $category && $q === '' && $posts->currentPage() === 1 && $items->isNotEmpty()) {
            $featured = $items->first();
            $items = $items->slice(1)->values();
        }

        return view('site.blog.index', [
            'category' => $category,
            'categories' => BlogCategory::orderBy('id')->get(),
            'posts' => $posts,
            'featured' => $featured,
            'items' => $items,
            'q' => $q,
        ]);
    }
}
