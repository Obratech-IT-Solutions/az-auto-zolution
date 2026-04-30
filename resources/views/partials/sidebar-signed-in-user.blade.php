@auth
@php
    $u = auth()->user();
    $nr = $u?->normalizedRole();
    $positionLabel = match ($nr) {
        \App\Models\User::ROLE_ADMIN => 'Administrator',
        \App\Models\User::ROLE_CASHIER => 'Cashier',
        default => $nr ? \Illuminate\Support\Str::title($nr) : '—',
    };
@endphp
<div class="sidebar-user-block" title="{{ $u->email }}">
  <div class="sidebar-user-name text-truncate">{{ $u->name }}</div>
  <div class="sidebar-user-role text-truncate">{{ $positionLabel }}</div>
</div>
@endauth
