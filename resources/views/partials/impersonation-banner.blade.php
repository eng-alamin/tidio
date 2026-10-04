@php($impersonation = \App\Support\Impersonation::data())
@if ($impersonation)
  <div role="status" style="position:sticky; top:0; z-index:9999; display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:12px; padding:8px 16px; background:#b42318; color:#fff; font-size:.85rem; font-weight:600;">
    <span>
      <i class="bi bi-person-badge" aria-hidden="true"></i>
      Viewing as {{ $impersonation['user_name'] }} in {{ $impersonation['workspace_name'] }}.
      Changes you make are real. {{ \App\Support\Impersonation::minutesLeft($impersonation) }} min left.
    </span>
    <form method="POST" action="{{ route('impersonation.stop') }}" style="margin:0;">
      @csrf
      <button type="submit" style="background:#fff; color:#b42318; border:0; border-radius:6px; padding:4px 12px; font-weight:700; cursor:pointer;">Exit impersonation</button>
    </form>
  </div>
@endif
