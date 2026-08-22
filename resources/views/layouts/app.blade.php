<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Premium Crypto & Multi-Broker Trading Bot Platform">
    <title>@yield('title', 'Capital First') | Automated Trading</title>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v=5">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <nav class="navbar">
        <a href="{{ url('/') }}" class="navbar-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Capital First Logo" style="height: 32px; width: auto; border-radius: 4px;">
            <span class="text-gradient">Capital First</span>
        </a>
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle Navigation">
            ☰
        </button>
        <div class="navbar-nav" id="navbarNav">
            @guest
                <a href="{{ url('/') }}#about" class="nav-link">About Us</a>
                <a href="{{ url('/') }}#how-it-works" class="nav-link">How we work</a>
                <a href="{{ url('/') }}#algo" class="nav-link">How our Algo works</a>
                <a href="{{ url('/') }}#team" class="nav-link">Our Team</a>
                <a href="{{ url('/') }}#contact" class="nav-link">Contact</a>
                <a href="{{ route('login') }}" class="nav-link">Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Sign Up</a>
            @else
                <a href="{{ url('/dashboard') }}" class="nav-link">Dashboard</a>
                <a href="{{ route('bots.index') }}" class="nav-link">My Bots</a>
                <a href="{{ route('trades.index') }}" class="nav-link">Positions</a>
                <a href="{{ route('brokers.index') }}" class="nav-link">Connect Broker</a>
                <a href="{{ route('settings.index') }}" class="nav-link">Settings</a>
                
                @if(in_array(Auth::user()->role, ['superadmin', 'admin']))
                    <a href="{{ route('admin.dashboard') }}" class="nav-link" style="color: var(--accent-red); font-weight: 600;">Admin Panel</a>
                @endif

                <div class="user-profile" style="display: flex; align-items: center; gap: 0.75rem; margin-left: 1rem; border-left: 1px solid var(--border-glass); padding-left: 1rem;">
                    
                    <!-- Notification Bell -->
                    <div class="notification-dropdown" style="position: relative; margin-right: 10px;">
                        <button id="notificationToggle" style="background: none; border: none; cursor: pointer; color: var(--text-primary); font-size: 1.2rem; position: relative;">
                            🔔
                            @if(Auth::user()->unreadNotifications->count() > 0)
                                <span style="position: absolute; top: -5px; right: -5px; background: var(--accent-red); color: white; border-radius: 50%; font-size: 0.65rem; padding: 2px 6px; font-weight: bold;">
                                    {{ Auth::user()->unreadNotifications->count() }}
                                </span>
                            @endif
                        </button>
                        <div id="notificationMenu" class="notification-menu" style="display: none;">
                            <div style="padding: 15px; border-bottom: 1px solid var(--border-glass); display: flex; justify-content: space-between; align-items: center;">
                                <h4 style="margin: 0; color: white;">Notifications</h4>
                                @if(Auth::user()->unreadNotifications->count() > 0)
                                    <form method="POST" action="{{ route('notifications.markAllAsRead') }}" style="margin:0;">
                                        @csrf
                                        <button type="submit" style="background:none; border:none; color: var(--accent-blue); font-size: 0.8rem; cursor: pointer; text-decoration: underline;">Mark all as read</button>
                                    </form>
                                @endif
                            </div>
                            <div style="max-height: 400px; overflow-y: auto;">
                                @forelse(Auth::user()->notifications()->take(10)->get() as $notification)
                                    <div class="notification-item" style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); background: {{ $notification->read_at ? 'transparent' : 'rgba(255, 60, 60, 0.05)' }}; transition: all 0.3s ease; position: relative;">
                                        <div style="font-weight: bold; color: {{ $notification->read_at ? 'var(--text-secondary)' : 'var(--accent-red)' }}; margin-bottom: 5px; font-size: 0.9rem;">
                                            ⚠️ {{ $notification->data['bot_name'] ?? 'Bot Error' }}
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 5px;">
                                            {{ \Illuminate\Support\Str::limit($notification->data['error_message'] ?? '', 80) }}
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </div>
                                        @if(!$notification->read_at)
                                            <button onclick="markAsRead('{{ $notification->id }}')" style="position: absolute; right: 15px; top: 15px; background: none; border: none; color: var(--accent-blue); cursor: pointer; font-size: 0.8rem;">Dismiss</button>
                                        @endif
                                    </div>
                                @empty
                                    <div style="padding: 20px; text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
                                        No new notifications
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple)); display: flex; align-items: center; justify-content: center; font-weight: bold; color: #fff; font-size: 1.1rem; box-shadow: 0 2px 10px rgba(0,240,255,0.2);">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div style="display: flex; flex-direction: column; line-height: 1.2;">
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);">{{ Auth::user()->name }}</span>
                        <span style="font-size: 0.75rem; color: var(--text-secondary);">{{ Auth::user()->email }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="display: inline; margin-left: 1rem;">
                    @csrf
                    <button type="submit" class="btn btn-outline" style="padding: 0.5rem 1rem;">Logout</button>
                </form>
            @endguest
        </div>
    </nav>

    <main style="padding-top: 80px;">
        @yield('content')
    </main>

    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('navbarNav').classList.toggle('active');
        });

        // Notification Toggle
        const notifToggle = document.getElementById('notificationToggle');
        const notifMenu = document.getElementById('notificationMenu');
        
        if (notifToggle && notifMenu) {
            notifToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                notifMenu.style.display = notifMenu.style.display === 'none' ? 'block' : 'none';
            });

            document.addEventListener('click', function(e) {
                if (!notifMenu.contains(e.target)) {
                    notifMenu.style.display = 'none';
                }
            });
        }

        // Mark as Read AJAX
        function markAsRead(id) {
            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(response => response.json())
              .then(data => {
                  if(data.success) {
                      window.location.reload();
                  }
              });
        }
    </script>
</body>
</html>
