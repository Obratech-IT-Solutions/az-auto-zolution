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
      width: 260px;
      height: 100vh;
      position: fixed;
      background: #1c1f26;
      box-shadow: 4px 0 10px rgba(0,0,0,0.2);
      color: #fff;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      z-index: 1051;
      left: 0;
      top: 0;
      transition: transform 0.25s;
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
      width: 90px; height: 90px; object-fit: cover;
      border-radius: 50%; border: 2px solid #ffffff88;
    }
    .sidebar h4 { font-size: 20px; font-weight: bold; margin-top: 10px; color: #f8f9fa; }
    .sidebar a {
      display: flex; align-items: center;
      padding: 14px 24px; font-size: 15px;
      color: #dee2e6; text-decoration: none;
      transition: background 0.2s;
    }
    .sidebar a i { margin-right: 12px; font-size: 18px; }
    .sidebar a:hover { background: #495057; color: #fff; }
    .logout-btn {
      position: absolute; bottom: 20px; left: 20px;
      width: calc(100% - 40px);
    }
    .content {
      margin-left: 260px; padding: 40px 30px;
      background: #f1f4f9; min-height: 100vh;
      box-shadow: inset 0 0 8px rgba(0,0,0,0.05);
      border-radius: 8px;
      animation: fadeIn 0.4s ease-in-out;
      position: relative;
      transition: margin-left 0.25s;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px);}
      to   { opacity: 1; transform: translateY(0);}
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
      min-width: 185px;
      text-align: right;
      user-select: none;
      position: absolute;
      top: 20px;
      right: 32px;
      z-index: 999;
    }
    /* Hamburger */
    .sidebar-toggle {
      display: none;
      position: fixed;
      top: 18px;
      left: 18px;
      z-index: 1100;
      background: #fff;
      border-radius: 50%;
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
      border: none;
      width: 42px; height: 42px;
      align-items: center; justify-content: center;
    }
    .sidebar-toggle i {
      font-size: 1.4rem;
      color: #233;
    }
    /* Responsive */
    @media (max-width: 991.98px) {
      .sidebar {
        transform: translateX(-100%);
      }
      .sidebar.show {
        transform: translateX(0);
      }
      .content {
        margin-left: 0;
        padding: 24px 4vw;
      }
      .sidebar-toggle {
        display: flex;
      }
      .header-clock {
        position: static;
        margin-bottom: 10px;
        text-align: right;
        width: 100%;
        min-width: 0;
      }
    }
    @media (max-width: 575.98px) {
      .content { padding: 12vw 2vw; }
      .sidebar { width: 95vw; }
    }
    /* Overlay on mobile */
    .sidebar-backdrop {
      display: none;
      position: fixed;
      z-index: 1050;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(40, 48, 66, 0.36);
    }
    .sidebar-backdrop.active {
      display: block;
    }
  </style>
</head>
<body>
  <!-- Hamburger for mobile -->
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open Sidebar">
    <i class="fas fa-bars"></i>
  </button>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="logo-container flex-shrink-0">
        <img src="{{ asset('images/logo.png') }}" alt="Logo">
        <h4 class="mt-2">AZ Auto Zolutions</h4>
      </div>
      <nav class="sidebar-links" aria-label="Main navigation">
      <a href="{{ route('admin.home') }}"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="{{ route('admin.sales-report') }}"><i class="fas fa-chart-line"></i> Sales Report</a>
      <a href="{{ route('admin.gross-sales-report') }}"><i class="fas fa-coins"></i> Gross Sales Report</a>
      <a href="{{ route('admin.income-analysis-report') }}"><i class="fas fa-chart-pie"></i> Income Analysis</a>
      <a href="{{ route('admin.discount-report') }}"><i class="fa-dollar-sign"></i> Discount Report</a>
      <a href="{{ route('admin.email-employee') }}"><i class="fas fa-envelope"></i> EMAIL/EMPLOYEE</a>
      <a href="{{ route('admin.invoices') }}"><i class="fas fa-file-invoice"></i> Invoices</a>
      <a href="{{ route('admin.inventory') }}"><i class="fas fa-warehouse"></i> Inventory</a>
      <a href="{{ route('admin.material-summary') }}"><i class="fas fa-clipboard-list"></i> Material Summary</a>
      <a href="{{ route('admin.labor-summary') }}"><i class="fas fa-users"></i> Labor Summary</a>
      <a href="{{ route('admin.trends') }}"><i class="fas fa-chart-line"></i> Trends</a>
      <a href="{{ route('admin.sms.index') }}"><i class="fas fa-sms"></i> SMS</a>
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
      <div class="header-clock" id="headerClock"></div>
      @yield('content')
    </div>
  </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
  <script>
    // Clock
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

    // Sidebar toggle for mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    sidebarToggle.onclick = function() {
      sidebar.classList.toggle('show');
      sidebarBackdrop.classList.toggle('active');
    };
    sidebarBackdrop.onclick = function() {
      sidebar.classList.remove('show');
      this.classList.remove('active');
    };
  </script>
</body>
</html>
