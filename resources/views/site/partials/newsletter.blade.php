<form id="newsletter" method="POST" action="{{ route('site.newsletter.store') }}" class="mt-4" style="max-width:340px" novalidate>
    @csrf
    <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;opacity:0">
    <label for="newsletter-email" class="d-block mb-2" style="font-size:.85rem;font-weight:600">Product news &amp; playbooks, no spam</label>
    <div class="d-flex gap-2">
        <input type="email" name="email" id="newsletter-email" class="form-control @error('email', 'newsletter') is-invalid @enderror"
               placeholder="you@company.com" value="{{ old('email') }}" required>
        <button type="submit" class="btn btn-cobalt" style="white-space:nowrap;padding:.5rem 1rem">Subscribe</button>
    </div>
    @if (session('newsletter_status'))
        <div class="mt-2" style="font-size:.85rem;color:var(--ok-green)" role="status">{{ session('newsletter_status') }}</div>
    @endif
    @error('email', 'newsletter')
        <div class="mt-2 text-danger" style="font-size:.85rem">{{ $message }}</div>
    @enderror
</form>
