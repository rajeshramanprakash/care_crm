@extends('customer.layouts.app')
@section('title', 'Chats')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
@php
// Check if customer has any deployments (vendors or freelancers assigned)
$hasDeployments = isset($sortedChats) && $sortedChats->count() > 0;
@endphp
@if(!$hasDeployments && $customerType !== 'operation_lead')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Chat is available when you have an assigned vendor or freelancer, or a consultation booking with a doctor.
            </div>
        </div>
    </section>
</div>
@else
<div class="content-wrapper">
    <div class="container-fluid d-flex justify-content-center align-items-start">
        <div class="chat-card row w-100 justify-content-center align-items-stretch mobile-chat-wrapper" style="overflow: hidden;">
            <!-- Sidebar: Vendor/Freelancer List -->
            <div class="col-md-4 col-lg-4 chat-sidebar p-0 d-flex flex-column border-end mobile-user-list" style="background: #f9f9f9; min-width: 270px; max-width: 100%;">
                <div class="chat-sidebar-header d-flex flex-column px-3 py-2 border-bottom bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="mb-0" style="font-size:18px;">Chats</h5>
                        <input type="text" class="form-control" id="searchChat" placeholder="Search..." style="width: 150px; font-size:12px;">
                    </div>
                </div>

                <div class="chat-user-list overflow-auto flex-grow-1" id="chatsList" style="background: #f9f9f9;">
                    @forelse($sortedChats as $chat)
                        @php
                            // Ensure we have a name for display
                            $chatName = $chat->name ?? $chat->customer_name ?? match ($chat->chat_type ?? '') {
                                'vendor' => 'Vendor #' . $chat->id,
                                'doctor' => 'Doctor #' . $chat->id,
                                default => 'Freelancer #' . $chat->id,
                            };
                            $chatInitial = strtoupper(substr($chatName, 0, 1));
                            $profileImage = $chat->profile_image ? Storage::url($chat->profile_image) : '';
                        @endphp
                        <div class="chat-user-item d-flex align-items-center px-3 py-2" data-chat-type="{{ $chat->chat_type }}" data-chat-id="{{ $chat->id }}" data-profile-image="{{ $profileImage }}" data-initial="{{ $chatInitial }}" style="text-decoration:none; cursor: pointer;">
                            <div class="me-3" style="width: 44px; height: 44px; position: relative;">
                                @if($chat->profile_image)
                                    <img src="{{ $profileImage }}" alt="{{ $chatName }}" class="rounded-circle" style="width: 44px; height: 44px; object-fit: cover; border: 2px solid #F7941D;" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="user-initial-avatar" style="display: none; background: #F7941D; width: 44px; height: 44px; border-radius: 50%; color: #fff; font-weight: 600; font-size: 1.2em; align-items: center; justify-content: center; position: absolute; top: 0; left: 0;">
                                        {{ $chatInitial }}
                                    </div>
                                @else
                                    <div class="user-initial-avatar" style="background: #F7941D; width: 44px; height: 44px; border-radius: 50%; color: #fff; font-weight: 600; font-size: 1.2em; display: flex; align-items: center; justify-content: center;">
                                        {{ $chatInitial }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold chat-name" style="font-size:14px;">
                                        {{ $chatName }}
                                    </span>
                                    <small class="text-muted last-message-time" style="font-size:0.85em;">
                                        @if($chat->last_message)
                                            {{ \Carbon\Carbon::parse($chat->last_message->created_at)->format('h:i A') }}
                                        @endif
                                    </small>
                                </div>
                                <div class="text-muted small last-message-preview" style="font-size:0.97em; white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px;">
                                    @if($chat->last_message)
                                        @if($chat->last_message->attachment)
                                            <i class="fa fa-paperclip"></i> Attachment
                                        @elseif($chat->last_message->message)
                                            {{ $chat->last_message->message }}
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <span class="badge unread-count ms-2" style="{{ ($chat->unread_count ?? 0) > 0 ? '' : 'display: none;' }} background: #25d366; color: #fff; font-weight: bold;">{{ $chat->unread_count ?? 0 }}</span>
                        </div>
                    @empty
                        <div class="text-center p-4 text-muted" style="padding: 40px 20px;">
                            <i class="fas fa-comments fa-3x mb-3" style="color: #ccc;"></i>
                            <h6 style="color: #666; margin-bottom: 10px;">कोई चैट उपलब्ध नहीं</h6>
                            <p style="color: #999; font-size: 0.9rem; line-height: 1.6;">
                                जब आपको Vendor/Freelancer assign होगा या डॉक्टर के साथ अपॉइंटमेंट बुक होगा, तब आप यहाँ चैट कर पाएंगे।
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
            <!-- Main Chat Area -->
            <div class="col-md-8 col-lg-8 chat-main p-0 d-flex flex-column mobile-chat-area" style="background: #fff;">
                <div class="chat-header d-flex align-items-center px-2 py-2 border-bottom bg-white">
                    <button class="btn btn-light rounded-circle d-md-none me-2 mobile-back-btn" style="width: 40px; height: 40px; display: none;" onclick="showChatListMobile()">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="position-relative me-3">
                        <img id="chatHeaderAvatar" src="" alt="User" class="rounded-circle chat-user-avatar" style="display: none; width: 44px; height: 44px; object-fit: cover; border: 2px solid #F7941D;" onerror="this.style.display='none'; $('#chatHeaderAvatarFallback').css('display', 'flex');">
                        <div id="chatHeaderAvatarFallback" class="user-initial-avatar chat-user-avatar" style="width: 44px; height: 44px; border-radius: 50%; background: #F7941D; color: #fff; font-weight: 600; display: flex; align-items: center; justify-content: center;">V</div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0" id="chatHeader">Select a contact to start chatting</h6>
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
                <div id="customerDoctorScheduleBanner" class="alert alert-warning mb-0 rounded-0 d-none py-2 px-3" style="border-radius: 0 !important; border-left: none; border-right: none; margin: 0;" role="alert"></div>
                <div class="chat-messages flex-grow-1 position-relative" id="chatMessagesContainer" style="background: #f8f9fa url('{{ asset('images/chatbackground.png') }}') center center / cover no-repeat; overflow-y: auto; padding: 32px; min-height: 350px; max-height: 71vh;">
                    <div id="chatMessages" style="position: relative;">
                        <div class="chat-welcome text-center mt-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h4>Select a contact to start chatting</h4>
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
                        <input type="hidden" id="receiverType" name="receiver_type">
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
    #searchChat { border-radius: 12px; border: 1px solid #e0e0e0; padding: 8px 14px; font-size: 1em; }
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
        max-height: 60vh;
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

@php
    $__custReverbCfg = [
        'enabled' => config('broadcasting.default') === 'reverb' && filled(config('broadcasting.connections.reverb.key')),
        'key' => config('broadcasting.connections.reverb.key'),
        'wsHost' => config('broadcasting.connections.reverb.options.host'),
        'wsPort' => (int) config('broadcasting.connections.reverb.options.port'),
        'scheme' => config('broadcasting.connections.reverb.options.scheme', 'http'),
        'channelPrefix' => \App\Support\DoctorCustomerChatRealtime::CHANNEL_PREFIX,
    ];
@endphp
<script>
window.__custDoctorRealtimeConfig = @json($__custReverbCfg);
window.__custDoctorRealtimeSignatures = @json($customerDoctorRealtimeSignatures ?? []);
window.__custRealtimeOpLeadIds = @json($customerRealtimeOperationLeadIds ?? []);
window.__custDoctorRealtimePollMs = 2500;
</script>
<script src="{{ asset('js/pusher.min.js') }}"></script>
<script src="{{ asset('js/echo.iife.js') }}"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script>
// All variables declared at top level to avoid initialization errors
let currentChatType = null;
let currentChatId = null;
let messagePollingInterval = null;
let lastMessageCount = 0;
let userHasScrolledManually = false;
let lastMessageId = 0;
let translatedMessages = {};

/** Doctor chat: messaging only during booked consultation window (server sends chat_schedule). */
let customerDoctorChatScheduleAllowed = true;

function applyCustomerDoctorChatSchedule(schedule, chatType) {
    if (chatType !== 'doctor') {
        customerDoctorChatScheduleAllowed = true;
        $('#customerDoctorScheduleBanner').addClass('d-none').empty();
        return;
    }
    // null/undefined = schedule not loaded yet (keep composer locked); object = server decision
    if (schedule == null) {
        customerDoctorChatScheduleAllowed = false;
        $('#customerDoctorScheduleBanner').addClass('d-none').empty();
        return;
    }
    customerDoctorChatScheduleAllowed = schedule.allowed !== false;
    const $b = $('#customerDoctorScheduleBanner');
    if (schedule.allowed === false) {
        $b.removeClass('d-none').text(
            schedule.user_message || 'आप डॉक्टर को संदेश केवल अपनी बुक की हुई अपॉइंटमेंट की तारीख और समय के दौरान ही भेज सकते हैं। You can message this doctor only during your booked slot.'
        );
    } else {
        $b.addClass('d-none').empty();
    }
}

function updateCustomerComposerLock() {
    const hasChat = !!(currentChatType && currentChatId);
    let locked = !hasChat;
    if (hasChat && currentChatType === 'doctor' && !customerDoctorChatScheduleAllowed) {
        locked = true;
    }
    $('#messageInput').prop('disabled', locked);
    $('#attachmentBtn').prop('disabled', locked);
    $('#messageForm button[type="submit"]').prop('disabled', locked);
}

// WebRTC Call variables - declared at top level
let localStream = null;
let peerConnection = null;
let isCallActive = false;
let isMuted = false;
let callInitiatedByUser = false;

$(document).ready(function() {
    // Ensure call modal is hidden on page load
    $('#callModal').hide();
    
    // Initialize call state
    isCallActive = false;
    callInitiatedByUser = false;
    
    // Search chats
    $('#searchChat').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        $('.chat-user-item').each(function() {
            const chatName = $(this).find('.chat-name').text().toLowerCase();
            $(this).toggle(chatName.includes(searchText));
        });
    });

    // Chat selection
    $(document).on('click', '.chat-user-item', function() {
        const chatType = $(this).data('chat-type');
        const chatId = $(this).data('chat-id');
        if (!chatType || !chatId) return;

        // Clear previous polling
        if (messagePollingInterval) {
            clearInterval(messagePollingInterval);
            messagePollingInterval = null;
        }

        // End any active call before switching chats
        if (isCallActive) {
            endCall();
        }

        // Clear messages
        $('#chatMessages').html('');
        currentChatType = chatType;
        currentChatId = chatId;
        lastMessageCount = 0;
        userHasScrolledManually = false;
        lastMessageId = 0;
        translatedMessages = {};

        // Update UI
        $('.chat-user-item').removeClass('active');
        $(this).addClass('active');
        
        const chatName = $(this).find('.chat-name').text();
        const profileImage = $(this).data('profile-image');
        const initial = $(this).data('initial');
        
        $('#chatHeader').text(chatName);
        
        // Update chat header avatar
        if (profileImage) {
            $('#chatHeaderAvatar').attr('src', profileImage).show();
            $('#chatHeaderAvatarFallback').hide();
        } else {
            $('#chatHeaderAvatar').hide();
            $('#chatHeaderAvatarFallback').text(initial).show();
        }
        
        $('#callBtn').prop('disabled', false);
        $('#receiverType').val(chatType);
        $('#receiverId').val(chatId);
        applyCustomerDoctorChatSchedule(null, chatType);
        updateCustomerComposerLock();
        $('.chat-welcome').hide();

        // Load messages
        loadMessages(chatType, chatId, true); // Initial load, scroll to bottom

        // Start polling (slower when Reverb is active)
        messagePollingInterval = setInterval(() => {
            if (currentChatType == chatType && currentChatId == chatId) {
                loadMessages(chatType, chatId, false); // Polling, don't force scroll
            }
        }, window.__custDoctorRealtimePollMs || 2500);

        // Mark as read
        markAsRead(chatType, chatId);

        // Mobile view
        if ($(window).width() <= 767) {
            $('.mobile-chat-wrapper').addClass('show-chat');
        }
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
        updateCustomerComposerLock();
    });

    // Send message
    $('#messageForm').on('submit', function(e) {
        e.preventDefault();
        const message = $('#messageInput').val().trim();
        const receiverType = $('#receiverType').val();
        const receiverId = $('#receiverId').val();
        const fileInput = $('#attachmentInput')[0];
        
        if ((!message || message === '') && !fileInput.files[0]) return;
        if (!receiverType || !receiverId) return;

        if (receiverType === 'doctor' && !customerDoctorChatScheduleAllowed) {
            alert('आप डॉक्टर को संदेश केवल अपनी बुक की हुई अपॉइंटमेंट की तारीख और समय के दौरान ही भेज सकते हैं। You can only message during your booked appointment time.');
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('receiver_type', receiverType);
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
            url: '{{ route("customer.chats.send") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#messageInput').val('');
                $('#attachmentInput').val('');
                cancelReply();
                // Reload messages immediately after sending (with forceReload=true to bypass lastMessageId filter)
                if (currentChatType == receiverType && currentChatId == receiverId) {
                    loadMessages(receiverType, receiverId, true, true); // Force scroll and force reload when sending
                    // Update lastMessageId after message is loaded
                    if (response && response.id) {
                        setTimeout(function() {
                            lastMessageId = response.id;
                        }, 300);
                    }
                }
            },
            error: function(xhr) {
                console.error('Error sending message:', xhr);
                let errorMessage = 'Failed to send message';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                    if (xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        errorMessage += ': ' + errors.join(', ');
                    }
                } else if (xhr.status === 401) {
                    errorMessage = 'You are not authorized. Please login again.';
                } else if (xhr.status === 403) {
                    errorMessage = 'You are not assigned to chat with this person.';
                } else if (xhr.status === 422) {
                    errorMessage = 'Please provide a message or attachment.';
                }
                if (xhr.responseJSON && xhr.responseJSON.chat_schedule && currentChatType === 'doctor') {
                    applyCustomerDoctorChatSchedule(xhr.responseJSON.chat_schedule, 'doctor');
                    updateCustomerComposerLock();
                }
                alert(errorMessage);
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

    // Load messages
    function loadMessages(chatType, chatId, shouldScroll = false, forceReload = false) {
        if (!chatType || !chatId) return;

        // Build URL with last_message_id if it exists (for polling)
        // Don't use lastMessageId filter if forceReload is true (e.g., after sending a message)
        let url = '{{ route("customer.chats.messages", [":type", ":id"]) }}'.replace(':type', chatType).replace(':id', chatId);
        if (lastMessageId > 0 && !forceReload) {
            url += '?last_message_id=' + lastMessageId;
        }

        $.get(url)
            .done(function(payload) {
                if (currentChatType != chatType || currentChatId != chatId) return;

                let items = [];
                if (Array.isArray(payload)) {
                    items = payload;
                    if (chatType === 'doctor') {
                        applyCustomerDoctorChatSchedule({ allowed: true }, 'doctor');
                    } else {
                        applyCustomerDoctorChatSchedule(null, chatType);
                    }
                } else if (payload && Array.isArray(payload.items)) {
                    items = payload.items;
                    applyCustomerDoctorChatSchedule(
                        chatType === 'doctor' ? (payload.chat_schedule ?? null) : null,
                        chatType
                    );
                } else {
                    console.error('loadMessages: unexpected response', payload);
                    $('#chatMessages').html(
                        '<div class="alert alert-warning m-3">Could not load messages. Please refresh the page.</div>'
                    );
                    return;
                }
                updateCustomerComposerLock();

                const chatMessages = $('#chatMessages');
                const chatMessagesContainer = $('#chatMessagesContainer');
                const wasAtBottom = isScrolledToBottom(chatMessagesContainer[0]);
                const isInitialLoad = lastMessageId === 0 || forceReload;
                
                // If initial load or force reload, replace all messages. If polling, only append new ones
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
                        
                        const isOutgoing = call.caller_type === 'customer';
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

                    const isSent = msg.sender_type === 'customer';
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
                            else if (senderType === 'doctor') rS = 'Doctor';
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
                if (lastMessageIdInBatch > lastMessageId) {
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
    function markAsRead(chatType, chatId) {
        $.post('{{ route("customer.chats.mark-read", [":type", ":id"]) }}'.replace(':type', chatType).replace(':id', chatId), {
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
            // Remove previous highlights
            $('.message-highlight').removeClass('message-highlight');
            
            // Calculate scroll position
            const chatMessagesRect = chatMessagesContainer[0].getBoundingClientRect();
            const targetRect = targetMsg[0].getBoundingClientRect();
            const scrollTop = chatMessagesContainer.scrollTop();
            
            // Calculate target scroll position to center the message
            const targetScrollTop = scrollTop + (targetRect.top - chatMessagesRect.top) - (chatMessagesRect.height / 2) + (targetRect.height / 2);
            
            // Smooth scroll to the message
            chatMessagesContainer.animate({
                scrollTop: targetScrollTop
            }, 500, function() {
                // Highlight the message after scrolling
                targetMsg.addClass('message-highlight');
                setTimeout(() => {
                    targetMsg.removeClass('message-highlight');
                }, 3000);
            });
        } else {
            // If message not found, reload messages and try again
            if (currentChatType && currentChatId) {
                loadMessages(currentChatType, currentChatId);
                setTimeout(() => {
                    scrollToOriginalMessage(messageId);
                }, 500);
            }
        }
    }

    // Reply button click handler - WhatsApp style
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
            if (typeof toastr !== 'undefined') toastr.warning('No text to translate'); else alert('No text to translate');
            return;
        }
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.ajax({
            url: '{{ route("customer.chats.translate") }}',
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
                if (typeof toastr !== 'undefined') toastr.error(data.message || 'Translation failed'); else alert(data.message || 'Translation failed');
            }
        }).fail(function(xhr) {
            var msg = 'Translation failed';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            else if (xhr.status === 419) msg = 'Session expired. Refresh the page and try again.';
            if (typeof toastr !== 'undefined') toastr.error(msg); else alert(msg);
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="fa fa-language"></i>');
        });
    });

    // Mobile functions
    window.showChatListMobile = function() {
        $('.mobile-chat-wrapper').removeClass('show-chat');
    };
    
    window.scrollToOriginalMessage = scrollToOriginalMessage;

    // WebRTC Call functionality
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
        
        // Double check that button is not disabled and chat is selected
        if ($(this).prop('disabled')) {
            console.log('Call button is disabled');
            return;
        }
        
        if (!currentChatType || !currentChatId) {
            alert('Please select a chat first');
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

    // Hang up button
    $('#hangupBtn').on('click', function() {
        endCall();
    });

    // Mute button
    $('#muteBtn').on('click', function() {
        toggleMute();
    });

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
        
        if (!currentChatType || !currentChatId) {
            console.log('No chat selected');
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
                        sendIceCandidate(event.candidate);
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
                        callInitiatedByUser = false; // Reset flag on error
                        endCall();
                    });
            })
            .catch(error => {
                console.error('Error accessing microphone:', error);
                alert('Microphone access denied. Please allow microphone access.');
                $('#callModal').fadeOut().css('display', 'none');
                $('#callBtn').prop('disabled', false); // Re-enable button on error
                callInitiatedByUser = false; // Reset flag on error
            });
    }

    function sendCallOffer(offer) {
        $.ajax({
            url: '{{ route("customer.chats.call.offer") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                receiver_type: currentChatType,
                receiver_id: currentChatId,
                offer: JSON.stringify(offer)
            },
            success: function(response) {
                if (response.answer) {
                    handleCallAnswer(JSON.parse(response.answer));
                }
                // Start polling for answer
                pollForCallAnswer();
            },
            error: function(xhr) {
                console.error('Error sending offer:', xhr);
                alert('Failed to initiate call');
                callInitiatedByUser = false; // Reset flag on error
                endCall();
            }
        });
    }

    function pollForCallAnswer() {
        const answerInterval = setInterval(() => {
            if (!isCallActive && peerConnection) {
                $.get('{{ route("customer.chats.call.answer") }}', {
                    receiver_type: currentChatType,
                    receiver_id: currentChatId
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
        peerConnection.setRemoteDescription(new RTCSessionDescription(answer))
            .then(() => {
                $('#callStatus').text('Connected');
                isCallActive = true;
            })
            .catch(error => {
                console.error('Error setting remote description:', error);
            });
    }

    function sendIceCandidate(candidate) {
        $.post('{{ route("customer.chats.call.ice") }}', {
            _token: '{{ csrf_token() }}',
            receiver_type: currentChatType,
            receiver_id: currentChatId,
            candidate: JSON.stringify(candidate)
        });
    }

    // Poll for ICE candidates from vendor/freelancer
    setInterval(function() {
        if (isCallActive && peerConnection && currentChatType && currentChatId) {
            $.get('{{ route("customer.chats.call.ice") }}', {
                receiver_type: currentChatType,
                receiver_id: currentChatId
            })
            .done(function(response) {
                if (response.candidates && response.candidates.length > 0) {
                    response.candidates.forEach(candidate => {
                        peerConnection.addIceCandidate(new RTCIceCandidate(JSON.parse(candidate)));
                    });
                }
            });
        }
    }, 1000);

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
        callInitiatedByUser = false; // Reset flag
        $('#callModal').fadeOut().css('display', 'none');
        $('#callStatus').text('Call ended');
        
        // Re-enable call button if chat is still selected
        if (currentChatType && currentChatId) {
            $('#callBtn').prop('disabled', false);
        } else {
            $('#callBtn').prop('disabled', true);
        }
        
        // Notify server
        if (currentChatType && currentChatId) {
            $.post('{{ route("customer.chats.call.end") }}', {
                _token: '{{ csrf_token() }}',
                receiver_type: currentChatType,
                receiver_id: currentChatId
            });
        }
    }

    function custWsTargetsOpenDoctorChat(e) {
        if (currentChatType !== 'doctor' || currentChatId == null) return false;
        if (String(e.doctor_request_id || '') !== String(currentChatId)) return false;
        const m = e.message;
        if (!m) return false;
        const custOl = m.sender_type === 'customer' ? String(m.sender_id) : String(m.receiver_id);
        const ids = (window.__custRealtimeOpLeadIds || []).map(String);
        return ids.indexOf(custOl) !== -1;
    }

    (function initCustomerDoctorReverb() {
        var cfg = window.__custDoctorRealtimeConfig || {};
        var sigs = window.__custDoctorRealtimeSignatures || [];
        if (!cfg.enabled || !cfg.key || typeof window.Pusher !== 'function') {
            return;
        }
        var EchoCtor = (window.Echo && window.Echo.default) ? window.Echo.default : window.Echo;
        if (typeof EchoCtor !== 'function') {
            return;
        }
        window.Pusher.logToConsole = false;
        try {
            window.__dcCustDoctorEcho = window.__dcCustDoctorEcho || new EchoCtor({
                broadcaster: 'reverb',
                key: cfg.key,
                cluster: '',
                wsHost: cfg.wsHost,
                wsPort: cfg.wsPort,
                wssPort: cfg.wsPort,
                forceTLS: String(cfg.scheme || 'http').toLowerCase() === 'https',
                enabledTransports: ['ws', 'wss'],
            });
            var prefix = cfg.channelPrefix || 'doctor-customer.';
            sigs.forEach(function (sig) {
                if (!sig) return;
                window.__dcCustDoctorEcho.channel(prefix + sig)
                    .listen('.doctor.customer.message', function (e) {
                        if (custWsTargetsOpenDoctorChat(e) && currentChatType && currentChatId) {
                            loadMessages(currentChatType, currentChatId, false, true);
                        }
                    })
                    .listen('.doctor.customer.unread', function () {
                        if (currentChatType === 'doctor' && currentChatId) {
                            loadMessages(currentChatType, currentChatId, false, true);
                        }
                    });
            });
        } catch (err) {
            if (window.console && console.warn) {
                console.warn('Customer doctor chat Reverb init failed:', err);
            }
        }
    })();
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
@endif
@endsection


