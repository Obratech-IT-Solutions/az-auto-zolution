<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — {{ config('app.name') }}</title>
  <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      background: url('/images/cover.jpg') center center no-repeat;
      background-size: cover;
      position: relative;
    }
    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: rgba(25, 27, 32, 0.62);
      z-index: 0;
    }
    .center-container {
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
      z-index: 1;
    }
    .card {
      background: rgba(255,255,255,0.98);
      border-radius: 5px;
      border: 1px solid #ffd71a33;
      box-shadow: 0 8px 32px 0 rgba(20,22,30,0.18);
      padding: 2.2rem 2rem 2rem 2rem;
      max-width: 340px;
      width: 100%;
      margin: 0 auto;
    }
    .login-title {
      font-family: 'Segoe UI', Arial, sans-serif;
      font-weight: 700;
      letter-spacing: 1px;
      text-align: center;
      margin-bottom: 1rem;
      color: #181b22;
      font-size: 1.18rem;
      text-shadow: 0 2px 8px #fff6;
    }
    .form-label {
      font-weight: 600;
      font-size: 0.97rem;
      color: #232323;
      margin-bottom: .25rem;
    }
    .form-control {
      background: #f8f9fb;
      border-radius: 7px;
      border: 1px solid #dee2e6;
      font-size: 1rem;
    }
    .form-control:focus {
      border-color:rgb(0, 0, 0);
      box-shadow: 0 0 0 2pxrgba(31, 30, 29, 0.2);
      background: #fff;
    }
    .btn-primary {
      background: #FFD71A;
      border: none;
      color: #232323;
      font-weight: 700;
      font-size: 1.1rem;
      transition: background .17s;
      border-radius: 7px;
      letter-spacing: 1px;
      box-shadow: 0 2px 12px #FFD71A22;
    }
    .btn-primary:hover, .btn-primary:focus {
      background:rgb(0, 0, 0);
      color:rgb(255, 255, 255);
    }
    .footer-text {
      color: #aaaaaa;
      text-align: center;
      font-size: .93rem;
      margin-top: 2rem;
      letter-spacing: .5px;
    }
    .alert-danger {
      font-size: .98rem;
      padding: .8rem 1.1rem;
      border-radius: 8px;
      margin-bottom: 1.2rem;
      text-align: center;
    }
    @media (max-width: 480px) {
      .card {
        padding: 1.4rem 0.7rem 1.3rem 0.7rem;
        max-width: 99vw;
      }
      .logo-img { width: 42px; height: 42px;}
    }
  </style>
</head>
<body>
  <div class="center-container">
    <div class="card">
      <div class="login-title">AZ AUTO ZOLUTIONS</div>

      @if($errors->any())
        <div class="alert alert-danger">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input id="email" type="email" name="email" value="{{ old('email') }}"
                 class="form-control" placeholder="your@email.com" required autofocus>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input id="password" type="password" name="password"
                 class="form-control" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 mt-2">Log In</button>
      </form>
      <div class="footer-text mt-3">© {{ date('Y') }} AZ Auto Zolutions</div>
    </div>
  </div>
</body>
</html>
