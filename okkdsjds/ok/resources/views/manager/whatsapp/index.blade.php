@extends('manager.layouts.app')

@section('title', 'All WhatsApp Chats')

@section('content')
<div class="content-wrapper" style="overflow: hidden !important">
    <div class="container-fluid" style="height: 100vh;position: fixed;max-width: -webkit-fill-available; ">
        <div class="chat-card row w-100 h-100 gx-0">
            <!-- Sidebar: User List -->
            <div class="col-md-4 col-lg-4 chat-sidebar p-0 d-flex flex-column">
                <div class="chat-sidebar-header d-flex align-items-center justify-content-between px-3 py-3 bg-white">
                    <h5 class="mb-0" style="font-size:14px; font-weight:600;">WhatsApp Chats</h5>
                    <input type="text" class="form-control" id="searchUser" placeholder="Search..." style="margin-left:10px; font-size:12px;">
                </div>
                <div class="chat-user-list flex-grow-1" id="usersList">
                    {{-- User/Number list will be populated here --}}
                </div>
            </div>
            <!-- Main Chat Area -->
            <div class="col-md-8 col-lg-8 chat-main p-0 d-flex flex-column">
                <div id="chat-header-panel" class="chat-header d-flex align-items-center px-3 py-2 bg-white" style="display: none;">
                    <div class="position-relative me-3">
                        <img src="" id="chat-avatar" class="rounded-circle" style="width: 44px; height: 44px; margin-right:10px;">
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold mb-0" id="chatHeader"></div>
                    </div>
                    <div class="ms-auto">
                        <button id="callBtn" class="btn btn-outline-success" style="border-radius:50%;padding:6px 10px;" title="Call this number">
                            <i class="fas fa-phone"></i>
                        </button>
                    </div>
                </div>
                <div class="chat-messages flex-grow-1 p-4" id="chatMessages">
                    <div class="chat-welcome text-center m-auto">
                        <i class="fab fa-whatsapp fa-3x text-muted mb-3"></i>
                        <h4>Welcome to All WhatsApp Chats</h4>
                        <p class="text-muted">Select a conversation to view messages</p>
                    </div>
                </div>
                <div id="message-form-container" class="chat-input p-3" style="display: none;">
                    <form id="messageForm" class="d-flex align-items-center">
                        <input type="hidden" id="receiverId" name="receiver_id">
                        <input type="text" class="form-control me-2" id="messageInput" placeholder="Type your message..." autocomplete="off">
                        <button type="submit" class="btn btn-success rounded-circle">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" tabindex="-1" id="addExecModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Executive to Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <select class="form-control" id="allExecDropdown"></select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="submitAddExec">Add</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('header-css')
<style>


