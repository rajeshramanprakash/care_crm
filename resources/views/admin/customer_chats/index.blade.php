@extends('admin.layouts.app')
@section('title', 'Customer Chats | Admin')

@section('header-css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #F7941D 0%, #e67e22 100%);
        --primary-color: #F7941D;
        --secondary-bg: #f8f9fa;
        --border-color: #edf2f7;
        --text-primary: #2d3748;
        --text-secondary: #718096;
    }

    .content-wrapper {
        background-color: #f4f6f9;
        min-height: 100vh;
    }

    /* Filter Section Styling */
    .filter-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid rgba(0,0,0,0.02);
    }

    .filter-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control-custom {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 15px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background-color: #f8fafc;
    }

    .form-control-custom:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(247, 148, 29, 0.1);
        background-color: #fff;
    }

    .btn-filter {
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(247, 148, 29, 0.3);
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(247, 148, 29, 0.4);
        color: white;
    }

    /* Chat Container Styling */
    .chat-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        background: #fff;
        overflow: hidden;
        height: 450px;
    }

    .chat-container {
        display: flex;
        height: 100%;
    }

    /* Sidebar Styling */
    .chat-sidebar {
        width: 320px;
        border-right: 1px solid var(--border-color);
        background: #fff;
        display: flex;
        flex-direction: column;
    }

    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
        background: #fff;
    }

    .sidebar-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .chat-list {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
    }

    .chat-user-item {
        padding: 12px 15px;
        border-radius: 12px;
        margin-bottom: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chat-user-item:hover {
        background-color: #fff9f2;
        border-color: #ffe0b2;
    }

    .chat-user-item.active {
        background: linear-gradient(to right, #fff9f2, #fff);
        border-left: 4px solid var(--primary-color);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #e0e0e0, #f5f5f5);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: var(--text-secondary);
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .chat-user-item.active .user-avatar {
        background: var(--primary-gradient);
        color: white;
        box-shadow: 0 4px 10px rgba(247, 148, 29, 0.3);
    }

    .user-info {
        flex: 1;
        min-width: 0;
    }

    .user-name {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.95rem;
        margin-bottom: 2px;
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

    /* Sub-list (Vendors/Freelancers) */
    .chat-participants {
        width: 300px;
        border-right: 1px solid var(--border-color);
        background: #fcfcfc;
        display: none;
        flex-direction: column;
    }

    .chat-participants.active {
        display: flex;
    }

    .participant-item {
        padding: 12px 15px;
        border-bottom: 1px solid var(--border-color);
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fff;
    }

    .participant-item:hover {
        background-color: #f8f9fa;
    }

    .participant-item.active {
        background-color: #fff3e0;
    }

    /* Message Area Styling */
    .chat-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
        position: relative;
    }

    .chat-area::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: radial-gradient(#F7941D 0.5px, transparent 0.5px), radial-gradient(#F7941D 0.5px, #fff 0.5px);
        background-size: 20px 20px;
        background-position: 0 0, 10px 10px;
        opacity: 0.03;
        pointer-events: none;
    }

    .chat-header {
        padding: 15px 25px;
        background: #fff;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        z-index: 10;
    }

    .chat-header-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .chat-messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 25px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    /* Message Bubbles */
    .message {
        display: flex;
        margin-bottom: 5px;
        max-width: 75%;
        position: relative;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .message.sent {
        align-self: flex-end;
    }

    .message.received {
        align-self: flex-start;
    }

    .message-bubble {
        padding: 12px 18px;
        position: relative;
        font-size: 0.95rem;
        line-height: 1.5;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .message.sent .message-bubble {
        background: var(--primary-gradient);
        color: white;
        border-radius: 18px 18px 4px 18px;
    }

    .message.received .message-bubble {
        background: #f1f2f6;
        color: var(--text-primary);
        border-radius: 18px 18px 18px 4px;
    }

    .message-time {
        font-size: 0.7rem;
        margin-top: 4px;
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
                    <h1 class="m-0" style="font-weight: 700; color: #2d3748;">Customer Chats</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Modern Filter Section -->
            <div class="filter-card">
                <form method="GET" action="{{ route('admin.customer_chats.index') }}">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="filter-label">Customer</label>
                            <select name="customer_id" class="form-control form-control-custom">
                                <option value="">All Customers</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->customer_name }} ({{ $customer->contact_no }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="filter-label">Vendor</label>
                            <select name="vendor_id" class="form-control form-control-custom">
                                <option value="">All Vendors</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ $vendorId == $vendor->id ? 'selected' : '' }}>
                                        {{ $vendor->name }} ({{ $vendor->contact_no }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <label class="filter-label">Freelancer</label>
                            <select name="freelancer_id" class="form-control form-control-custom">
                                <option value="">All Freelancers</option>
                                @foreach($freelancers as $freelancer)
                                    <option value="{{ $freelancer->id }}" {{ $freelancerId == $freelancer->id ? 'selected' : '' }}>
                                        {{ $freelancer->name }} ({{ $freelancer->contact_no }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3 mb-md-0">
                            <label class="filter-label">Search</label>
                            <input type="text" name="search" class="form-control form-control-custom" placeholder="Search messages..." value="{{ $search }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-filter">
                                <i class="fas fa-filter mr-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modern Chat Interface -->
            <div class="chat-card">
                <div class="chat-container">
                    <!-- Customer List Sidebar -->
                    <div class="chat-sidebar">
                        <div class="sidebar-header">
                            <h5 class="sidebar-title">Customers</h5>
                        </div>
                        <div class="chat-list">
                            @php
                                $uniqueCustomers = collect($chatPairs)->map(function($pair) {
                                    return $pair['customer'];
                                })->unique('id')->values();
                            @endphp
                            @forelse($uniqueCustomers as $customer)
                                <div class="chat-user-item" data-customer-id="{{ $customer->id }}">
                                    <div class="user-avatar">
                                        {{ strtoupper(substr($customer->customer_name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div class="user-info">
                                        <div class="user-name">{{ $customer->customer_name ?? 'Unknown' }}</div>
                                        <div class="user-meta">{{ $customer->contact_no ?? '' }}</div>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
                                </div>
                            @empty
                                <div class="text-center p-5 text-muted">
                                    <i class="fas fa-users-slash fa-2x mb-3" style="opacity: 0.3;"></i>
                                    <p>No customers found</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Participants Sidebar (Vendors/Freelancers) -->
                    <div class="chat-participants" id="chatParticipants">
                        <div class="sidebar-header bg-light">
                            <h5 class="sidebar-title" id="participantsHeader" style="font-size: 1rem;">Select Customer</h5>
                        </div>
                        <div id="participantsList" class="chat-list" style="background: #f8f9fa;">
                            <div class="text-center p-5 text-muted">
                                <i class="fas fa-hand-pointer fa-2x mb-3" style="opacity: 0.3;"></i>
                                <p>Select a customer to view chats</p>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Message Area -->
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
                                <h5 style="color: #718096;">Welcome to Customer Chats</h5>
                                <p class="small">Select a customer and participant to start viewing messages</p>
                            </div>
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
    let currentCustomerId = null;
    let currentVendorId = null;
    let currentFreelancerId = null;

    // Store chat pairs data
    const chatPairsData = @json($chatPairs);

    // Use event delegation for customer selection
    $(document).ready(function() {
        $(document).on('click', '.chat-user-item', function() {
            const customerId = $(this).data('customer-id');
            selectCustomer(customerId, $(this)[0]);
        });

        // Use event delegation for vendor/freelancer selection
        $(document).on('click', '.participant-item', function() {
            const customerId = $(this).data('customer-id');
            const vendorId = $(this).data('vendor-id') || null;
            const freelancerId = $(this).data('freelancer-id') || null;
            loadChat(customerId, vendorId, freelancerId, $(this)[0]);
        });
    });

    function selectCustomer(customerId, element) {
        // Remove active class from all customer items
        $('.chat-user-item').removeClass('active');
        
        // Add active class to clicked customer
        if (element) {
            $(element).addClass('active');
        }

        // Find all vendors/freelancers for this customer
        const customerPairs = chatPairsData.filter(pair => pair.customer.id == customerId);
        
        // Show participants panel
        $('#chatParticipants').addClass('active');
        
        // Update header
        const customer = customerPairs.length > 0 ? customerPairs[0].customer : null;
        $('#participantsHeader').text(customer ? customer.customer_name : 'Select Customer');
        
        // Build participants list
        let participantsHtml = '';
        if (customerPairs.length === 0) {
            participantsHtml = `
                <div class="text-center p-5 text-muted">
                    <i class="fas fa-comments fa-2x mb-3" style="opacity: 0.3;"></i>
                    <p>No chats found</p>
                </div>
            `;
        } else {
            customerPairs.forEach(pair => {
                const participant = pair.vendor || pair.freelancer;
                const isVendor = !!pair.vendor;
                const unreadBadge = pair.unread_count > 0 ? `<span class="badge-unread">${pair.unread_count}</span>` : '';
                const lastMessage = pair.last_message ? 
                    `<div class="user-meta mt-1">
                        ${pair.last_message.message ? (pair.last_message.message.length > 30 ? pair.last_message.message.substring(0, 30) + '...' : pair.last_message.message) : 'Attachment'}
                        <span class="float-right" style="font-size: 0.7rem;">${new Date(pair.last_message.created_at).toLocaleDateString('en-GB', {day: 'numeric', month: 'short'})}</span>
                    </div>` : '';
                
                const initials = participant ? participant.name.substring(0, 1).toUpperCase() : 'U';
                
                participantsHtml += `
                    <div class="participant-item" 
                         data-customer-id="${customerId}"
                         data-vendor-id="${pair.vendor ? pair.vendor.id : ''}"
                         data-freelancer-id="${pair.freelancer ? pair.freelancer.id : ''}">
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-avatar" style="width: 40px; height: 40px; font-size: 0.9rem;">
                                ${initials}
                            </div>
                            <div class="user-info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="user-name" style="font-size: 0.9rem;">
                                        ${participant ? participant.name : 'N/A'}
                                        <span class="badge badge-light ml-1" style="font-size: 0.6rem;">${isVendor ? 'Vendor' : 'Freelancer'}</span>
                                    </div>
                                    ${unreadBadge}
                                </div>
                                ${lastMessage}
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#participantsList').html(participantsHtml);
        
        // Clear chat messages
        $('#chatMessages').html(`
            <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                <div style="width: 80px; height: 80px; background: #f1f2f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <i class="fas fa-comments fa-2x" style="color: #cbd5e0;"></i>
                </div>
                <h5 style="color: #718096;">Select a Participant</h5>
                <p class="small">Choose a vendor or freelancer to view the conversation</p>
            </div>
        `);
        $('#chatHeader').hide();
    }

    function loadChat(customerId, vendorId, freelancerId, element) {
        // Remove active class from all items
        document.querySelectorAll('.participant-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Add active class to clicked item
        if (element) {
            element.classList.add('active');
        }

        currentCustomerId = customerId;
        currentVendorId = vendorId;
        currentFreelancerId = freelancerId;

        // Update header
        const header = document.getElementById('chatHeader');
        const headerTitle = document.getElementById('chatHeaderTitle');
        const headerSubtitle = document.getElementById('chatHeaderSubtitle');
        
        const participantName = element ? element.querySelector('.user-name').childNodes[0].textContent.trim() : 'Chat';
        const customerName = document.querySelector('.chat-user-item.active .user-name').textContent.trim();
        
        headerTitle.textContent = participantName;
        headerSubtitle.textContent = `Chat with ${customerName}`;
        header.style.display = 'flex';

        // Load messages
        const params = new URLSearchParams({
            customer_id: customerId
        });
        
        if (vendorId) {
            params.append('vendor_id', vendorId);
        }
        if (freelancerId) {
            params.append('freelancer_id', freelancerId);
        }

        // Show loading state
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.innerHTML = `
            <div class="h-100 d-flex align-items-center justify-content-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `;

        fetch(`{{ route('admin.customer_chats.messages') }}?${params}`)
            .then(response => response.json())
            .then(messages => {
                if (messages.length === 0) {
                    chatMessages.innerHTML = `
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                            <i class="fas fa-comment-slash fa-2x mb-3" style="opacity: 0.3;"></i>
                            <p>No messages yet</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                messages.forEach(message => {
                    const isSent = message.sender_type === 'customer'; // Assuming customer is 'sent' from admin perspective? 
                    // Wait, usually in admin panel:
                    // If sender_type is 'customer', it's a message FROM customer (Received)
                    // If sender_type is 'vendor'/'freelancer', it's FROM them (Received)
                    // But this is a chat between Customer AND Vendor/Freelancer.
                    // Admin is viewing it.
                    // So 'Sent' vs 'Received' depends on who we consider the "primary" user.
                    // Let's stick to the previous logic: 
                    // Previous logic: const isSent = message.sender_type === 'customer';
                    // If previous logic worked, I'll keep it. 
                    // Actually, let's look at the previous code:
                    // const isSent = message.sender_type === 'customer';
                    // .message.sent { justify-content: flex-end; } -> Blue/Orange
                    // So Customer messages are on the RIGHT (Sent).
                    // Vendor/Freelancer messages are on the LEFT (Received).
                    
                    const messageDate = new Date(message.created_at);
                    const timeStr = messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    let messageContent = '';
                    if (message.attachment) {
                        const ext = message.attachment.split('.').pop().toLowerCase();
                        const imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (imgExts.includes(ext)) {
                            messageContent = `<img src="/storage/${message.attachment}" style="max-width: 200px; max-height: 200px; border-radius: 12px;" alt="Image">`;
                        } else {
                            messageContent = `<a href="/storage/${message.attachment}" target="_blank" class="btn btn-sm btn-light"><i class="fas fa-download"></i> Download File</a>`;
                        }
                    } else {
                        messageContent = message.message || '';
                    }

                    html += `
                        <div class="message ${isSent ? 'sent' : 'received'}">
                            <div class="message-bubble">
                                ${messageContent}
                                <div class="message-time">${timeStr}</div>
                            </div>
                        </div>
                    `;
                });

                chatMessages.innerHTML = html;
                chatMessages.scrollTop = chatMessages.scrollHeight;
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                chatMessages.innerHTML = `
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-danger">
                        <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                        <p>Error loading messages</p>
                    </div>
                `;
            });
    }
</script>
@endsection

