@forelse($notifications as $notification)
<div class="notification-item {{ is_null($notification->read_at) ? 'unread' : '' }}"
    data-id="{{ $notification->id }}">

    @if($notification->type === 'admin_broadcast')
    <!-- عرض الإشعارات العامة -->
    <div class="notification-content">
        <strong>{{ $notification->data['title'] }}</strong>
        <p>{{ $notification->data['message'] }}</p>
        <small>من: {{ $notification->data['sender_name'] }}</small>
    </div>
    @else
    <!-- عرض الإشعارات الخاصة -->
    <div class="notification-content">
        {{ $notification->data['message'] }}
    </div>
    @endif

    <div class="notification-time">
        {{ $notification->created_at->diffForHumans() }}
    </div>
</div>
@empty
<div class="text-center p-4 text-muted">
    <i class="fas fa-bell-slash mb-3" style="font-size: 24px; opacity: 0.5;"></i>
    <p class="mb-0 small">لا توجد إشعارات</p>
</div>
@endforelse