.content-wrapper {
        height: 100vh;
        background: #f8f9fc !important;
        padding: 0 !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    .container-fluid{
        /* padding: 20px; */
        /* border-radius: 20px; */
    }
    .chat-card {
        background: #fff;
        border-radius: 20px;
    }

    /* Sidebar */
    .chat-sidebar {
        background: #fff;
        border-right: 1px solid #e9eaeb;
    }
    .chat-sidebar-header {
        border-bottom: 1px solid #e9eaeb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        z-index: 10;
    }
    #searchUser {
        border-radius: 20px;
        background-color: #f0f2f5;
        border: none;
    }
    .chat-user-list {
        overflow-y: auto;
    }
    .chat-user-item {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid #f0f2f5;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .chat-user-item:hover { background-color: #f7f7f7; }
    .chat-user-item.active { background-color: #e9f5ff; }

    /* Main Chat Area */
    .chat-main {
        height: 83%;
        background-color: #e5ddd5;
        background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
    }
    .chat-header {
        border-bottom: 1px solid #e9eaeb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        z-index: 10;
    }
    .chat-messages {
        height: 100%; /* Will be controlled by flex-grow */
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    .chat-input {
        box-shadow: 0 -1px 5px rgba(0,0,0,0.05);
        z-index: 10;
    }
    #messageInput {
        border-radius: 20px;
        border: 1px solid #ccc;
    }

    /* Messages */
    .message {
        display: flex;
        flex-direction: column;
        margin-bottom: 0.75rem;
        max-width: 65%;
        position: relative;
    }
    .message.sent {
        align-self: flex-end;
        align-items: flex-end;
    }
    .message.received {
        align-self: flex-start;
        align-items: flex-start;
    }
    .message-content {
        padding: 9px 12px;
        border-radius: 12px;
        box-shadow: 0 1px 1px rgba(0,0,0,0.05);
        word-wrap: break-word;
    }
    .message.sent .message-content { background-color: #dcf8c6; font-size: 13px; }
    .message.received .message-content { background-color: #fff; font-size: 13px; }
    .message-time {
        font-size: 0.75em;
        color: #8c8c8c;
        margin-top: 2px;
    }
    .message.sent .message-time {
        text-align: right;
        margin-right: 5px;
    }
    .message.received .message-time {
        text-align: left;
        margin-left: 5px;
    }
</style>
@endsection

@section('footer-script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const usersList = document.getElementById('usersList');
    const chatMessages = document.getElementById('chatMessages');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const receiverIdInput = document.getElementById('receiverId');
    const searchInput = document.getElementById('searchUser');
    const welcomePanel = document.querySelector('.chat-welcome');
    const chatHeaderPanel = document.getElementById('chat-header-panel');
    const messageFormContainer = document.getElementById('message-form-container');

    let allNumbers = [];
    let currentNumber = null;
    let pollingInterval = null;
    let isFirstLoad = true;
    let currentPage = 1;
    let hasMore = true;
    const perPage = 20;

    function fetchNumbers() {
        fetch('{{ route("admin.all_whatsapp_chats.numbers") }}')
            .then(response => response.json())
            .then(data => {
                allNumbers = data;
                renderNumbers(allNumbers);
            });
    }

    function renderNumbers(numbers) {
        usersList.innerHTML = '';
        numbers.forEach(num => {
            const userElement = `
                <div class="chat-user-item d-flex align-items-center" data-number="${num.msg_from}">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-1 user-name" style="font-size:14px;">${num.msg_from}</h6>
                            <div>
                                <small class="text-muted">${moment(num.time).fromNow()}</small>
                                ${num.unread_count > 0 ? `<span class=\"badge bg-danger ms-2\">${num.unread_count}</span>` : ''}
                            </div>
                        </div>
                        <p class="mb-0 text-muted small text-truncate">${num.body || '<em>No preview available</em>'}</p>
                    </div>
                </div>
            `;
            usersList.insertAdjacentHTML('beforeend', userElement);
        });
    }

    function renderLoadMoreButton() {
        let btn = document.getElementById('loadMoreBtn');
        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'loadMoreBtn';
            btn.className = 'btn btn-light w-100 mb-2';
            btn.textContent = 'Load More';
            btn.onclick = function() {
                if (hasMore) loadMessages(currentNumber, currentPage + 1, true);
            };
            chatMessages.prepend(btn);
        }
        btn.style.display = hasMore ? 'block' : 'none';
    }

    function removeLoadMoreButton() {
        let btn = document.getElementById('loadMoreBtn');
        if (btn) btn.remove();
    }

    function loadMessages(number, page = 1, prepend = false) {
        // Mark as read
        fetch(`/admin/all-whatsapp-chats/mark-read/${number}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        if (isFirstLoad) {
            chatMessages.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        }
        fetch(`{{ url('admin/all-whatsapp-chats/messages') }}/${number}?page=${page}&per_page=${perPage}&_=${Date.now()}`)
            .then(response => response.json())
            .then(data => {
                let messages = data.messages || [];
                hasMore = data.has_more;
                currentPage = data.page;
                if (isFirstLoad) {
                    chatMessages.innerHTML = '';
                }
                if (prepend) {
                    // Save current scroll position
                    const oldScrollHeight = chatMessages.scrollHeight;
                    const oldScrollTop = chatMessages.scrollTop;
                    // Prepend messages
                    let tempDiv = document.createElement('div');
                    messages.forEach(message => tempDiv.innerHTML += getMessageHtml(message));
                    if (chatMessages.firstChild && chatMessages.firstChild.id === 'loadMoreBtn') {
                        chatMessages.insertBefore(tempDiv, chatMessages.firstChild.nextSibling);
                    } else {
                        chatMessages.prepend(tempDiv);
                    }
                    // Restore scroll position
                    chatMessages.scrollTop = chatMessages.scrollHeight - oldScrollHeight + oldScrollTop;
                } else {
                    removeLoadMoreButton();
                    messages.forEach(message => appendMessage(message));
                }
                if (hasMore) {
                    renderLoadMoreButton();
                } else {
                    removeLoadMoreButton();
                }
                isFirstLoad = false;
            });
    }

    function getMessageHtml(message) {
        // This function should return the HTML string for a message (same as appendMessage, but as a string)
        const isSent = message.is_sent;
        let docHtml = '';
        if (message.doc) {
            if (message.type === 'image') {
                docHtml = `<div class="mt-2"><img src="${message.doc}" alt="image" style="max-width: 220px; max-height: 220px; border-radius: 8px;"/></div>`;
            } else if (message.type === 'audio') {
                docHtml = `<div class="mt-2"><audio controls src="${message.doc}">Your browser does not support the audio element.</audio></div>`;
            } else if (message.type === 'video') {
                docHtml = `<div class="mt-2"><video controls style="max-width: 220px; max-height: 220px; border-radius: 8px;"><source src="${message.doc}" type="video/mp4">Your browser does not support the video tag.</video></div>`;
            } else if (message.type === 'location') {
                docHtml = `<div class="mt-2"><a href="${message.doc}" target="_blank">View Location</a></div>`;
            } else if (message.type === 'contact' || message.type === 'vcard') {
                try {
                    const contactInfo = JSON.parse(message.doc);
                    docHtml = `<div class="mt-2">Name: ${contactInfo.name}, Mobile: ${contactInfo.mobile}</div>`;
                } catch (e) {
                    docHtml = `<div class="mt-2">Invalid contact info</div>`;
                }
            } else {
                const imgExt = ["jpg", "jpeg", "png", "gif", "webp"];
                const url = message.doc;
                const ext = url.split('.').pop().split('?')[0].toLowerCase();
                if (imgExt.includes(ext)) {
                    docHtml = `<div class="mt-2"><img src="${url}" alt="image" style="max-width: 220px; max-height: 220px; border-radius: 8px;"/></div>`;
                } else {
                    docHtml = `<div class="mt-2"><a href="${url}" target="_blank">View File</a></div>`;
                }
            }
        }
        let statusHtml = '';
        if (isSent) {
            let statusIcon = '';
            let statusText = '';
            let statusColor = '';
            switch(message.status) {
                case 'sent': statusIcon = '✓'; statusText = 'Sent'; statusColor = '#8e8e93'; break;
                case 'delivered': statusIcon = '✓✓'; statusText = 'Delivered'; statusColor = '#8e8e93'; break;
                case 'read': statusIcon = '✓✓'; statusText = 'Read'; statusColor = '#34b7f1'; break;
                case 'failed': statusIcon = '✗'; statusText = 'Failed'; statusColor = '#ff3b30'; break;
                default: statusIcon = '⏳'; statusText = 'Sending...'; statusColor = '#8e8e93';
            }
            statusHtml = `<div class="message-status" style="font-size: 11px; color: ${statusColor}; margin-top: 2px;"><span style="font-weight: bold;">${statusIcon}</span> ${statusText}</div>`;
        }
        return `<div class="message ${isSent ? 'sent' : 'received'}"><div class="message-content"><div style="font-size:13px;">${message.body || ''}</div>${docHtml}<div class="message-time text-end mt-1">${moment(message.time).format('LT')}</div>${statusHtml}</div></div>`;
    }

    usersList.addEventListener('click', function(e) {
        const target = e.target.closest('.chat-user-item');
        if (!target) return;

        currentNumber = target.dataset.number;
        isFirstLoad = true;
        currentPage = 1;
        hasMore = true;

        document.querySelectorAll('.chat-user-item').forEach(item => item.classList.remove('active'));
        target.classList.add('active');

        // Remove unread badge immediately for this number
        const badge = target.querySelector('.badge.bg-danger');
        if (badge) badge.remove();

        document.getElementById('chatHeader').textContent = currentNumber;
        document.getElementById('chat-avatar').src = `https://ui-avatars.com/api/?name=${currentNumber.charAt(0)}&background=25d366&color=fff`;

        // Fetch and show executives for this number
        fetch(`/admin/all-whatsapp-chats/executives/${currentNumber}`)
            .then(response => response.json())
            .then(executives => {
                let execDiv = document.getElementById('executiveNames');
                if (!execDiv) {
                    execDiv = document.createElement('div');
                    execDiv.id = 'executiveNames';
                    execDiv.style.fontSize = '12px';
                    execDiv.style.color = '#888';
                    document.getElementById('chatHeader').after(execDiv);
                }
                let execNames = executives.map(e =>
                    `<span class="exec-pill" style="display:inline-block;background:#e9f5ff;color:#333;padding:2px 8px;border-radius:12px;margin-right:4px;margin-bottom:2px;">
                        ${e.name} <button class="remove-exec-btn" data-exec-id="${e.id}" style="border:none;background:transparent;color:#d9534f;font-size:13px;outline:none;cursor:pointer;" title="Remove">&times;</button>
                    </span>`
                ).join(' ');
                execDiv.innerHTML = execNames + ` <button id="addExecBtn" style="margin-left:8px; font-size:14px; padding:2px 8px; border-radius:50%; border:none; background:#25d366; color:#fff; cursor:pointer;">+</button>`;

                // Add click event for plus button
                document.getElementById('addExecBtn').onclick = function() {
                    showAddExecutiveModal(currentNumber);
                };

                // Add click event for remove buttons
                execDiv.querySelectorAll('.remove-exec-btn').forEach(btn => {
                    btn.onclick = function(e) {
                        e.stopPropagation();
                        const execId = this.getAttribute('data-exec-id');
                        removeExecutiveFromGroup(currentNumber, execId);
                    };
                });
            });

        // Function to show modal and populate dropdown
        window.showAddExecutiveModal = function(number) {
            // Fetch all users
            fetch('/api/all-users')
                .then(res => res.json())
                .then(users => {
                    const dropdown = document.getElementById('allExecDropdown');
                    dropdown.innerHTML = '';
                    users.forEach(u => {
                        dropdown.innerHTML += `<option value="${u.id}">${u.f_name} ${u.l_name ?? ''}</option>`;
                    });
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('addExecModal'));
                    modal.show();
                    document.getElementById('submitAddExec').onclick = function() {
                        const execId = dropdown.value;
                        fetch('/whatsapp-group/add-executive', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ whatsapp_number: number, executive_id: execId })
                        })
                        .then(res => res.json())
                        .then(() => {
                            modal.hide();
                            // Refresh executive list
                            fetch(`/admin/all-whatsapp-chats/executives/${number}`)
                                .then(response => response.json())
                                .then(executives => {
                                    let execDiv = document.getElementById('executiveNames');
                                    let execNames = executives.map(e =>
                                        `<span class="exec-pill" style="display:inline-block;background:#e9f5ff;color:#333;padding:2px 8px;border-radius:12px;margin-right:4px;margin-bottom:2px;">${e.name} <button class="remove-exec-btn" data-exec-id="${e.id}" style="border:none;background:transparent;color:#d9534f;font-size:13px;outline:none;cursor:pointer;" title="Remove">&times;</button></span>`
                                    ).join(' ');
                                    execDiv.innerHTML = execNames + ` <button id="addExecBtn" style="margin-left:8px; font-size:14px; padding:2px 8px; border-radius:50%; border:none; background:#25d366; color:#fff; cursor:pointer;">+</button>`;
                                    document.getElementById('addExecBtn').onclick = function() {
                                        showAddExecutiveModal(number);
                                    };
                                    execDiv.querySelectorAll('.remove-exec-btn').forEach(btn => {
                                        btn.onclick = function(e) {
                                            e.stopPropagation();
                                            const execId = this.getAttribute('data-exec-id');
                                            removeExecutiveFromGroup(number, execId);
                                        };
                                    });
                                });
                        });
                    };
                });
        };

        welcomePanel.style.display = 'none';
        chatHeaderPanel.style.display = 'flex';
        messageFormContainer.style.display = 'block';
        receiverIdInput.value = currentNumber;

        loadMessages(currentNumber);
    });

    // Clear previous polling
    if (pollingInterval) clearInterval(pollingInterval);

    // Start polling for the selected chat
    pollingInterval = setInterval(() => {
        if (currentNumber) loadMessages(currentNumber);
    }, 3000);

    window.addEventListener('beforeunload', function() {
        if (pollingInterval) clearInterval(pollingInterval);
    });

    function isUserAtBottom(element, threshold = 50) {
        return element.scrollHeight - element.scrollTop - element.clientHeight < threshold;
    }

    function appendMessage(message) {
        const isSent = message.is_sent;
        let docHtml = '';

        // Prefer message.type if available
        if (message.doc) {
            if (message.type === 'image') {
                docHtml = `<div class="mt-2"><img src="${message.doc}" alt="image" style="max-width: 220px; max-height: 220px; border-radius: 8px;"/></div>`;
            } else if (message.type === 'audio') {
                docHtml = `<div class="mt-2"><audio controls src="${message.doc}">Your browser does not support the audio element.</audio></div>`;
            } else if (message.type === 'video') {
                docHtml = `<div class="mt-2"><video controls style="max-width: 220px; max-height: 220px; border-radius: 8px;"><source src="${message.doc}" type="video/mp4">Your browser does not support the video tag.</video></div>`;
            } else if (message.type === 'location') {
                docHtml = `<div class="mt-2"><a href="${message.doc}" target="_blank">View Location</a></div>`;
            } else if (message.type === 'contact' || message.type === 'vcard') {
                try {
                    const contactInfo = JSON.parse(message.doc);
                    docHtml = `<div class="mt-2">Name: ${contactInfo.name}, Mobile: ${contactInfo.mobile}</div>`;
                } catch (e) {
                    docHtml = `<div class="mt-2">Invalid contact info</div>`;
                }
            } else {
                // fallback: check extension for image
                const imgExt = ["jpg", "jpeg", "png", "gif", "webp"];
                const url = message.doc;
                const ext = url.split('.').pop().split('?')[0].toLowerCase();
                if (imgExt.includes(ext)) {
                    docHtml = `<div class="mt-2"><img src="${url}" alt="image" style="max-width: 220px; max-height: 220px; border-radius: 8px;"/></div>`;
                } else {
                    docHtml = `<div class="mt-2"><a href="${url}" target="_blank">View File</a></div>`;
                }
            }
        }

        // Status display logic
        let statusHtml = '';
        if (isSent) {
            let statusIcon = '';
            let statusText = '';
            let statusColor = '';

            switch(message.status) {
                case 'sent':
                    statusIcon = '✓';
                    statusText = 'Sent';
                    statusColor = '#8e8e93';
                    break;
                case 'delivered':
                    statusIcon = '✓✓';
                    statusText = 'Delivered';
                    statusColor = '#8e8e93';
                    break;
                case 'read':
                    statusIcon = '✓✓';
                    statusText = 'Read';
                    statusColor = '#34b7f1';
                    break;
                case 'failed':
                    statusIcon = '✗';
                    statusText = 'Failed';
                    statusColor = '#ff3b30';
                    break;
                default:
                    statusIcon = '⏳';
                    statusText = 'Sending...';
                    statusColor = '#8e8e93';
            }

            statusHtml = `<div class="message-status" style="font-size: 11px; color: ${statusColor}; margin-top: 2px;">
                <span style="font-weight: bold;">${statusIcon}</span> ${statusText}
            </div>`;
        }

        const messageHtml = `
            <div class="message ${isSent ? 'sent' : 'received'}">
                <div class="message-content">
                    <div style="font-size:13px;">${message.body || ''}</div>
                    ${docHtml}
                    <div class="message-time text-end mt-1">${moment(message.time).format('LT')}</div>
                    ${statusHtml}
                </div>
            </div>
        `;
        const shouldScroll = isUserAtBottom(chatMessages);
        chatMessages.insertAdjacentHTML('beforeend', messageHtml);
        if (shouldScroll) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = messageInput.value;
        if (!message.trim() || !currentNumber) return;

        const originalButtonHtml = this.querySelector('button').innerHTML;
        this.querySelector('button').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        this.querySelector('button').disabled = true;

        fetch('{{ route("whatsapp_chat.send") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ recipient: currentNumber, message: message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.message_id) {
                messageInput.value = '';
                const sentMessage = {
                    body: message,
                    is_sent: true,
                    time: new Date().toISOString()
                };
                appendMessage(sentMessage);
            } else {
                alert('Failed to send message: ' + (data.details?.error?.message || 'Unknown error'));
            }
        })
        .catch(() => alert('An error occurred.'))
        .finally(() => {
            this.querySelector('button').innerHTML = originalButtonHtml;
            this.querySelector('button').disabled = false;
        });
    });

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const filteredNumbers = allNumbers.filter(num => num.msg_from.toLowerCase().includes(searchTerm));
        renderNumbers(filteredNumbers);
    });

    document.getElementById('callBtn').onclick = function() {
        handleCall(currentNumber);
    };

    window.handleCall = function(number) {
        if (!number) return;
        if (!confirm('Are you sure you want to make this call?')) return;
        var $btn = $('#callBtn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.ajax({
            url: `/call-outbound/${number}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    toastr.success('Call initiated successfully');
                } else {
                    toastr.error(response.message || 'Failed to initiate call');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error initiating call';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-phone"></i>');
            }
        });
    };

    window.removeExecutiveFromGroup = function(number, execId) {
        if (!number || !execId) return;
        if (!confirm('Are you sure you want to remove this executive from the chat?')) return;
        $.ajax({
            url: '/whatsapp-group/remove-executive',
            type: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: JSON.stringify({ whatsapp_number: number, executive_id: execId }),
            success: function(response) {
                toastr.success('Executive removed successfully');
                // Refresh executive list
                fetch(`/admin/all-whatsapp-chats/executives/${number}`)
                    .then(response => response.json())
                    .then(executives => {
                        let execDiv = document.getElementById('executiveNames');
                        let execNames = executives.map(e =>
                            `<span class="exec-pill" style="display:inline-block;background:#e9f5ff;color:#333;padding:2px 8px;border-radius:12px;margin-right:4px;margin-bottom:2px;">${e.name} <button class="remove-exec-btn" data-exec-id="${e.id}" style="border:none;background:transparent;color:#d9534f;font-size:13px;outline:none;cursor:pointer;" title="Remove">&times;</button></span>`
                        ).join(' ');
                        execDiv.innerHTML = execNames + ` <button id="addExecBtn" style="margin-left:8px; font-size:14px; padding:2px 8px; border-radius:50%; border:none; background:#25d366; color:#fff; cursor:pointer;">+</button>`;
                        document.getElementById('addExecBtn').onclick = function() {
                            showAddExecutiveModal(number);
                        };
                        execDiv.querySelectorAll('.remove-exec-btn').forEach(btn => {
                            btn.onclick = function(e) {
                                e.stopPropagation();
                                const execId = this.getAttribute('data-exec-id');
                                removeExecutiveFromGroup(number, execId);
                            };
                        });
                    });
            },
            error: function(xhr) {
                let errorMessage = 'Error removing executive';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            }
        });
    };

    fetchNumbers(); // Initial fetch
});
</script>
@endsection
