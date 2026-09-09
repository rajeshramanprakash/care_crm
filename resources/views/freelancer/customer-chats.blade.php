@extends('freelancer.layouts.app')
@section('title', 'Customer Chats')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
<div class="content-wrapper">
    <div class="container-fluid d-flex justify-content-center align-items-start">
        <div class="chat-card row w-100 justify-content-center align-items-stretch mobile-chat-wrapper" style="overflow: hidden;">
            <!-- Sidebar: Customer List -->
            <div class="col-md-4 col-lg-4 chat-sidebar p-0 d-flex flex-column border-end mobile-user-list" style="background: #f9f9f9; min-width: 270px; max-width: 340px;">
                <div class="chat-sidebar-header d-flex flex-column px-3 py-2 border-bottom bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="mb-0" style="font-size:18px;">Customer Chats</h5>
                        <input type="text" class="form-control" id="searchCustomer" placeholder="Search..." style="width: 150px; font-size:12px;">
                    </div>
                </div>

                <div class="chat-user-list overflow-auto flex-grow-1" id="customersList" style="background: #f9f9f9;">
                    @forelse($customers as $customer)
                        <div class="chat-user-item d-flex align-items-center px-3 py-2" data-customer-id="{{ (int) $customer->id }}" data-profile-image="{{ $customer->profile_image ? Storage::url($customer->profile_image) : '' }}" data-initial="{{ strtoupper(substr($customer->customer_name ?? 'C', 0, 1)) }}" style="text-decoration:none; cursor: pointer;">
                            <div class="me-3" style="width: 44px; height: 44px; position: relative;">
                                @if($customer->profile_image)
                                    <img src="{{ Storage::url($customer->profile_image) }}" alt="{{ $customer->customer_name ?? 'Customer' }}" class="rounded-circle" style="width: 44px; height: 44px; object-fit: cover; border: 2px solid #F7941D;" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="user-initial-avatar" style="display: none; background: #F7941D; width: 44px; height: 44px; border-radius: 50%; color: #fff; font-weight: 600; font-size: 1.2em; align-items: center; justify-content: center; position: absolute; top: 0; left: 0;">
                                        {{ strtoupper(substr($customer->customer_name ?? 'C', 0, 1)) }}
                                    </div>
                                @else
                                    <div class="user-initial-avatar" style="background: #F7941D; width: 44px; height: 44px; border-radius: 50%; color: #fff; font-weight: 600; font-size: 1.2em; display: flex; align-items: center; justify-content: center;">
                                        {{ strtoupper(substr($customer->customer_name ?? 'C', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold customer-name" style="font-size:14px;">{{ $customer->customer_name ?? 'N/A' }}</span>
                                    <small class="text-muted last-message-time" style="font-size:0.85em;">
                                        @if($customer->last_message)
                                            {{ \Carbon\Carbon::parse($customer->last_message->created_at)->format('h:i A') }}
                                        @endif
                                    </small>
                                </div>
                                <div class="text-muted small last-message-preview" style="font-size:0.97em; white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px;">
                                    @if($customer->last_message)
                                        @if($customer->last_message->attachment)
                                            <i class="fa fa-paperclip"></i> Attachment
                                        @elseif($customer->last_message->message)
                                            {{ $customer->last_message->message }}
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <span class="badge unread-count ms-2" style="{{ ($customer->unread_count ?? 0) > 0 ? '' : 'display: none;' }} background: #25d366; color: #fff; font-weight: bold;">{{ $customer->unread_count ?? 0 }}</span>
                        </div>
                    @empty
                        <div class="text-center p-4 text-muted">
                            <i class="fas fa-comments fa-2x mb-2"></i>
                            <p>No customers assigned yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
            <!-- Main Chat Area -->
            <div class="col-md-8 col-lg-8 chat-main p-0 d-flex flex-column mobile-chat-area" style="background: #fff;">
                <div class="chat-header d-flex align-items-center px-2 py-2 border-bottom bg-white">
                    <button class="btn btn-light rounded-circle d-md-none me-2 mobile-back-btn" style="width: 40px; height: 40px; display: none;" onclick="showCustomerListMobile()">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="position-relative me-3">
                        <img id="chatHeaderAvatar" src="" alt="Customer" class="rounded-circle chat-user-avatar" style="display: none; width: 44px; height: 44px; object-fit: cover; border: 2px solid #F7941D;" onerror="this.style.display='none'; $('#chatHeaderAvatarFallback').css('display', 'flex');">
                        <div id="chatHeaderAvatarFallback" class="user-initial-avatar chat-user-avatar" style="width: 44px; height: 44px; border-radius: 50%; background: #F7941D; color: #fff; font-weight: 600; display: flex; align-items: center; justify-content: center;">C</div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0" id="chatHeader">Select a customer to start chatting</h6>
                    </div>
                    <button class="btn btn-success rounded-circle" id="callBtn" disabled style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;" title="Audio Call">
                        <i class="fas fa-phone"></i>
                    </button>
                    <div class="ms-2 d-flex align-items-center gap-2 translate-lang-wrap" title="Translate (Gemini 2.0 Flash)">
                        <label for="translateTargetLang" class="small text-muted mb-0 text-nowrap">Translate to:</label>
                        <select id="translateTargetLang" class="form-select form-select-sm translate-lang-select">
                            <option value="Hindi">Hindi</option>
                            <option value="English">English</option>
                            <option value="Tamil">Tamil</option>
                            <option value="Telugu">Telugu</option>
                            <option value="Marathi">Marathi</option>
                            <option value="Bengali">Bengali</option>
                            <option value="Gujarati">Gujarati</option>
                            <option value="Punjabi">Punjabi</option>
                        </select>
                    </div>
                </div>
                <!-- Call Modal -->
                <div id="callModal" class="call-modal" style="display: none !important; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
                    <div class="call-content text-center" style="background: white; padding: 40px; border-radius: 20px; max-width: 400px; width: 90%;">
                        <div class="caller-avatar mb-4" style="width: 120px; height: 120px; border-radius: 50%; background: #25d366; color: white; font-size: 48px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <i class="fas fa-user"></i>
                        </div>
                        <h4 id="callerName" style="margin-bottom: 10px;">Calling...</h4>
                        <p id="callStatus" class="text-muted mb-4">Connecting...</p>
                        <div class="call-controls d-flex justify-content-center gap-3">
                            <button id="muteBtn" class="btn btn-secondary rounded-circle" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-microphone"></i>
                            </button>
                            <button id="hangupBtn" class="btn btn-danger rounded-circle" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-phone"></i>
                            </button>
                        </div>
                        <audio id="remoteAudio" autoplay style="display: none;"></audio>
                        <audio id="localAudio" muted style="display: none;"></audio>
                    </div>
                </div>
                <div class="chat-messages flex-grow-1 position-relative" id="chatMessagesContainer" style="background: #f8f9fa url('{{ asset('images/chatbackground.png') }}') center center / cover no-repeat; overflow-y: auto; padding: 32px; min-height: 350px; max-height: 60vh;">
                    <div id="chatMessages" style="position: relative;">
                        <div class="chat-welcome text-center mt-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h4>Select a customer to start chatting</h4>
                        </div>
                    </div>
                </div>
                <!-- Reply Preview Box -->
                <div id="replyPreview" class="reply-preview-box" style="display:none; background:#f0f2f5; border-top:2px solid #25d366; padding: 8px 16px;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1">
                            <small class="text-muted d-block"><i class="fa fa-reply me-1"></i>Replying to <span id="replyToUser"></span></small>
                            <div id="replyToMessage" class="text-truncate small" style="max-width: 400px; color: #667781;"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-danger" id="cancelReply" style="padding: 0; border: none; background: none;">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="chat-input d-flex align-items-center px-3 py-2 border-top bg-white">
                    <form id="messageForm" class="d-flex w-100 align-items-center" style="gap: 10px;" enctype="multipart/form-data">
                        <input type="hidden" id="receiverId" name="receiver_id">
                        <input type="hidden" id="replyToMessageId" name="reply_to_id">
                        <button type="button" class="btn btn-secondary rounded-circle" id="attachmentBtn" disabled style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;" title="Attach File">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <input type="file" id="attachmentInput" style="display: none;" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
                        <input type="text" id="messageInput" class="form-control" placeholder="Type a message..." disabled>
                        <button type="submit" class="btn btn-primary" disabled style="border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body, .content-wrapper { background: #f8f9fc !important; }
    .chat-card { background: transparent; overflow: hidden; height: 87vh; }
    .chat-sidebar { background: #f9f9f9; border-right: 1px solid #f0f0f0; border-radius: 18px 0 0 18px; }
    .chat-sidebar-header { background: #fff; border-bottom: 1px solid #f0f0f0; padding: 20px 24px; position: sticky; top: 0; z-index: 2; }
    #searchCustomer { border-radius: 12px; border: 1px solid #e0e0e0; padding: 8px 14px; font-size: 1em; }
    .chat-user-list { overflow-y: auto; flex-grow: 1; background: #f9f9f9; padding: 0 0 10px 0; }
    .chat-user-item { display: flex; align-items: center; padding: 16px 24px; border-radius: 10px; margin: 4px 8px; cursor: pointer; transition: background 0.2s; border: 2px solid transparent; background: #fff; }
    .chat-user-item.active, .chat-user-item:hover { background: #f0f8ff; box-shadow: 0 2px 8px rgba(0, 123, 255, 0.08); }
    .chat-main { background: #fff; border-radius: 0 18px 18px 0; display: flex; flex-direction: column; padding: 0; height: 100%; }
    .chat-header { background: #fff; border-bottom: 1px solid #f0f0f0; padding: 18px 24px; display: flex; align-items: center; flex-wrap: wrap; gap: 8px; position: sticky; top: 0; z-index: 2; }
    .chat-messages {
        background: #f8f9fa url('{{ asset('images/chatbackground.png') }}') center center / cover no-repeat;
        overflow-y: auto;
        flex-grow: 1;
        padding: 32px;
        min-height: 350px;
        max-height: 100vh !important;
        display: flex;
        flex-direction: column;
    }
    .message { margin-bottom: 1.2rem; max-width: 70%; display: flex; flex-direction: column; word-break: break-word; }
    .message.sent { margin-left: auto; align-items: flex-end; }
    .message.received { margin-right: auto; align-items: flex-start; }
    .message-content { padding: 0.75rem 1.2rem; border-radius: 1.2rem; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.07); font-size: 1.05em; }
    .message.sent .message-content { background-color: #F7941D; color: #fff; border-bottom-right-radius: 0.4rem; }
    .message.received .message-content { background-color: #fff; color: #222; border-bottom-left-radius: 0.4rem; border: 1px solid #e6e6e6; }
    .message-time { font-size: 0.8em; color: #b0b0b0; margin-top: 0.2rem; }
    .chat-input { background-color: #F8F9FA; border-top: 1px solid #f0f0f0; padding: 18px 24px; }
    #messageInput { border-radius: 18px; border: 1px solid #e0e0e0; padding: 14px 18px; font-size: 14px; min-height: 48px; background: #ffffff; }
    #messageInput:focus { border-color: #F7941D; }
    .chat-input .btn { border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; font-size: 1.2em; background: #F7941D; color: #fff; border: none; }
    .chat-input .btn:hover { background: #d97706; }
    .chat-input .btn-secondary { background: #6c757d; color: #fff; }
    .chat-input .btn-secondary:hover { background: #5a6268; }
    .badge.unread-count { background: #25d366 !important; color: #fff !important; font-weight: bold; border-radius: 10px; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; }
    @media (max-width: 767px) {
        .mobile-chat-wrapper { flex-direction: column !important; height: 100vh !important; }
        .chat-sidebar, .chat-main { min-width: 100vw !important; width: 100vw !important; border-radius: 0 !important; height: 100vh !important; position: absolute; left: 0; top: 0; z-index: 10; }
        .mobile-user-list { display: block !important; }
        .mobile-chat-area { display: none !important; }
        .mobile-chat-wrapper.show-chat .mobile-chat-area { display: block !important; z-index: 20; }
        .mobile-chat-wrapper.show-chat .mobile-user-list { display: none !important; }
    }
    .translate-lang-wrap { flex-shrink: 0; }
    .translate-lang-select {
        min-width: 110px;
        max-width: 140px;
        font-size: 13px;
        height: 32px;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        border: 1px solid #e9eaeb;
        background-color: #fff;
    }
    .translate-lang-select:focus { border-color: #25d366; box-shadow: 0 0 0 0.2rem rgba(37, 211, 102, 0.2); }
    @media (max-width: 768px) {
        .translate-lang-wrap { order: 1; width: 100%; margin-top: 6px; }
        .translate-lang-select { max-width: none; }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script>
let currentCustomerId = null;
let messagePollingInterval = null;
let lastMessageCount = 0;
let userHasScrolledManually = false;
let lastMessageId = 0;
let translatedMessages = {};

// WebRTC Call variables - declared at top level
let localStream = null;
let peerConnection = null;
let isCallActive = false;
let isMuted = false;
let incomingCallCustomerId = null;

$(document).ready(function() {
    // Ensure call modal is hidden on page load
    $('#callModal').hide();
    isCallActive = false;
    incomingCallCustomerId = null;
    // Search customers
    $('#searchCustomer').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        $('.chat-user-item').each(function() {
            const customerName = $(this).find('.customer-name').text().toLowerCase();
            $(this).toggle(customerName.includes(searchText));
        });
    });

    function openCustomerChat($item) {
        const rawCustomerId = $item.attr('data-customer-id') ?? $item.data('customer-id');
        const customerId = String(rawCustomerId || '').trim();
        if (!customerId) return;

        // Clear previous polling
        if (messagePollingInterval) {
            clearInterval(messagePollingInterval);
            messagePollingInterval = null;
        }
        
        // Stop incoming call check
        stopIncomingCallCheck();

        // End any active call before switching customers
        if (isCallActive) {
            endCall();
        }
        
        // Ensure call modal is hidden when switching customers
        $('#callModal').hide().css('display', 'none');
        incomingCallCustomerId = null;

        // Clear messages
        $('#chatMessages').html('');
        currentCustomerId = customerId;
        lastMessageCount = 0;
        userHasScrolledManually = false;
        lastMessageId = 0;
        translatedMessages = {};

        // Update UI
        $('.chat-user-item').removeClass('active');
        $item.addClass('active');
        
        const customerName = $item.find('.customer-name').text();
        const profileImage = $item.attr('data-profile-image') ?? $item.data('profile-image');
        const initial = $item.attr('data-initial') ?? $item.data('initial');
        
        $('#chatHeader').text(customerName);
        
        // Update chat header avatar
        if (profileImage) {
            $('#chatHeaderAvatar').attr('src', profileImage).show();
            $('#chatHeaderAvatarFallback').hide();
        } else {
            $('#chatHeaderAvatar').hide();
            $('#chatHeaderAvatarFallback').text(initial).show();
        }
        $('#callBtn').prop('disabled', false);
        $('#receiverId').val(customerId);
        $('#messageInput').prop('disabled', false);
        $('#attachmentBtn').prop('disabled', false);
        $('#messageForm button[type="submit"]').prop('disabled', false);
        $('.chat-welcome').hide();

        // Load messages
        loadMessages(customerId, true); // Initial load, scroll to bottom

        // Start polling for messages
        messagePollingInterval = setInterval(() => {
            if (currentCustomerId == customerId) {
                loadMessages(customerId, false); // Polling, don't force scroll
            }
        }, 3000);

        // Start checking for incoming calls
        startIncomingCallCheck();

        // Mark as read
        markAsRead(customerId);

        // Mobile view
        if ($(window).width() <= 767) {
            $('.mobile-chat-wrapper').addClass('show-chat');
        }
    }

    // Customer selection (delegated + direct binding fallback)
    $(document).on('click', '.chat-user-item', function(e) {
        e.preventDefault();
        openCustomerChat($(this));
    });
    $('.chat-user-item').on('click', function(e) {
        e.preventDefault();
        openCustomerChat($(this));
    });

    // Attachment button click
    $('#attachmentBtn').on('click', function() {
        $('#attachmentInput').click();
    });

    // File input change
    $('#attachmentInput').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Show file name in message input
        $('#messageInput').val('📎 ' + file.name);
        $('#messageInput').prop('disabled', false);
        $('#messageForm button[type="submit"]').prop('disabled', false);
    });

    // Send message
    $('#messageForm').on('submit', function(e) {
        e.preventDefault();
        const message = $('#messageInput').val().trim();
        const receiverId = $('#receiverId').val();
        const fileInput = $('#attachmentInput')[0];
        
        if ((!message || message === '') && !fileInput.files[0]) return;
        if (!receiverId) return;

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('receiver_id', receiverId);
        
        // Remove file name prefix if present
        const cleanMessage = message.replace(/^📎\s*/, '');
        if (cleanMessage) {
            formData.append('message', cleanMessage);
        }
        
        if (fileInput.files[0]) {
            formData.append('attachment', fileInput.files[0]);
        }

        // Add reply_to_id if present
        const replyToId = $('#replyToMessageId').val();
        if (replyToId) {
            formData.append('reply_to_id', replyToId);
        }

        $.ajax({
            url: '{{ route("freelancer.customer-chats.send") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#messageInput').val('');
                $('#attachmentInput').val('');
                cancelReply();
                // Load messages with forceReload to ensure new message is fetched immediately
                if (currentCustomerId == receiverId) {
                    loadMessages(receiverId, true, true); // Force scroll and force reload when sending
                }
            },
            error: function(xhr) {
                console.error('Error sending message:', xhr);
                alert('Failed to send message');
            }
        });
    });

    // Enter key to send
    $('#messageInput').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            $('#messageForm').submit();
        }
    });

    // Update unread counts
    function updateUnreadCounts() {
        $.get('{{ route("freelancer.customer-chats.unread-counts") }}')
            .done(function(counts) {
                $('.chat-user-item').each(function() {
                    const customerId = $(this).data('customer-id');
                    const count = counts[customerId] || 0;
                    const badge = $(this).find('.unread-count');
                    if (count > 0) {
                        badge.text(count).show();
                    } else {
                        badge.hide();
                    }
                });
            });
    }

    // Load messages
    function loadMessages(customerId, shouldScroll = false, forceReload = false) {
        if (!customerId) return;

        // Build URL with last_message_id if it exists (for polling)
        // If forceReload is true, don't use lastMessageId filter to ensure all messages are fetched
        let url = '{{ route("freelancer.customer-chats.messages", ":id") }}'.replace(':id', customerId);
        if (lastMessageId > 0 && !forceReload) {
            url += '?last_message_id=' + lastMessageId;
        }

        $.get(url)
            .done(function(items) {
                if (currentCustomerId != customerId) return;

                const chatMessages = $('#chatMessages');
                const chatMessagesContainer = $('#chatMessagesContainer');
                const wasAtBottom = isScrolledToBottom(chatMessagesContainer[0]);
                const isInitialLoad = lastMessageId === 0;
                
                // If initial load, replace all messages. If polling, only append new ones
                if (isInitialLoad) {
                    chatMessages.html('');
                }

                // Separate messages and calls
                const messages = items.filter(item => item.type === 'message').map(item => item.data);
                const calls = items.filter(item => item.type === 'call').map(item => item.data);

                if (messages.length === 0 && calls.length === 0) {
                    if (isInitialLoad) {
                        chatMessages.html(`
                            <div class="chat-welcome text-center mt-5">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h4>No messages yet</h4>
                                <p class="text-muted">Start the conversation!</p>
                            </div>
                        `);
                    }
                    return;
                }

                // Only scroll if user is at bottom AND new messages arrived OR explicitly requested
                const shouldAutoScroll = shouldScroll || (wasAtBottom && items.length > 0);

                let currentDate = null;
                let lastMessageIdInBatch = lastMessageId;
                
                // Process all items (messages and calls) in chronological order
                items.forEach(function(item) {
                    if (item.type === 'call') {
                        // Check if this call already exists in DOM
                        const callId = 'call_' + item.data.id;
                        if (!isInitialLoad && chatMessages.find(`[data-call-id="${callId}"]`).length > 0) {
                            return; // Skip if already exists
                        }
                        
                        // Render call history
                        const call = item.data;
                        const callDate = moment(call.call_started_at || call.created_at).format('YYYY-MM-DD');
                        let dateSeparatorExists = false;
                        if (!isInitialLoad) {
                            chatMessages.find('.text-center.text-muted.my-3 small').each(function() {
                                if ($(this).text() === moment(call.call_started_at || call.created_at).format('MMMM DD, YYYY')) {
                                    dateSeparatorExists = true;
                                    return false;
                                }
                            });
                        }
                        
                        if (currentDate !== callDate && !dateSeparatorExists) {
                            currentDate = callDate;
                            chatMessages.append(`<div class="text-center text-muted my-3"><small>${moment(call.call_started_at || call.created_at).format('MMMM DD, YYYY')}</small></div>`);
                        }
                        
                        const isOutgoing = call.caller_type === 'freelancer';
                        const callStatus = call.call_status;
                        let callText = '';
                        let callIcon = '';
                        
                        if (callStatus === 'initiated') {
                            callText = isOutgoing ? 'Calling...' : 'Incoming call...';
                            callIcon = '<i class="fas fa-phone"></i>';
                        } else if (callStatus === 'connected') {
                            callText = 'Call connected';
                            callIcon = '<i class="fas fa-phone-alt"></i>';
                        } else if (callStatus === 'ended') {
                            const duration = call.call_duration ? formatCallDuration(call.call_duration) : '';
                            callText = isOutgoing ? `Outgoing call ended${duration ? ' (' + duration + ')' : ''}` : `Incoming call ended${duration ? ' (' + duration + ')' : ''}`;
                            callIcon = '<i class="fas fa-phone-slash"></i>';
                        } else if (callStatus === 'missed') {
                            callText = isOutgoing ? 'Call missed' : 'Missed call';
                            callIcon = '<i class="fas fa-phone-slash"></i>';
                        } else if (callStatus === 'rejected') {
                            callText = isOutgoing ? 'Call rejected' : 'Call rejected';
                            callIcon = '<i class="fas fa-phone-slash"></i>';
                        }
                        
                        const callTime = moment(call.call_started_at || call.created_at).format('h:mm A');
                        const callEndTime = call.call_ended_at ? moment(call.call_ended_at).format('h:mm A') : '';
                        const timeText = callEndTime ? `${callTime} - ${callEndTime}` : callTime;
                        
                        const callHtml = `
                            <div class="message call-message" data-call-id="${callId}" style="margin: 10px auto; max-width: 70%; text-align: center;">
                                <div class="message-content" style="background: #e3f2fd; color: #1976d2; padding: 10px 15px; border-radius: 12px; display: inline-block;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                        <span>${callIcon}</span>
                                        <span style="font-weight: 500;">${callText}</span>
                                    </div>
                                    <div class="message-time" style="font-size: 0.75em; margin-top: 5px; color: #666;">${timeText}</div>
                                </div>
                            </div>
                        `;
                        chatMessages.append(callHtml);
                        return; // Skip to next item
                    }
                    
                    // Process message
                    const msg = item.data;
                    
                    // Check if this message already exists in DOM
                    if (!isInitialLoad && chatMessages.find(`[data-message-id="${msg.id}"]`).length > 0) {
                        // Update lastMessageIdInBatch even if message already exists
                        if (msg.id > lastMessageIdInBatch) {
                            lastMessageIdInBatch = msg.id;
                        }
                        return; // Skip if already exists
                    }
                    // Update last message ID
                    if (msg.id > lastMessageIdInBatch) {
                        lastMessageIdInBatch = msg.id;
                    }
                    
                    // Update last message ID
                    if (msg.id > lastMessageIdInBatch) {
                        lastMessageIdInBatch = msg.id;
                    }
                    
                    const msgDate = moment(msg.created_at).format('YYYY-MM-DD');
                    // Check if date separator already exists (for appending new messages)
                    let dateSeparatorExists = false;
                    if (!isInitialLoad) {
                        chatMessages.find('.text-center.text-muted.my-3 small').each(function() {
                            if ($(this).text() === moment(msg.created_at).format('MMMM DD, YYYY')) {
                                dateSeparatorExists = true;
                                return false;
                            }
                        });
                    }
                    
                    if (currentDate !== msgDate && !dateSeparatorExists) {
                        currentDate = msgDate;
                        chatMessages.append(`<div class="text-center text-muted my-3"><small>${moment(msg.created_at).format('MMMM DD, YYYY')}</small></div>`);
                    }

                    const isSent = msg.sender_type === 'freelancer';
                    const messageClass = isSent ? 'sent' : 'received';
                    let messageHtml = `<div class="message ${messageClass}" data-message-id="${msg.id}">`;
                    
                    // Reply preview - WhatsApp style
                    let repliedHtml = '';
                    if (msg.reply_to_id && msg.replied_to) {
                        const rT = msg.replied_to.message || (msg.replied_to.attachment ? '[Attachment]' : '');
                        let rS = 'Unknown';
                        if (msg.replied_to.sender) {
                            rS = msg.replied_to.sender.customer_name || msg.replied_to.sender.name || 'Unknown';
                        } else {
                            // Fallback: use sender_type to determine name
                            const senderType = msg.replied_to.sender_type;
                            if (senderType === 'customer') rS = 'Customer';
                            else if (senderType === 'vendor') rS = 'Vendor';
                            else if (senderType === 'freelancer') rS = 'Freelancer';
                        }
                        const truncatedText = rT.length > 50 ? rT.substring(0, 50) + '...' : rT;
                        repliedHtml = `<div class="replied-message" onclick="event.stopPropagation(); scrollToOriginalMessage(${msg.reply_to_id});" title="Click to view original message" style="cursor: pointer; background: rgba(37,211,102,0.1); border-left: 3px solid #25d366; padding: 8px 12px; margin-bottom: 8px; border-radius: 4px; font-size: 13px;">
                            <div class="replied-message-sender" style="color: #25d366; font-weight: 600; font-size: 12px; margin-bottom: 4px;"><i class="fa fa-reply me-1"></i>${escapeHtml(rS)}</div>
                            <div class="replied-message-text" style="color: #667781; font-size: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${escapeHtml(truncatedText)}</div>
                        </div>`;
                    }
                    
                    if (msg.attachment) {
                        const attachmentUrl = '/storage/' + msg.attachment;
                        const isImage = msg.attachment_type && msg.attachment_type.startsWith('image/');
                        const isVideo = msg.attachment_type && msg.attachment_type.startsWith('video/');
                        const fileName = msg.attachment.split('/').pop();
                        
                        if (isImage) {
                            messageHtml += `<div class="message-content">
                                ${repliedHtml}
                                <img src="${attachmentUrl}" style="max-width: 300px; max-height: 300px; border-radius: 8px; cursor: pointer;" alt="Image" onclick="window.open('${attachmentUrl}', '_blank')">
                                <div class="mt-2">
                                    <a href="${attachmentUrl}" download="${fileName}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                </div>
                            </div>`;
                        } else if (isVideo) {
                            messageHtml += `<div class="message-content">
                                ${repliedHtml}
                                <video controls preload="metadata" style="max-width: 300px; max-height: 400px; border-radius: 8px; background: #000; display: block;">
                                    <source src="${attachmentUrl}" type="${msg.attachment_type}">
                                    Your browser does not support the video tag.
                                </video>
                                <div class="mt-2">
                                    <a href="${attachmentUrl}" download="${fileName}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                    <a href="${attachmentUrl}" target="_blank" class="btn btn-sm btn-secondary ms-2">
                                        <i class="fa fa-external-link-alt"></i> Open in New Tab
                                    </a>
                                </div>
                            </div>`;
                        } else {
                            messageHtml += `<div class="message-content">
                                ${repliedHtml}
                                <div class="d-flex align-items-center gap-2 p-2" style="background: #f0f0f0; border-radius: 8px;">
                                    <i class="fa fa-file fa-2x text-primary"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">${escapeHtml(fileName)}</div>
                                        <small class="text-muted">Document</small>
                                    </div>
                                    <a href="${attachmentUrl}" download="${fileName}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                </div>
                            </div>`;
                        }
                    } else {
                        messageHtml += `<div class="message-content">${repliedHtml}${escapeHtml(msg.message || '')}</div>`;
                    }
                    
                    const senderName = isSent ? 'You' : $('#chatHeader').text();
                    const messageText = msg.attachment ? '[Attachment]' : (msg.message || '');
                    const tr = translatedMessages[msg.id];
                    const transStyle = tr ? '' : 'display:none;';
                    const transHtml = tr ? '<small class="text-muted">' + escapeHtml(tr.lang) + ':</small> ' + escapeHtml(tr.text) : '';
                    messageHtml += `<div class="message-time">${moment(msg.created_at).format('h:mm A')}</div>`;
                    messageHtml += `<button class="message-translate-btn" data-message-id="${msg.id}" data-message-text="${escapeHtml(messageText)}" title="Translate (Gemini 2.0 Flash)" style="opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px;"><i class="fa fa-language"></i></button>`;
                    messageHtml += `<div class="message-translated mt-1" style="${transStyle} font-size:12px; color:#666; border-left:3px solid #F7941D; padding-left:8px;">${transHtml}</div>`;
                    messageHtml += `<button class="message-reply-btn" data-message-id="${msg.id}" data-sender-name="${escapeHtml(senderName)}" data-message-text="${escapeHtml(messageText)}" title="Reply to this message" style="opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px;"><i class="fa fa-reply"></i> Reply</button>`;
                    messageHtml += `</div>`;
                    chatMessages.append(messageHtml);
                });

                // Update last message ID only if we processed new messages
                // If forceReload, always update to the latest message ID
                if (forceReload || lastMessageIdInBatch > lastMessageId) {
                    lastMessageId = lastMessageIdInBatch;
                }
                // Only count new items that were actually added
                const newItemsCount = items.filter(item => {
                    if (item.type === 'message') {
                        return !chatMessages.find(`[data-message-id="${item.data.id}"]`).length || isInitialLoad;
                    } else if (item.type === 'call') {
                        return !chatMessages.find(`[data-call-id="call_${item.data.id}"]`).length || isInitialLoad;
                    }
                    return false;
                }).length;
                lastMessageCount += newItemsCount;

                // Only scroll if conditions are met
                if (shouldAutoScroll) {
                    setTimeout(() => {
                        chatMessagesContainer.scrollTop(chatMessagesContainer[0].scrollHeight);
                        userHasScrolledManually = false;
                    }, 100);
                }
                updateUnreadCounts();
            })
            .fail(function() {
                console.error('Failed to load messages');
            });
    }

    // Check if user is scrolled to bottom
    function isScrolledToBottom(element) {
        if (!element) return true;
        const threshold = 100; // 100px threshold
        return element.scrollHeight - element.scrollTop - element.clientHeight < threshold;
    }

    // Track manual scrolling
    $('#chatMessagesContainer').on('scroll', function() {
        if (!isScrolledToBottom(this)) {
            userHasScrolledManually = true;
        } else {
            userHasScrolledManually = false;
        }
    });

    // Mark as read
    function markAsRead(customerId) {
        $.post('{{ route("freelancer.customer-chats.mark-read", ":id") }}'.replace(':id', customerId), {
            _token: '{{ csrf_token() }}'
        });
    }

    // Escape HTML
    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Format call duration
    function formatCallDuration(seconds) {
        if (!seconds) return '';
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }

    // Reply functions - WhatsApp style
    function setReplyTo(messageId, senderName, messageText) {
        if (!messageId) return;
        $('#replyToMessageId').val(messageId);
        $('#replyToUser').text(senderName || 'Unknown');
        const displayText = messageText || '[Attachment]';
        $('#replyToMessage').text(displayText.length > 50 ? displayText.substring(0, 50) + '...' : displayText);
        $('#replyPreview').slideDown(200);
        $('#messageInput').focus();
    }

    function cancelReply() {
        $('#replyToMessageId').val('');
        $('#replyToUser').text('');
        $('#replyToMessage').text('');
        $('#replyPreview').slideUp(200);
    }

    function scrollToOriginalMessage(messageId) {
        const chatMessages = $('#chatMessages');
        const chatMessagesContainer = $('#chatMessagesContainer');
        const targetMsg = chatMessages.find(`.message[data-message-id="${messageId}"]`);
        
        if (targetMsg.length) {
            $('.message-highlight').removeClass('message-highlight');
            const chatMessagesRect = chatMessagesContainer[0].getBoundingClientRect();
            const targetRect = targetMsg[0].getBoundingClientRect();
            const scrollTop = chatMessagesContainer.scrollTop();
            const targetScrollTop = scrollTop + (targetRect.top - chatMessagesRect.top) - (chatMessagesRect.height / 2) + (targetRect.height / 2);
            
            chatMessagesContainer.animate({
                scrollTop: targetScrollTop
            }, 500, function() {
                targetMsg.addClass('message-highlight');
                setTimeout(() => {
                    targetMsg.removeClass('message-highlight');
                }, 3000);
            });
        } else {
            if (currentCustomerId) {
                loadMessages(currentCustomerId);
                setTimeout(() => {
                    scrollToOriginalMessage(messageId);
                }, 500);
            }
        }
    }

    // Reply button click handler
    $(document).on('click', '.message-reply-btn', function(e) {
        e.stopPropagation();
        e.preventDefault();
        const $btn = $(this);
        const messageId = $btn.attr('data-message-id') || $btn.data('message-id');
        const senderName = $btn.attr('data-sender-name') || $btn.data('sender-name') || 'Unknown';
        const messageText = $btn.attr('data-message-text') || $btn.data('message-text') || '';
        
        if (!messageId) {
            console.error('Message ID not found');
            return;
        }
        
        setReplyTo(messageId, senderName, messageText);
    });

    // Cancel reply button
    $('#cancelReply').on('click', function() {
        cancelReply();
    });

    // Show reply button on hover
    $(document).on('mouseenter', '.message', function() {
        $(this).find('.message-reply-btn').css('opacity', '1');
        $(this).find('.message-translate-btn').css('opacity', '1');
    }).on('mouseleave', '.message', function() {
        $(this).find('.message-reply-btn').css('opacity', '0');
        $(this).find('.message-translate-btn').css('opacity', '0');
    });

    // Translate button click (Gemini 2.0 Flash)
    $(document).on('click', '.message-translate-btn', function(e) {
        e.stopPropagation();
        e.preventDefault();
        const $btn = $(this);
        const $msg = $btn.closest('.message');
        const msgId = $msg.attr('data-message-id');
        const text = $btn.attr('data-message-text') || $btn.data('message-text') || '';
        const $translatedDiv = $msg.find('.message-translated');
        const targetLang = $('#translateTargetLang').val() || 'Hindi';
        if (translatedMessages[msgId]) {
            delete translatedMessages[msgId];
            $translatedDiv.hide().empty();
            return;
        }
        if (!text || text === '[Attachment]') {
            alert('No text to translate');
            return;
        }
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.ajax({
            url: '{{ route("freelancer.customer-chats.translate") }}',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                _token: '{{ csrf_token() }}',
                text: text,
                target_language: targetLang
            }),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).done(function(data) {
            if (data.success) {
                translatedMessages[msgId] = { text: data.translated || '', lang: targetLang };
                $translatedDiv.html('<small class="text-muted">' + escapeHtml(targetLang) + ':</small> ' + escapeHtml(data.translated || '')).show();
            } else {
                alert(data.message || 'Translation failed');
            }
        }).fail(function(xhr) {
            var msg = 'Translation failed';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            else if (xhr.status === 419) msg = 'Session expired. Refresh the page and try again.';
            alert(msg);
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="fa fa-language"></i>');
        });
    });

    // Mobile functions
    window.showCustomerListMobile = function() {
        $('.mobile-chat-wrapper').removeClass('show-chat');
    };
    
    window.scrollToOriginalMessage = scrollToOriginalMessage;

    // Initial unread counts update
    updateUnreadCounts();
    const chatRealtimeUserId = @json(Auth::id());
    if (window.ChatRealtime && window.chatRealtimeConfig && window.chatRealtimeConfig.enabled && chatRealtimeUserId) {
        window.ChatRealtime.subscribeUserChannel(chatRealtimeUserId, function(payload, eventName) {
            updateUnreadCounts();
        });
    } else if (window.chatRealtimeConfig && !window.chatRealtimeConfig.enabled) {
        setInterval(updateUnreadCounts, 30000);
    }

    // WebRTC Call functionality
    let callInitiatedByUser = false; // Track if call was initiated by user click
    const configuration = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ]
    };

    // Call button click - only initiate call when explicitly clicked
    $('#callBtn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Call button clicked');
        
        // Double check that button is not disabled and customer is selected
        if ($(this).prop('disabled')) {
            console.log('Call button is disabled');
            return;
        }
        
        if (!currentCustomerId) {
            alert('Please select a customer first');
            return;
        }
        
        // Prevent multiple clicks
        if (isCallActive) {
            console.log('Call already active');
            return;
        }
        
        // Mark that call is initiated by user
        callInitiatedByUser = true;
        initiateCall();
    });

    // Poll for incoming calls - only when customer is selected
    let incomingCallCheckInterval = null;
    
    function startIncomingCallCheck() {
        // Clear any existing interval
        if (incomingCallCheckInterval) {
            clearInterval(incomingCallCheckInterval);
        }
        
        // Only start checking if customer is selected
        if (currentCustomerId) {
            incomingCallCheckInterval = setInterval(checkForIncomingCall, 2000);
        }
    }
    
    function stopIncomingCallCheck() {
        if (incomingCallCheckInterval) {
            clearInterval(incomingCallCheckInterval);
            incomingCallCheckInterval = null;
        }
    }

    function checkForIncomingCall() {
        // Don't check if call is already active or no customer selected
        if (isCallActive || !currentCustomerId) {
            return;
        }
        
        // Don't check if modal is already visible (to prevent duplicate calls)
        if ($('#callModal').is(':visible') || $('#callModal').css('display') !== 'none') {
            return;
        }
        
        // Only check if we're actually in a chat with a customer
        if (currentCustomerId !== $('#receiverId').val()) {
            return;
        }
        
        $.get('{{ route("freelancer.customer-chats.call.offer") }}', { customer_id: currentCustomerId })
            .done(function(response) {
                // Only handle if we have an offer, call is not active, and modal is not visible
                if (response.offer && response.customer_id && !isCallActive) {
                    // Double check modal is not visible
                    if ($('#callModal').is(':visible') || $('#callModal').css('display') !== 'none') {
                        return;
                    }
                    
                    // Only show if this is for the currently selected customer
                    if (response.customer_id == currentCustomerId) {
                        incomingCallCustomerId = response.customer_id;
                        handleIncomingCall(JSON.parse(response.offer), response.customer_id);
                    }
                }
            })
            .fail(function() {
                // Silently fail - don't show errors for polling
            });
    }

    function handleIncomingCall(offer, customerId) {
        // Double check that we're not already in a call
        if (isCallActive) {
            return;
        }
        
        const customerName = $('.chat-user-item[data-customer-id="' + customerId + '"]').find('.customer-name').text() || 'Customer';
        $('#callerName').text('Incoming call from ' + customerName);
        $('#callStatus').text('Incoming call...');
        $('#callModal').css('display', 'flex').fadeIn();
        
        // Stop polling while call modal is open
        stopIncomingCallCheck();
        
        // Auto-accept incoming call
        acceptIncomingCall(offer, customerId);
    }
    
    function acceptIncomingCall(offer, customerId) {
        navigator.mediaDevices.getUserMedia({ audio: true, video: false })
            .then(stream => {
                localStream = stream;
                document.getElementById('localAudio').srcObject = stream;
                
                peerConnection = new RTCPeerConnection(configuration);
                
                stream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, stream);
                });
                
                peerConnection.ontrack = (event) => {
                    document.getElementById('remoteAudio').srcObject = event.streams[0];
                    $('#callStatus').text('Connected');
                    isCallActive = true;
                };
                
                peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        sendIceCandidate(event.candidate, customerId);
                    }
                };
                
                const offerObj = new RTCSessionDescription(offer);
                peerConnection.setRemoteDescription(offerObj)
                    .then(() => {
                        return peerConnection.createAnswer();
                    })
                    .then(answer => {
                        return peerConnection.setLocalDescription(answer);
                    })
                    .then(() => {
                        sendCallAnswer(peerConnection.localDescription, customerId);
                        $('#callStatus').text('Connecting...');
                    })
                    .catch(error => {
                        console.error('Error accepting call:', error);
                        endCall();
                    });
            })
            .catch(error => {
                console.error('Error accessing microphone:', error);
                alert('Microphone access denied');
                endCall();
            });
    }

    function initiateCall() {
        // Prevent auto-calling - only call when explicitly initiated by user
        if (!callInitiatedByUser) {
            console.log('Call not initiated by user - preventing auto-call');
            return;
        }
        
        // Prevent auto-calling - only call when explicitly initiated
        if (isCallActive) {
            console.log('Call already active');
            return;
        }
        
        if (!currentCustomerId) {
            console.log('No customer selected');
            callInitiatedByUser = false;
            return;
        }
        
        console.log('Initiating call...');
        
        // Disable call button during call initiation
        $('#callBtn').prop('disabled', true);
        
        const callerName = $('#chatHeader').text();
        $('#callerName').text('Calling ' + callerName);
        $('#callStatus').text('Connecting...');
        $('#callModal').css('display', 'flex').fadeIn();
        
        // Get user media
        navigator.mediaDevices.getUserMedia({ audio: true, video: false })
            .then(stream => {
                localStream = stream;
                document.getElementById('localAudio').srcObject = stream;
                
                // Create peer connection
                peerConnection = new RTCPeerConnection(configuration);
                
                // Add local stream tracks
                stream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, stream);
                });
                
                // Handle remote stream
                peerConnection.ontrack = (event) => {
                    document.getElementById('remoteAudio').srcObject = event.streams[0];
                    $('#callStatus').text('Connected');
                    isCallActive = true;
                };
                
                // Handle ICE candidates
                peerConnection.onicecandidate = (event) => {
                    if (event.candidate) {
                        sendIceCandidate(event.candidate, currentCustomerId);
                    }
                };
                
                // Create offer
                peerConnection.createOffer()
                    .then(offer => {
                        return peerConnection.setLocalDescription(offer);
                    })
                    .then(() => {
                        sendCallOffer(peerConnection.localDescription);
                    })
                    .catch(error => {
                        console.error('Error creating offer:', error);
                        alert('Failed to initiate call');
                        callInitiatedByUser = false;
                        endCall();
                    });
            })
            .catch(error => {
                console.error('Error accessing microphone:', error);
                alert('Microphone access denied. Please allow microphone access.');
                $('#callModal').fadeOut().css('display', 'none');
                $('#callBtn').prop('disabled', false);
                callInitiatedByUser = false;
            });
    }

    function sendCallOffer(offer) {
        $.ajax({
            url: '{{ route("freelancer.customer-chats.call.offer.send") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                customer_id: currentCustomerId,
                offer: JSON.stringify(offer)
            },
            success: function(response) {
                if (response.answer) {
                    handleCallAnswer(JSON.parse(response.answer));
                } else {
                    // Start polling for answer
                    pollForCallAnswer();
                }
            },
            error: function(xhr) {
                console.error('Error sending offer:', xhr);
                alert('Failed to initiate call');
                callInitiatedByUser = false;
                endCall();
            }
        });
    }

    function pollForCallAnswer() {
        const answerInterval = setInterval(() => {
            if (!isCallActive && peerConnection) {
                $.get('{{ route("freelancer.customer-chats.call.answer.get") }}', {
                    customer_id: currentCustomerId
                })
                .done(function(response) {
                    if (response.answer) {
                        clearInterval(answerInterval);
                        handleCallAnswer(JSON.parse(response.answer));
                    }
                });
            } else {
                clearInterval(answerInterval);
            }
        }, 1000);
    }

    function handleCallAnswer(answer) {
        if (!peerConnection) return;
        
        peerConnection.setRemoteDescription(new RTCSessionDescription(answer))
            .then(() => {
                $('#callStatus').text('Connected');
                isCallActive = true;
            })
            .catch(error => {
                console.error('Error setting remote description:', error);
            });
    }

    // Hang up button
    $('#hangupBtn').on('click', function() {
        endCall();
    });

    // Mute button
    $('#muteBtn').on('click', function() {
        toggleMute();
    });

    function sendCallAnswer(answer, customerId) {
        $.post('{{ route("freelancer.customer-chats.call.answer") }}', {
            _token: '{{ csrf_token() }}',
            customer_id: customerId,
            answer: JSON.stringify(answer)
        });
    }

    function sendIceCandidate(candidate, customerId) {
        $.post('{{ route("freelancer.customer-chats.call.ice.send") }}', {
            _token: '{{ csrf_token() }}',
            customer_id: customerId,
            candidate: JSON.stringify(candidate)
        });
    }

    function toggleMute() {
        if (localStream) {
            isMuted = !isMuted;
            localStream.getAudioTracks().forEach(track => {
                track.enabled = !isMuted;
            });
            $('#muteBtn').html(isMuted ? '<i class="fas fa-microphone-slash"></i>' : '<i class="fas fa-microphone"></i>');
            $('#muteBtn').toggleClass('btn-danger', isMuted).toggleClass('btn-secondary', !isMuted);
        }
    }

    function endCall() {
        console.log('Ending call...');
        
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
            localStream = null;
        }
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }
        isCallActive = false;
        isMuted = false;
        callInitiatedByUser = false;
        $('#callModal').fadeOut().css('display', 'none');
        $('#callStatus').text('Call ended');
        
        // Re-enable call button if customer is still selected
        if (currentCustomerId) {
            $('#callBtn').prop('disabled', false);
            startIncomingCallCheck();
        } else {
            $('#callBtn').prop('disabled', true);
        }
        
        const customerIdToEnd = incomingCallCustomerId || currentCustomerId;
        if (customerIdToEnd) {
            $.post('{{ route("freelancer.customer-chats.call.end") }}', {
                _token: '{{ csrf_token() }}',
                customer_id: customerIdToEnd
            });
            incomingCallCustomerId = null;
        }
    }

    // Poll for ICE candidates (for both incoming and outgoing calls)
    setInterval(function() {
        if (isCallActive && peerConnection && currentCustomerId) {
            const customerId = incomingCallCustomerId || currentCustomerId;
            $.get('{{ route("freelancer.customer-chats.call.ice") }}', { customer_id: customerId })
                .done(function(response) {
                    if (response.candidates && response.candidates.length > 0) {
                        response.candidates.forEach(candidate => {
                            peerConnection.addIceCandidate(new RTCIceCandidate(JSON.parse(candidate)));
                        });
                    }
                });
        }
    }, 1000);
});
</script>
<style>
    .reply-preview-box {
        background: #f0f2f5;
        border-top: 2px solid #25d366;
        animation: slideDown 0.2s ease;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .message-reply-btn:hover {
        background: rgba(0,0,0,0.1) !important;
        color: #25d366 !important;
    }
    .replied-message:hover {
        background: rgba(37,211,102,0.2) !important;
    }
    .message-highlight {
        animation: highlightPulse 2s ease;
    }
    @keyframes highlightPulse {
        0% { background-color: transparent; }
        10% { background-color: rgba(247,148,29,0.3); transform: scale(1.02); }
        50% { background-color: rgba(247,148,29,0.2); }
        100% { background-color: transparent; transform: scale(1); }
    }
</style>
@endsection
