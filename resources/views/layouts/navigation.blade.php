<nav class="main-nav">
  <div class="nav-container">
    <a class="nav-brand" href="{{ route('/') }}">
      <span class="brand-text">ARKAN</span>
    </a>
    <button class="nav-toggle" onclick="toggleNav()">
      <span class="nav-toggle-icon"></span>
    </button>

    <div class="nav-menu" id="navMenu">
      <ul class="nav-list">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('/') ? 'active' : '' }}" href="{{ route('/') }}">Home</a>
        </li>

        @auth
        @if(auth()->user()->role == 'manager')
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('attendances.index') ? 'active' : '' }}"
            href="{{ route('attendances.index') }}">Attendance Records</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('leaves.index') ? 'active' : '' }}"
            href="{{ route('leaves.index') }}">Leave Records</a>
        </li>
        @endif

        @if(auth()->user()->role == 'employee')
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('attendances.create') ? 'active' : '' }}"
            href="{{ route('attendances.create') }}">Mark Attendance</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('leaves.create') ? 'active' : '' }}"
            href="{{ route('leaves.create') }}">Mark Leave</a>
        </li>
        @endif

        <!-- Notification Bell -->
        <li class="nav-item notification-dropdown">
          <a class="nav-link" href="#" onclick="toggleNotifications(event)">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">0</span>
          </a>
          <div class="dropdown-content notifications-dropdown">
            <div class="dropdown-header">
              <h6>Notifications</h6>
            </div>
            <div class="notifications-container">
              <!-- Notifications will be loaded here -->
            </div>
          </div>
        </li>

        <!-- User Dropdown -->
        <li class="nav-item user-dropdown">
          <a class="nav-link" href="#" onclick="toggleUserMenu(event)">
            {{ Auth::user()->name }}
          </a>
          <ul class="dropdown-content">
            <li><a href="/user/profile">Profile</a></li>
            <li class="dropdown-divider"></li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-button">Logout</button>
              </form>
            </li>
          </ul>
        </li>
        @else
        <li class="nav-item">
          <a class="nav-link" href="{{ route('login') }}">Login</a>
        </li>
        @endauth
      </ul>
    </div>
  </div>
</nav>

<style>
  .main-nav {
    background-color: #212529;
    padding: 1rem 0;
    margin-bottom: 20px;
  }

  .nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .nav-brand {
    color: white;
    text-decoration: none;
    font-size: 1.5rem;
    font-weight: bold;
  }

  .nav-toggle {
    display: none;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 0.5rem;
  }

  .nav-toggle-icon {
    display: block;
    width: 25px;
    height: 2px;
    background-color: white;
    position: relative;
  }

  .nav-toggle-icon::before,
  .nav-toggle-icon::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 2px;
    background-color: white;
    left: 0;
  }

  .nav-toggle-icon::before {
    top: -6px;
  }

  .nav-toggle-icon::after {
    bottom: -6px;
  }

  .nav-menu {
    display: flex;
    align-items: center;
  }

  .nav-list {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 1rem;
  }

  .nav-link {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    padding: 0.5rem 1rem;
    transition: color 0.3s;
  }

  .nav-link:hover,
  .nav-link.active {
    color: white;
  }

  /* Dropdowns */
  .notification-dropdown,
  .user-dropdown {
    position: relative;
  }

  .dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    min-width: 200px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    border-radius: 4px;
    z-index: 1000;
  }

  .notifications-dropdown {
    width: 300px;
    max-height: 400px;
    overflow-y: auto;
  }

  .dropdown-header {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #eee;
  }

  .dropdown-content a,
  .dropdown-button {
    display: block;
    padding: 0.5rem 1rem;
    color: #333;
    text-decoration: none;
    transition: background-color 0.3s;
  }

  .dropdown-button {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    cursor: pointer;
  }

  .dropdown-content a:hover,
  .dropdown-button:hover {
    background-color: #f8f9fa;
  }

  .dropdown-divider {
    height: 1px;
    background-color: #eee;
    margin: 0.5rem 0;
  }

  /* Notification Badge */
  .notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    transform: translate(50%, -50%);
    background-color: #dc3545;
    color: white;
    font-size: 0.6rem;
    padding: 0.25rem 0.5rem;
    border-radius: 10px;
    display: none;
  }

  /* Notification Items */
  .notification-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    transition: background-color 0.3s;
  }

  .notification-item:hover {
    background-color: #f8f9fa;
  }

  .notification-item.unread {
    background-color: #f0f7ff;
  }

  .notification-content {
    margin-bottom: 5px;
  }

  .notification-time {
    font-size: 0.8rem;
    color: #6c757d;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .nav-toggle {
      display: block;
    }

    .nav-menu {
      display: none;
      width: 100%;
      position: absolute;
      top: 100%;
      left: 0;
      background-color: #212529;
      padding: 1rem;
    }

    .nav-menu.active {
      display: block;
    }

    .nav-list {
      flex-direction: column;
      align-items: flex-start;
    }

    .dropdown-content {
      position: static;
      box-shadow: none;
      width: 100%;
    }
  }
</style>

<script>
  function toggleNav() {
    const navMenu = document.getElementById('navMenu');
    navMenu.classList.toggle('active');
  }

  function toggleNotifications(event) {
    event.preventDefault();
    const dropdown = event.currentTarget.nextElementSibling;
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
  }

  function toggleUserMenu(event) {
    event.preventDefault();
    const dropdown = event.currentTarget.nextElementSibling;
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
  }

  // Close dropdowns when clicking outside
  document.addEventListener('click', function(event) {
    if (!event.target.closest('.notification-dropdown')) {
      document.querySelectorAll('.notifications-dropdown').forEach(dropdown => {
        dropdown.style.display = 'none';
      });
    }
    if (!event.target.closest('.user-dropdown')) {
      document.querySelectorAll('.user-dropdown .dropdown-content').forEach(dropdown => {
        dropdown.style.display = 'none';
      });
    }
  });

  // Notifications update logic
  document.addEventListener('DOMContentLoaded', function() {
    const updateNotifications = () => {
      fetch('/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
          const badge = document.querySelector('.notification-badge');
          if (data.count > 0) {
            badge.style.display = 'inline';
            badge.textContent = data.count;
          } else {
            badge.style.display = 'none';
          }
        });

      fetch('/notifications')
        .then(response => response.text())
        .then(html => {
          document.querySelector('.notifications-container').innerHTML = html;
        });
    };

    updateNotifications();
    setInterval(updateNotifications, 30000);

    document.querySelector('.notifications-container').addEventListener('click', function(e) {
      const notificationLink = e.target.closest('.notification-link');
      if (notificationLink) {
        e.preventDefault();
        fetch(notificationLink.dataset.markAsRead)
          .then(response => response.json())
          .then(() => {
            window.location.href = notificationLink.href;
          });
      }
    });
  });
</script>