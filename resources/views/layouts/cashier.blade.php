<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>
    @hasSection('title')
    @yield('title') — {{ config('app.name') }}
    @else
    {{ config('app.name') }}
    @endif
  </title>
  <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">

  <!-- Icons & CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #1e3c72, #2a5298);
      color: #333;
    }
    .sidebar {
      width: 260px; height: 100vh; position: fixed;
      background: #1c1f26; box-shadow: 4px 0 10px rgba(0,0,0,0.2);
      color: #fff;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .sidebar-links {
      flex: 1 1 auto;
      min-height: 0;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
    }
    .sidebar-footer {
      flex-shrink: 0;
      padding: 12px 16px 20px;
      background: #1c1f26;
      border-top: 1px solid rgba(255,255,255,0.08);
    }
    .sidebar-user-block {
      margin-bottom: 12px;
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .sidebar-user-name {
      font-size: 0.95rem;
      font-weight: 600;
      color: #f8f9fa;
      line-height: 1.3;
    }
    .sidebar-user-role {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.55);
      margin-top: 2px;
    }
    .sidebar .logo-container { text-align: center; padding: 25px 0 15px; }
    .sidebar .logo-container img {
      width:90px; height:90px; object-fit:cover;
      border-radius:50%; border:2px solid #ffffff88;
    }
    .sidebar h4 { font-size:20px; font-weight:bold; margin-top:10px; color:#f8f9fa; }
    .sidebar a {
      display:flex; align-items:center;
      padding:14px 24px; font-size:15px;
      color:#dee2e6; text-decoration:none;
      transition: background 0.2s;
    }
    .sidebar a i { margin-right:12px; font-size:18px; }
    .sidebar a:hover { background:#495057; color:#fff; }
    .sidebar a.sidebar-nav-active {
      background: rgba(74, 144, 226, 0.28);
      color: #fff;
      font-weight: 600;
      box-shadow: inset 4px 0 0 #4a90e2;
    }
    .sidebar a.sidebar-nav-active:hover {
      background: rgba(74, 144, 226, 0.4);
      color: #fff;
    }
    .sidebar a.sidebar-nav-active i {
      color: #a8c9f0;
    }
    .content {
      margin-left:260px; padding:40px 30px;
      background:#f1f4f9; min-height:100vh;
      box-shadow: inset 0 0 8px rgba(0,0,0,0.05);
      border-radius:8px;
      animation: fadeIn 0.4s ease-in-out;
      position: relative;
    }
    @keyframes fadeIn {
      from { opacity:0; transform: translateY(10px); }
      to   { opacity:1; transform: translateY(0);  }
    }
    /* Notification and Clock Row — must sit above sticky page headers (e.g. Clients & Vehicles z-index 1020–1030) */
    .notif-row {
      position: absolute;
      top: 20px;
      right: 32px;
      z-index: 1040;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .header-clock {
      font-size: 1.13rem;
      color: #212529;
      font-weight: bold;
      background: none;
      border: none;
      box-shadow: none;
      padding: 0;
      margin: 0;
      letter-spacing: 0.5px;
      min-width: 175px;
      text-align: right;
      user-select: none;
    }
    .notif-bell-box .btn {
      background: none;
      border: none;
      box-shadow: none;
      padding: 0;
    }
    .notif-bell-box .fa-bell {
      font-size: 28px;
    }
    .notif-bell-box .badge {
      position: absolute;
      top: -7px;
      right: -9px;
      font-size: 0.72em;
      padding: 3px 6px;
      border-radius: 50%;
    }
    .notif-bell-box {
      position: relative;
    }
    /* Above sticky tables; below Bootstrap modals (1055) */
    .notif-bell-box .dropdown-menu {
      z-index: 1045;
    }
    .dropdown-menu {
      min-width: 240px;
      max-width: 330px;
      font-size: 1rem;
    }
    .low-stock-scroll {
      max-height: min(320px, 50vh);
      overflow-y: auto;
      overscroll-behavior: contain;
    }
    .low-stock-item-row {
      padding: 6px 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 8px;
    }
    .low-stock-item-row .low-stock-label {
      min-width: 0;
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .dropdown-header {
      font-size: 1.05em;
      font-weight: bold;
      background: #f8d7da;
      color: #b30000;
    }
    .dropdown-divider {
      margin: 0;
    }
    .low-stock-clear-all {
      font-size: 0.8rem;
      white-space: nowrap;
    }
  </style>
</head>

<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="logo-container flex-shrink-0">
        <img src="{{ asset('images/logo.png') }}" alt="Logo">
        <h4 class="mt-2">AZ Auto Zolutions</h4>
      </div>
      <nav class="sidebar-links" aria-label="Main navigation">
        <a href="{{ route('cashier.dashboard') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.dashboard', 'cashier.home')]) @if(request()->routeIs('cashier.dashboard', 'cashier.home')) aria-current="page" @endif><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="{{ route('cashier.appointment.index') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.appointment.*')]) @if(request()->routeIs('cashier.appointment.*')) aria-current="page" @endif><i class="fas fa-calendar-check"></i> Appointments</a>
        <a href="{{ route('cashier.quotation.index') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.quotation.*')]) @if(request()->routeIs('cashier.quotation.*')) aria-current="page" @endif><i class="fas fa-file-alt"></i> Quotation</a>
        <a href="{{ route('cashier.service-order') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.serviceorder.*', 'cashier.service-order')]) @if(request()->routeIs('cashier.serviceorder.*', 'cashier.service-order')) aria-current="page" @endif><i class="fas fa-tools"></i> Service Orders</a>
        <a href="{{ route('cashier.invoice.index') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.invoice.*', 'cashier.invoice-blank')]) @if(request()->routeIs('cashier.invoice.*', 'cashier.invoice-blank')) aria-current="page" @endif><i class="fas fa-file-invoice"></i> Invoicing</a>
        <a href="{{ route('cashier.sms.index') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.sms.*')]) @if(request()->routeIs('cashier.sms.*')) aria-current="page" @endif><i class="fas fa-sms"></i> SMS</a>
        <a href="{{ route('cashier.inventory.index') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.inventory.*')]) @if(request()->routeIs('cashier.inventory.*')) aria-current="page" @endif><i class="fas fa-boxes"></i> Inventory</a>
        <a href="{{ route('cashier.expenses.index') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.expenses.*')]) @if(request()->routeIs('cashier.expenses.*')) aria-current="page" @endif><i class="fas fa-wallet"></i> Expenses</a>
        <a href="{{ route('cashier.ar-cashdeposit.index') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.ar-cashdeposit.*')]) @if(request()->routeIs('cashier.ar-cashdeposit.*')) aria-current="page" @endif><i class="fas fa-hand-holding-usd"></i> A/R & Cash Deposit</a>
        <a href="{{ route('cashier.vehicles.index') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.vehicles.*', 'cashier.clients.*')]) @if(request()->routeIs('cashier.vehicles.*', 'cashier.clients.*')) aria-current="page" @endif><i class="fas fa-users"></i> Clients & Vehicles</a>
        <a href="{{ route('cashier.history') }}" @class(['sidebar-nav-active' => request()->routeIs('cashier.history', 'cashier.history.*')]) @if(request()->routeIs('cashier.history', 'cashier.history.*')) aria-current="page" @endif><i class="fas fa-history"></i> History</a>
      </nav>
      <div class="sidebar-footer">
        @include('partials.sidebar-signed-in-user')
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="btn btn-danger w-100">
            <i class="fas fa-sign-out-alt"></i> Logout
          </button>
        </form>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content w-100">
      <!-- Time and Notification Bell -->
      <div class="notif-row">
        <div class="header-clock" id="headerClock">
          <!-- JS will set date and time here -->
        </div>
        <div class="notif-bell-box">
          <div class="dropdown">
            <button class="btn position-relative" id="notifBell" data-bs-toggle="dropdown" aria-expanded="false"
              data-low-stock-total="{{ (int) ($lowStockCount ?? 0) }}"
              data-low-stock-rendered="{{ isset($lowStockItems) ? $lowStockItems->count() : 0 }}"
              style="color: {{ ($lowStockCount ?? 0) > 0 ? '#c30000' : '#adb5bd' }};">
              <i class="fas fa-bell"></i>
              @if(($lowStockCount ?? 0) > 0)
                {{-- Badge = alerts in this list (capped preview); × / Clear all updates via JS — no phantom remainder count --}}
                <span class="badge bg-danger">{{ $lowStockItems->count() }}</span>
              @endif
            </button>
            @if(($lowStockCount ?? 0) > 0)
              <ul class="dropdown-menu dropdown-menu-end shadow p-0" style="min-width: 280px; max-width: 380px;" aria-labelledby="notifBell">
                <li class="dropdown-header px-3 py-2 mb-0 rounded-top d-flex align-items-center justify-content-between gap-2 flex-wrap">
                  <span class="text-truncate" style="min-width: 0;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Low Inventory (≤ 5 units)
                  </span>
                  <button type="button" class="btn btn-link btn-sm text-danger p-0 low-stock-clear-all flex-shrink-0 text-decoration-none" title="Hide every alert in this list (browser only)">Clear all</button>
                </li>
                <li><hr class="dropdown-divider my-0"></li>
                <li class="p-0">
                  <ul class="list-unstyled mb-0 low-stock-scroll">
                    @foreach($lowStockItems as $item)
                      <li class="low-stock-item-row border-bottom" data-low-stock-id="{{ $item->id }}">
                        <span class="low-stock-label" title="{{ $item->item_name }}"><b>{{ $item->item_name }}</b></span>
                        <span class="badge bg-warning text-dark flex-shrink-0">{{ $item->quantity }}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary low-stock-dismiss flex-shrink-0 py-0 px-2" title="Hide this alert" aria-label="Dismiss notification">&times;</button>
                      </li>
                    @endforeach
                  </ul>
                </li>
                @if($lowStockCount > $lowStockItems->count())
                  <li class="px-3 py-2 small text-muted border-top">
                    Showing {{ $lowStockItems->count() }} of {{ $lowStockCount }}. Open Inventory to see or restock the rest.
                  </li>
                @endif
              </ul>
            @endif
          </div>
        </div>
      </div>
      @yield('content')
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Simple Clock: 11:53 PM May 21, 2025
    function updateHeaderClock() {
      const clockElem = document.getElementById('headerClock');
      const now = new Date();
      let hour = now.getHours();
      const min = now.getMinutes().toString().padStart(2, '0');
      const ampm = hour >= 12 ? 'PM' : 'AM';
      hour = hour % 12 || 12;
      const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      const dateStr = `${months[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;
      const timeStr = `${hour}:${min} ${ampm} ${dateStr}`;
      clockElem.textContent = timeStr;
    }
    setInterval(updateHeaderClock, 1000);
    window.onload = updateHeaderClock;
  </script>
  <script>
    (function () {
      var KEY = 'az_cashier_lowstock_dismissed_v1';
      var MAX_IDS = 800;
      function parseDismissed() {
        try {
          var a = JSON.parse(localStorage.getItem(KEY) || '[]');
          return Array.isArray(a) ? a.map(Number).filter(Boolean) : [];
        } catch (e) { return []; }
      }
      function saveDismissed(ids) {
        var uniq = [];
        var seen = {};
        ids.forEach(function (id) {
          if (!seen[id]) { seen[id] = true; uniq.push(id); }
        });
        if (uniq.length > MAX_IDS) uniq = uniq.slice(-MAX_IDS);
        localStorage.setItem(KEY, JSON.stringify(uniq));
      }
      function updateLowStockBadge() {
        var btn = document.getElementById('notifBell');
        if (!btn) return;
        /** Ongoing = rows still showing (not dismissed with ×). No extra phantom count for SKUs beyond the dropdown preview. */
        var n = document.querySelectorAll('.low-stock-item-row[data-low-stock-id]:not(.d-none)').length;
        var badge = btn.querySelector('.badge');
        if (badge) {
          badge.textContent = String(n);
          badge.classList.toggle('d-none', n <= 0);
        }
        btn.style.color = n > 0 ? '#c30000' : '#adb5bd';
      }
      function applyDismissed() {
        var dismissed = new Set(parseDismissed());
        document.querySelectorAll('.low-stock-item-row[data-low-stock-id]').forEach(function (el) {
          var id = parseInt(el.getAttribute('data-low-stock-id'), 10);
          if (dismissed.has(id)) el.classList.add('d-none');
        });
        updateLowStockBadge();
      }
      document.querySelectorAll('.low-stock-dismiss').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          var row = btn.closest('.low-stock-item-row');
          if (!row) return;
          var id = parseInt(row.getAttribute('data-low-stock-id'), 10);
          if (!id) return;
          var arr = parseDismissed();
          if (arr.indexOf(id) === -1) arr.push(id);
          saveDismissed(arr);
          row.classList.add('d-none');
          updateLowStockBadge();
        });
      });
      var clearAllBtn = document.querySelector('.low-stock-clear-all');
      if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          var arr = parseDismissed();
          document.querySelectorAll('.low-stock-item-row[data-low-stock-id]').forEach(function (el) {
            var id = parseInt(el.getAttribute('data-low-stock-id'), 10);
            if (id && arr.indexOf(id) === -1) arr.push(id);
            el.classList.add('d-none');
          });
          saveDismissed(arr);
          updateLowStockBadge();
        });
      }
      applyDismissed();
    })();
  </script>
</body>
</html>
