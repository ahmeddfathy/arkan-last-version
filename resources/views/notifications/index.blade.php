<style>
    /* General Notification Styling */
    .notification-item {
        background-color: #fff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease-in-out;
        overflow: hidden;
        font-family: 'Arial', sans-serif;
    }

    .notification-item.unread {
        background-color: #f0f8ff;
        border-left: 4px solid #007bff;
    }

    .notification-item:hover {
        transform: scale(1.02);
    }

    /* Notification Link */
    .notification-link {
        text-decoration: none;
        color: #333;
    }

    /* Notification Header */
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    /* Notification Title */
    .notification-title {
        font-weight: bold;
        font-size: 16px;
        color: #333;
    }

    /* Notification Time */
    .notification-time {
        font-size: 12px;
    }

    /* Notification Content */
    .notification-content {
        font-size: 14px;
        color: #555;
        line-height: 1.5;
    }

    /* Empty State */
    .no-notifications {
        color: #888;
        font-size: 14px;
    }

    /* Hover Effects */
    .notification-item:hover .notification-content {
        color: #007bff;
    }
</style>

@forelse($notifications as $notification)
<div class="notification-item {{ $notification->isUnread() ? 'unread' : '' }}">
    <a href="{{
            match($notification->type) {
                'absence-requests' => '/absence-requests',
                'permission-requests' => '/permission-requests',
                'overtime-requests' => '/overtime-requests',
                default => '#'
            }
        }}"
        class="notification-link"
        data-mark-as-read="{{ route('notifications.mark-as-read', $notification) }}">
        <div class="notification-header d-flex justify-content-between">
            <div class="notification-title">
                <strong>{{ $notification->data['title'] ?? 'إشعار' }}</strong>
            </div>
            <div class="notification-time text-muted">
                {{ $notification->created_at->diffForHumans() }}
            </div>
        </div>
        <div class="notification-content">
            {{ $notification->data['message'] }}
        </div>
    </a>
</div>
@empty
<div class="no-notifications p-3 text-center text-muted">
    لا توجد إشعارات
</div>
@endforelse
