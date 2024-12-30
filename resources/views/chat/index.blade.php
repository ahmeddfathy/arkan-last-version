@extends('layouts.app')

@section('content')
<div class="chat-container">
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <div class="user-profile">
                <div class="user-avatar">
                    <img src="https://th.bing.com/th/id/OIP.7dl-g74HDQZsmchtLCsMvQHaHa?rs=1&pid=ImgDetMain" alt="Profile">
                </div>
                <div class="user-info">
                    <h4>{{ Auth::user()->name }}</h4>
                    <span class="status-text">{{ Auth::user()->role }}</span>
                </div>
            </div>
        </div>

        <div class="chat-list">
            @foreach($chats as $chat)
            <div class="chat-item @if($chat['unread_count'] > 0) unread @endif"
                 onclick="loadChat({{ $chat['user']->id }}, '{{ $chat['user']->name }}')">
                <div class="chat-item-avatar">
                    <img src="{{ $chat['user']->avatar ?? asset('images/default-avatar.png') }}" alt="Avatar">
                    <span class="status-dot {{ $chat['user']->is_online ? 'online' : 'offline' }}"></span>
                </div>
                <div class="chat-item-content">
                    <div class="chat-item-header">
                        <h5>{{ $chat['user']->name }}</h5>
                        <span class="chat-time">
                            {{ $chat['last_message']->created_at->diffForHumans(null, true) }}
                        </span>
                    </div>
                    <div class="chat-item-message">
                        <p>{{ Str::limit($chat['last_message']->content, 50) }}</p>
                        @if($chat['unread_count'] > 0)
                            <span class="unread-badge">{{ $chat['unread_count'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="chat-main">
        <div id="no-chat-selected" class="no-chat-selected">
            <div class="no-chat-content">
                <img src="https://th.bing.com/th/id/OIP.snSDnMg9wU5TwatvtkEuCQHaHa?rs=1&pid=ImgDetMain" alt="Select a chat">
                <h3>Select a chat to start messaging</h3>
            </div>
        </div>

        <div id="chat-area" class="chat-area d-none">
            <div class="chat-header" >
                <div class="chat-contact-info">
                    <div class="contact-avatar">
                        <img src="https://th.bing.com/th/id/OIP.7dl-g74HDQZsmchtLCsMvQHaHa?rs=1&pid=ImgDetMain" alt="Contact" id="contact-avatar">
                    </div>
                    <div class="contact-details">
                        <h4 id="contact-name"></h4>
                        <span class="status-text" id="contact-status"></span>
                    </div>
                </div>
            </div>

            <div class="chat-content">
                <div class="messages-container" id="messages-container"></div>
            </div>

            <div class="chat-input-area">
                <form id="message-form">
                    <div class="input-wrapper">
                        <input type="text"
                               id="message-input"
                               placeholder="Type a message"
                               autocomplete="off">
                        <button type="submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/chat.css') }}">
@endpush

@push('scripts')
<script>
let currentChatId = null;

function loadChat(userId, userName) {
    document.querySelector('.chat-list').classList.remove('active');
    currentChatId = userId;
    $('#no-chat-selected').addClass('d-none');
    $('#chat-area').removeClass('d-none');
    $('#contact-name').text(userName);

    fetchMessages();
    startMessagePolling();
    updateUserStatus(userId);
}

function fetchMessages() {
    if (!currentChatId) return;

    $.get(`/chat/messages/${currentChatId}`, function(messages) {
        renderMessages(messages);
        scrollToBottom();
    });
}

function renderMessages(messages) {
    const container = $('#messages-container');
    container.empty();

    let currentDate = null;

    messages.forEach(message => {
        const messageDate = new Date(message.created_at).toLocaleDateString();

        if (messageDate !== currentDate) {
            container.append(`
                <div class="message-date-divider">
                    <span>${messageDate}</span>
                </div>
            `);
            currentDate = messageDate;
        }

        const isOwn = message.sender_id === {{ Auth::id() }};
        const time = new Date(message.created_at).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });

        container.append(`
            <div class="message ${isOwn ? 'message-own' : 'message-other'}">
                <div class="message-content">
                    <p>${message.content}</p>
                    <div class="message-meta">
                        <span class="message-time">${time}</span>
                        ${isOwn ? `
                            <span class="message-status">
                                ${message.is_seen ?
                                    '<i class="fas fa-check-double seen"></i>' :
                                    '<i class="fas fa-check"></i>'}
                            </span>
                        ` : ''}
                    </div>
                </div>
            </div>
        `);
    });
}

function startMessagePolling() {
    stopMessagePolling();
    window.messagePolling = setInterval(fetchMessages, 3000);
}

function stopMessagePolling() {
    if (window.messagePolling) {
        clearInterval(window.messagePolling);
    }
}

function updateUserStatus(userId) {
    function checkStatus() {
        $.get(`/status/user/${userId}`, function(response) {
            const statusText = response.is_online ? 'Online' : 'Last seen ' +
                new Date(response.last_seen_at).toLocaleString();
            $('#contact-status').text(statusText);
        });
    }

    checkStatus();
    window.statusInterval = setInterval(checkStatus, 30000);
}

function scrollToBottom() {
    const container = $('#messages-container');
    container.scrollTop(container[0].scrollHeight);
}

$('#message-form').on('submit', function(e) {
    e.preventDefault();

    const input = $('#message-input');
    const content = input.val().trim();

    if (!content || !currentChatId) return;

    $.post('/chat/send', {
        receiver_id: currentChatId,
        content: content,
        _token: '{{ csrf_token() }}'
    }, function() {
        input.val('');
        fetchMessages();
    });
});

// Automatically load chat with the manager
document.addEventListener('DOMContentLoaded', function () {
    @if($manager)
        loadChat({{ $manager->id }}, '{{ $manager->name }}');
    @endif
});
document.querySelector('.chat-sidebar-header').addEventListener('click', function() {
    document.querySelector('.chat-list').classList.toggle('active');
});
</script>
@endpush
