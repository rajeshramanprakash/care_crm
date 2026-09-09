@extends('admin.layouts.app')

@section('content')
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #F7941D 0%, #e67e22 100%);
        --primary-color: #F7941D;
        --secondary-bg: #f8f9fa;
        --border-color: #e2e8f0;
        --text-primary: #2d3748;
        --text-secondary: #718096;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
        --shadow-md: 0 4px 6px rgba(0,0,0,0.05);
    }

    .content-wrapper {
        background: #f4f6f9;
    }

    /* Main Card */
    .chat-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        background: #fff;
        overflow: hidden;
        height: 600px; /* Fixed height as requested */
        display: flex;
    }

    /* 3-Pane Layout */
    .staff-sidebar {
        width: 280px;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    .participants-sidebar {
        width: 300px;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        background: #f8f9fa;
        display: none; /* Hidden by default until staff selected */
    }
    .participants-sidebar.active {
        display: flex;
    }

    .chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
        position: relative;
    }

    /* Headers */
    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
        background: #fff;
    }

    .sidebar-title {
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        font-size: 1.1rem;
    }

    .chat-header {
        padding: 15px 25px;
        border-bottom: 1px solid var(--border-color);
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
    }

    .chat-header-title {
        font-weight: 700;
        color: var(--text-primary);
        font-size: 1.1rem;
    }

    /* Lists */
    .chat-list {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
    }

    /* User Items */
    .user-item {
        padding: 12px 15px;
        border-radius: 12px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid transparent;
    }

    .user-item:hover {
        background: #fff5eb;
    }

    .user-item.active {
        background: #fff5eb;
        border-color: #ffe0b2;
        box-shadow: var(--shadow-sm);
    }

    .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #4a5568;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .user-item.active .user-avatar {
        background: var(--primary-gradient);
        color: white;
    }

    .user-info {
        flex: 1;
        min-width: 0;
    }

    .user-name {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-meta {
        font-size: 0.8rem;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Messages */
    .chat-messages-container {
        flex: 1;
        padding: 25px;
        overflow-y: auto;
        background-color: #ffffff;
        background-image: radial-gradient(#f1f5f9 1px, transparent 1px);
        background-size: 20px 20px;
    }

    .message {
        display: flex;
        margin-bottom: 20px;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .message.sent {
        justify-content: flex-end;
    }

    .message.received {
        justify-content: flex-start;
    }

    .message-bubble {
        max-width: 70%;
        padding: 12px 18px;
        position: relative;
        font-size: 0.95rem;
        line-height: 1.5;
        box-shadow: var(--shadow-sm);
    }

    .message.sent .message-bubble {
        background: var(--primary-gradient);
        color: white;
        border-radius: 18px 18px 4px 18px;
    }

    .message.received .message-bubble {
        background: white;
        color: var(--text-primary);
        border-radius: 18px 18px 18px 4px;
        border: 1px solid #edf2f7;
    }

    .message-time {
        font-size: 0.7rem;
        margin-top: 6px;
        opacity: 0.8;
        text-align: right;
    }

    .message.received .message-time {
        text-align: left;
        color: var(--text-secondary);
    }

    .badge-unread {
        background: #ff4757;
        color: white;
        font-size: 0.7rem;
        padding: 4px 8px;
        border-radius: 20px;
        box-shadow: 0 2px 5px rgba(255, 71, 87, 0.4);
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
        width: 6px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 3px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-sm-6">
                    <h1 class="m-0" style="font-weight: 700; color: #2d3748;">Staff Chats</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="chat-card">
                <!-- 1. Staff Sidebar -->
                <div class="staff-sidebar">
                    <div class="sidebar-header">
                        <h5 class="sidebar-title">Staff Members</h5>
                    </div>
                    <div class="chat-list">
                        @forelse($salesStaff as $staff)
                            <div class="user-item staff-item" data-staff-id="{{ $staff->id }}">
                                <div class="user-avatar">
                                    {{ strtoupper(substr($staff->f_name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="user-info">
                                    <div class="user-name">{{ $staff->f_name }} {{ $staff->l_name }}</div>
                                    <div class="user-meta">{{ $staff->email }}</div>
                                </div>
                                <i class="fas fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
                            </div>
                        @empty
                            <div class="text-center p-5 text-muted">
                                <i class="fas fa-users-slash fa-2x mb-3" style="opacity: 0.3;"></i>
                                <p>No staff found</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- 2. Participants Sidebar -->
                <div class="participants-sidebar" id="participantsSidebar">
                    <div class="sidebar-header bg-light">
                        <h5 class="sidebar-title" id="participantsHeader">Select Staff</h5>
                    </div>
                    <div id="participantsList" class="chat-list">
                        <div class="text-center p-5 text-muted">
                            <i class="fas fa-hand-pointer fa-2x mb-3" style="opacity: 0.3;"></i>
                            <p>Select a staff member to view their chats</p>
                        </div>
                    </div>
                </div>

                <!-- 3. Chat Area -->
                <div class="chat-area">
                    <div class="chat-header" id="chatHeader" style="display: none;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-avatar" style="width: 40px; height: 40px; font-size: 1rem;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="chat-header-title" id="chatHeaderTitle">Chat Details</div>
                                <div class="text-muted small" id="chatHeaderSubtitle">Select a conversation</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chat-messages-container" id="chatMessages">
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                            <div style="width: 80px; height: 80px; background: #f1f2f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                                <i class="fas fa-comments fa-2x" style="color: #cbd5e0;"></i>
                            </div>
                            <h5 style="color: #718096;">Welcome to Staff Chats</h5>
                            <p class="small">Select a staff member and participant to start viewing messages</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('footer-script')
<script>
    let currentStaffId = null;
    let currentParticipantId = null;

    // Store staff data (including participants)
    const staffData = @json($salesStaff);

    $(document).ready(function() {
        // Staff Selection
        $(document).on('click', '.staff-item', function() {
            const staffId = $(this).data('staff-id');
            selectStaff(staffId, $(this)[0]);
        });

        // Participant Selection
        $(document).on('click', '.participant-item', function() {
            const participantId = $(this).data('participant-id');
            loadChat(participantId, $(this)[0]);
        });
    });

    function selectStaff(staffId, element) {
        // UI Updates
        $('.staff-item').removeClass('active');
        if(element) $(element).addClass('active');
        
        currentStaffId = staffId;
        
        // Find staff data
        const staff = staffData.find(s => s.id == staffId);
        
        // Show participants sidebar
        $('#participantsSidebar').addClass('active');
        $('#participantsHeader').text(staff ? staff.f_name + "'s Chats" : 'Chats');
        
        // Build participants list
        let html = '';
        if (!staff || !staff.chatParticipants || staff.chatParticipants.length === 0) {
            html = `
                <div class="text-center p-5 text-muted">
                    <i class="fas fa-comment-slash fa-2x mb-3" style="opacity: 0.3;"></i>
                    <p>No active chats found</p>
                </div>
            `;
        } else {
            staff.chatParticipants.forEach(p => {
                const lastMsg = p.lastMessage ? 
                    (p.lastMessage.message ? 
                        (p.lastMessage.message.length > 30 ? p.lastMessage.message.substring(0, 30) + '...' : p.lastMessage.message) 
                        : 'Attachment') 
                    : '';
                
                const time = p.lastMessage ? new Date(p.lastMessage.created_at).toLocaleDateString('en-GB', {day: 'numeric', month: 'short'}) : '';
                const unreadBadge = p.unreadCount > 0 ? `<span class="badge-unread">${p.unreadCount}</span>` : '';
                
                html += `
                    <div class="user-item participant-item" data-participant-id="${p.id}">
                        <div class="user-avatar" style="width: 38px; height: 38px; font-size: 0.9rem;">
                            ${p.f_name.substring(0, 1).toUpperCase()}
                        </div>
                        <div class="user-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="user-name" style="font-size: 0.9rem;">${p.f_name} ${p.l_name || ''}</div>
                                ${unreadBadge}
                            </div>
                            <div class="user-meta mt-1">
                                ${lastMsg}
                                <span class="float-right" style="font-size: 0.7rem;">${time}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#participantsList').html(html);
        
        // Reset Chat Area
        $('#chatMessages').html(`
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                <div style="width: 80px; height: 80px; background: #f1f2f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <i class="fas fa-comments fa-2x" style="color: #cbd5e0;"></i>
                </div>
                <h5 style="color: #718096;">Select a Conversation</h5>
            </div>
        `);
        $('#chatHeader').hide();
    }

    function loadChat(participantId, element) {
        $('.participant-item').removeClass('active');
        if(element) $(element).addClass('active');
        
        currentParticipantId = participantId;
        
        // Update Header
        const participantName = $(element).find('.user-name').text();
        const staffName = $('.staff-item.active .user-name').text();
        
        $('#chatHeaderTitle').text(participantName);
        $('#chatHeaderSubtitle').text(`Chatting with ${staffName}`);
        $('#chatHeader').css('display', 'flex');
        
        // Loading State
        $('#chatMessages').html(`
            <div class="h-100 d-flex align-items-center justify-content-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `);
        
        // Fetch Messages
        // Route: admin.staff.chats.messages {salesId} {participantId}
        const url = `{{ route('admin.staff.chats.messages', [':salesId', ':participantId']) }}`
            .replace(':salesId', currentStaffId)
            .replace(':participantId', participantId);
            
        fetch(url)
            .then(response => response.json())
            .then(messages => {
                if (messages.length === 0) {
                    $('#chatMessages').html(`
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                            <i class="fas fa-comment-slash fa-2x mb-3" style="opacity: 0.3;"></i>
                            <p>No messages yet</p>
                        </div>
                    `);
                    return;
                }

                let html = '';
                messages.forEach(msg => {
                    // Logic for Sent/Received
                    // If sender_id == currentStaffId (Sales Staff), it is SENT by the staff (so it's "Sent" from staff perspective)
                    // But wait, we are the ADMIN viewing the chat.
                    // Usually, "Sent" means sent by the primary user we are inspecting (Staff).
                    // "Received" means sent by the Participant.
                    // Let's stick to: Staff = Sent (Right), Participant = Received (Left).
                    
                    const isSent = msg.sender_id == currentStaffId;
                    const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    let content = msg.message || '';
                    if (msg.attachment) {
                         const ext = msg.attachment.split('.').pop().toLowerCase();
                        const imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (imgExts.includes(ext)) {
                            content = `<img src="/storage/${msg.attachment}" style="max-width: 200px; max-height: 200px; border-radius: 12px;" alt="Image">`;
                        } else {
                            content = `<a href="/storage/${msg.attachment}" target="_blank" class="btn btn-sm btn-light"><i class="fas fa-download"></i> Download File</a>`;
                        }
                    }

                    html += `
                        <div class="message ${isSent ? 'sent' : 'received'}">
                            <div class="message-bubble">
                                ${content}
                                <div class="message-time">${time}</div>
                            </div>
                        </div>
                    `;
                });
                
                const chatContainer = document.getElementById('chatMessages');
                chatContainer.innerHTML = html;
                chatContainer.scrollTop = chatContainer.scrollHeight;
            })
            .catch(err => {
                console.error(err);
                $('#chatMessages').html(`
                    <div class="text-center text-danger mt-5">
                        <p>Error loading messages</p>
                    </div>
                `);
            });
    }
</script>
@endsection
