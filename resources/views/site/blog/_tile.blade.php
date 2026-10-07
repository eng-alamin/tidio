<a class="tile" href="{{ $post->url }}">
    <div class="post-thumb glass" style="padding:0">
        @if ($post->cover_image)
            <img src="{{ $post->cover_image }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">
        @else
            <i class="bi {{ $post->icon }}"></i>
        @endif
    </div>
    @if ($post->category)<span class="tag">{{ $post->category->name }}</span>@endif
    <h3>{{ $post->title }}</h3>
    <p>{{ $post->excerpt }}</p>
    <p style="margin-top:.9rem;font-size:.82rem">{{ $post->published_at->format('M j, Y') }} · {{ $post->read_minutes }} min read</p>
</a>
