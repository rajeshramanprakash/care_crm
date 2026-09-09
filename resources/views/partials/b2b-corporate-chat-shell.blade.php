@php
    $chatMode = $chatMode ?? 'corporate';
    $chatLayout = $chatLayout ?? 'group';
    $contacts = $contacts ?? collect();
    $groupMembers = $groupMembers ?? [];
    $currentStaffUserId = $currentStaffUserId ?? null;
    $themePrimary = $themePrimary ?? '#F7941D';
    $themePrimaryDark = $themePrimaryDark ?? '#cf6413';
    $routes = $routes ?? [];
    $sidebarTitle = $sidebarTitle ?? 'Chat';
    $sidebarHint = $sidebarHint ?? '';
    $groupName = $groupName ?? null;
@endphp
<div class="bcc-chat-page" data-chat-mode="{{ $chatMode }}" data-chat-layout="{{ $chatLayout }}">
    <div class="chat-card bcc-chat-card">
        <div class="bcc-sidebar">
            <div class="bcc-sidebar-header">
                <h5 class="mb-1">{{ $sidebarTitle }}</h5>
                @if($groupName)
                    <span class="bcc-group-badge"><i class="fas fa-users mr-1"></i>{{ $groupName }}</span>
                @endif
                @if($sidebarHint)
                    <p class="text-muted small mb-2">{{ $sidebarHint }}</p>
                @endif
                <input type="text" class="form-control form-control-sm" id="bccSearchContact" placeholder="{{ $chatLayout === 'group' ? 'Search groups…' : 'Search contacts…' }}">
            </div>
            <div class="bcc-contact-list flex-grow-1" id="bccContactList">
                @foreach($contacts as $contact)
                    <div class="bcc-contact-item"
                         data-contact-id="{{ $contact['id'] }}"
                         data-sidebar-key="{{ $contact['sidebar_key'] ?? $contact['id'] }}"
                         data-thread-kind="{{ $contact['thread_kind'] ?? 'direct' }}"
                         data-name="{{ strtolower($contact['name']) }}"
                         data-mobile="{{ $contact['mobile'] ?? '' }}">
                        @if(!empty($contact['avatar']))
                            <img src="{{ $contact['avatar'] }}" class="bcc-avatar" alt="">
                        @else
                            <div class="bcc-avatar bcc-avatar-initial" style="background:{{ $contact['color'] }}">{{ $contact['initial'] }}</div>
                        @endif
                        <div class="bcc-contact-body flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between">
                                <span class="bcc-contact-name">{{ $contact['name'] }}</span>
                                <small class="text-muted">{{ $contact['last_message_time'] ?? '' }}</small>
                            </div>
                            <div class="bcc-contact-sub text-muted">{{ $contact['subtitle'] ?? '' }}</div>
                            <div class="bcc-contact-preview text-muted">{{ \Illuminate\Support\Str::limit($contact['last_message'] ?? 'Start group chat', 40) }}</div>
                            @if(!empty($contact['members']))
                                <div class="bcc-contact-members text-muted small">{{ count($contact['members']) }} members</div>
                            @endif
                        </div>
                        <span class="badge bcc-unread" style="{{ ($contact['unread_count'] ?? 0) > 0 ? '' : 'display:none' }}">{{ $contact['unread_count'] ?? 0 }}</span>
                    </div>
                @endforeach
                @if($contacts->isEmpty())
                    <div class="text-center text-muted p-4 small">No chat contacts assigned yet.</div>
                @endif
            </div>
        </div>
        <div class="bcc-main">
            <div class="bcc-chat-header d-flex align-items-center">
                <div class="bcc-avatar bcc-avatar-initial bcc-header-avatar" id="bccHeaderAvatar">?</div>
                <div class="flex-grow-1 ms-3 min-width-0 bcc-header-info" id="bccHeaderInfo" title="">
                    <div class="fw-bold text-truncate" id="bccChatHeader">Select a chat</div>
                    <div class="small text-muted text-truncate" id="bccChatSubheader"></div>
                    <div class="small text-muted bcc-members-line" id="bccChatMembers" style="display:none;"></div>
                </div>
                <button type="button" class="btn btn-light rounded-circle bcc-call-btn" id="bccCallBtn" style="display:none;" title="Call via IVR">
                    <i class="fas fa-phone-alt"></i>
                </button>
            </div>
            <div class="bcc-messages flex-grow-1" id="bccChatMessages">
                <div class="bcc-welcome text-center mt-5">
                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                    <h5>Welcome to Chat</h5>
                    <p class="text-muted mb-0">Select a group — all members share the same conversation.</p>
                </div>
            </div>
            <div class="bcc-input border-top" id="bccChatInputWrap" style="display:none;">
                <form id="bccMessageForm" class="d-flex align-items-end gap-2">
                    <input type="hidden" id="bccActiveContactId" value="">
                    <button type="button" class="btn btn-secondary rounded-circle bcc-attach-btn" id="bccAttachmentBtn" title="Attach file">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <input type="file" id="bccAttachmentInput" class="d-none" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
                    <textarea id="bccMessageInput" class="form-control flex-grow-1" rows="1" placeholder="Type a message…" maxlength="5000"></textarea>
                    <button type="submit" class="btn bcc-send-btn"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>

@if($chatMode === 'corporate')
<div class="modal fade" id="bccMembersModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-users mr-2"></i>Group members</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                <p class="text-muted small px-3 pt-2 mb-2">Start a <strong>private chat</strong> with any CRM team member (separate from group chat).</p>
                <div class="list-group list-group-flush" id="bccMembersList"></div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="bccAttachmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send attachment</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center" id="bccAttachmentPreview"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="bccSendAttachmentBtn" style="background:{{ $themePrimary }};border-color:{{ $themePrimary }}">Send</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Parent wrappers (b2b portal + admin CRM) */
    .b2b-content-card:has(.bcc-chat-page),
    .content .container-fluid:has(.bcc-chat-page) {
        padding: 0 !important;
        overflow: hidden;
    }
    .bcc-chat-page {
        margin: 0;
        height: calc(100vh - 200px);
        max-height: calc(100vh - 200px);
        min-height: 480px;
        display: flex;
        flex-direction: column;
    }
    .content-wrapper .bcc-chat-page {
        height: calc(100vh - 240px);
        max-height: calc(100vh - 240px);
    }
    .bcc-chat-page .bcc-chat-card {
        display: flex;
        flex-direction: row;
        flex: 1 1 auto;
        min-height: 0;
        height: 100%;
        max-height: 100%;
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        box-shadow: 0 8px 30px rgba(15,23,42,.06);
    }
    .bcc-sidebar {
        flex: 0 0 32%;
        max-width: 360px;
        min-width: 260px;
        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;
        overflow: hidden;
        background: #f9fafb;
        border-right: 1px solid #e5e7eb;
    }
    .bcc-main {
        flex: 1 1 auto;
        min-width: 0;
        min-height: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
    }
    .bcc-sidebar-header {
        flex-shrink: 0;
        padding: 1rem;
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
    }
    .bcc-group-badge { display: inline-block; font-size: .72rem; background: #fff3e8; color: {{ $themePrimary }}; padding: .2rem .55rem; border-radius: 6px; font-weight: 600; margin-bottom: .5rem; }
    .bcc-contact-list {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        padding: .5rem;
    }
    .bcc-contact-item {
        display: flex; align-items: center; gap: .65rem; padding: .75rem; margin-bottom: .35rem;
        border-radius: 10px; background: #fff; border: 1px solid transparent; cursor: pointer;
        user-select: none;
    }
    .bcc-contact-item * { pointer-events: none; }
    .bcc-contact-item:hover, .bcc-contact-item.active { background: #fffaf5; border-color: #ffd8b7; }
    .bcc-contact-members { font-size: .72rem; }
    .bcc-message-sender { font-size: .72rem; font-weight: 700; color: {{ $themePrimary }}; margin-bottom: 2px; }
    .bcc-members-line { line-height: 1.3; max-height: 2.6em; overflow: hidden; }
    .bcc-header-info.bcc-header-clickable { cursor: pointer; border-radius: 8px; padding: 4px 8px; margin: -4px -8px; }
    .bcc-header-info.bcc-header-clickable:hover { background: rgba(247, 148, 29, 0.08); }
    .bcc-member-row { display: flex; align-items: center; gap: 12px; padding: 12px 16px; }
    .bcc-member-row .bcc-member-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; flex-shrink: 0; }
    .bcc-member-actions .btn { font-size: .8rem; }
    .bcc-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .bcc-avatar-initial { display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; }
    .bcc-contact-name { font-weight: 600; font-size: .9rem; }
    .bcc-contact-sub, .bcc-contact-preview { font-size: .78rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .bcc-unread { background: {{ $themePrimary }} !important; color: #fff; border-radius: 10px; }
    .bcc-chat-header {
        flex-shrink: 0;
        padding: .85rem 1.25rem;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }
    .bcc-call-btn, .bcc-attach-btn, .bcc-send-btn {
        width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .bcc-call-btn { border: 1px solid #bbf7d0; color: #16a34a; }
    .bcc-call-btn:hover { background: #dcfce7; }
    .bcc-messages {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 1.25rem;
        background-color: #f8f9fa;
        background-image: url('{{ asset('images/chatbackground.png') }}');
        background-repeat: repeat;
        background-size: 280px auto;
        background-attachment: local;
    }
    .bcc-message { margin-bottom: 1rem; max-width: 75%; display: flex; flex-direction: column; word-break: break-word; }
    .bcc-message.sent { margin-left: auto; align-items: flex-end; }
    .bcc-message.received { margin-right: auto; align-items: flex-start; }
    .bcc-message-content {
        padding: .65rem 1rem; border-radius: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,.06); font-size: .95rem;
    }
    .bcc-message.sent .bcc-message-content { background: {{ $themePrimary }}; color: #fff; border-bottom-right-radius: .35rem; }
    .bcc-message.sent .bcc-message-content a { color: #fff; font-weight: 600; }
    .bcc-message.received .bcc-message-content { background: #fff; color: #222; border: 1px solid #e5e7eb; border-bottom-left-radius: .35rem; }
    .bcc-file-attachment {
        display: inline-flex; align-items: center; gap: .5rem; max-width: 100%;
        padding: .45rem .65rem; border-radius: 8px; text-decoration: none !important;
        word-break: break-all;
    }
    .bcc-message.sent .bcc-file-attachment { background: rgba(255,255,255,.22); color: #fff !important; }
    .bcc-message.received .bcc-file-attachment { background: #f3f4f6; color: #1f2937 !important; border: 1px solid #e5e7eb; }
    .bcc-file-attachment i { font-size: 1.25rem; flex-shrink: 0; }
    .bcc-message-content img, .bcc-message-content video { display: block; max-width: 220px; border-radius: 8px; }
    .bcc-message-time { font-size: .72rem; color: #9ca3af; margin-top: .2rem; }
    .bcc-input {
        flex-shrink: 0;
        padding: .85rem 1.25rem;
        background: #f8f9fa;
        border-top: 1px solid #e5e7eb;
    }
    @media (max-width: 767px) {
        .bcc-chat-page { height: calc(100vh - 160px); max-height: calc(100vh - 160px); }
        .bcc-chat-page .bcc-chat-card { flex-direction: column; }
        .bcc-sidebar { flex: 0 0 auto; max-height: 38%; max-width: none; min-width: 0; width: 100%; }
    }
    #bccMessageInput { border-radius: 14px; resize: none; min-height: 44px; max-height: 120px; }
    .bcc-send-btn { background: {{ $themePrimary }}; color: #fff; border: none; border-radius: 50%; }
    .bcc-send-btn:hover { background: {{ $themePrimaryDark }}; color: #fff; }
    .bcc-attach-btn { border-radius: 50%; }
</style>

<script>
(function () {
    var chatMode = @json($chatMode);
    var chatLayout = @json($chatLayout);
    var routes = @json($routes);
    var contacts = @json($contacts->values());
    var groupMembers = @json($groupMembers);
    var currentStaffUserId = @json($currentStaffUserId);
    var currentContactId = null;
    var currentSidebarKey = null;
    var currentThreadKind = 'group';
    var currentContactMobile = null;
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var pendingAttachmentFile = null;
    var isGroupLayout = chatLayout === 'group' || (routes.chat_layout === 'group');

    function notify(msg, type) {
        if (typeof toastr !== 'undefined') {
            toastr[type || 'info'](msg);
        } else {
            alert(msg);
        }
    }

    function escapeHtml(t) {
        var d = document.createElement('div');
        d.textContent = t || '';
        return d.innerHTML;
    }

    function contactBySidebarKey(key) {
        return contacts.find(function (c) {
            return String(c.sidebar_key || c.id) === String(key);
        });
    }

    function isGroupThread() {
        return currentThreadKind === 'group';
    }

    function isSent(msg) {
        if (chatMode === 'corporate') {
            return msg.sender_type === 'b2b';
        }
        if (isGroupThread()) {
            return msg.sender_type === 'staff' && String(msg.sender_staff_user_id) === String(currentStaffUserId);
        }
        return msg.sender_type === 'staff';
    }

    function messagesUrl() {
        if (chatMode === 'corporate') {
            return isGroupThread() ? routes.messages_group : (routes.messages_direct + '/' + currentContactId);
        }
        return isGroupThread()
            ? (routes.messages_group + '/' + currentContactId + '/group')
            : (routes.messages_direct + '/' + currentContactId + '/direct');
    }

    function markReadUrl() {
        if (chatMode === 'corporate') {
            return isGroupThread() ? routes.mark_read_group : (routes.mark_read_direct + '/' + currentContactId);
        }
        return isGroupThread()
            ? (routes.mark_read_group + '/' + currentContactId + '/group')
            : (routes.mark_read_direct + '/' + currentContactId + '/direct');
    }

    function showModal(el) {
        if (typeof jQuery !== 'undefined' && jQuery.fn.modal) jQuery(el).modal('show');
        else if (window.bootstrap) new bootstrap.Modal(document.querySelector(el)).show();
    }

    function hideModal(el) {
        if (typeof jQuery !== 'undefined' && jQuery.fn.modal) jQuery(el).modal('hide');
        else if (window.bootstrap) {
            var m = bootstrap.Modal.getInstance(document.querySelector(el));
            if (m) m.hide();
        }
    }

    function renderMembersModal() {
        var list = document.getElementById('bccMembersList');
        if (!list) return;
        list.innerHTML = '';
        (groupMembers || []).filter(function (m) { return m.type === 'staff'; }).forEach(function (m) {
            var row = document.createElement('div');
            row.className = 'list-group-item bcc-member-row';
            row.innerHTML = '<div class="bcc-member-avatar" style="background:#F7941D">' + escapeHtml((m.name || '?').charAt(0)) + '</div>'
                + '<div class="flex-grow-1 min-width-0"><div class="font-weight-bold">' + escapeHtml(m.name) + '</div><div class="small text-muted">' + escapeHtml(m.role || '') + '</div></div>'
                + '<div class="bcc-member-actions"><button type="button" class="btn btn-sm btn-primary bcc-member-chat-btn" data-staff-id="' + m.id + '"><i class="fas fa-comment mr-1"></i>Message</button></div>';
            list.appendChild(row);
        });
        list.querySelectorAll('.bcc-member-chat-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                hideModal('#bccMembersModal');
                var staffId = String(btn.getAttribute('data-staff-id'));
                var target = document.querySelector('.bcc-contact-item[data-sidebar-key="' + staffId + '"]');
                selectContactElement(target);
            });
        });
    }

    function resolveAttachmentUrl(msg) {
        if (msg.attachment_url) return msg.attachment_url;
        if (msg.attachment) {
            var path = String(msg.attachment).replace(/^storage\//, '').replace(/^\/+/, '');
            return (window.location.origin || '') + '/storage/' + path;
        }
        return '';
    }

    function messageHasAttachment(msg) {
        return !!(msg.attachment_url || msg.attachment);
    }

    function attachmentFileName(msg, url) {
        if (msg.attachment_name) return msg.attachment_name;
        if (url) {
            var part = url.split('/').pop() || '';
            try { part = decodeURIComponent(part); } catch (e) {}
            return part;
        }
        return 'Download file';
    }

    function attachmentFileIcon(type) {
        if (!type) return 'fa-file';
        if (type.indexOf('pdf') !== -1) return 'fa-file-pdf';
        if (type.indexOf('word') !== -1 || type.indexOf('document') !== -1) return 'fa-file-word';
        if (type.indexOf('sheet') !== -1 || type.indexOf('excel') !== -1) return 'fa-file-excel';
        if (type.indexOf('image/') === 0) return 'fa-file-image';
        if (type.indexOf('video/') === 0) return 'fa-file-video';
        return 'fa-file-alt';
    }

    function renderAttachmentContent(msg) {
        var url = resolveAttachmentUrl(msg);
        var type = (msg.attachment_type || '').toLowerCase();
        var bodyHtml = msg.body ? escapeHtml(msg.body) : '';

        if (!url) {
            return bodyHtml || (messageHasAttachment(msg)
                ? '<span class="bcc-file-attachment"><i class="fas fa-file-alt"></i> Attachment</span>'
                : '');
        }

        if (type.indexOf('image/') === 0) {
            var imgHtml = '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener">'
                + '<img src="' + escapeHtml(url) + '" alt="Image" class="img-fluid rounded"></a>';
            return bodyHtml ? (bodyHtml + '<div class="mt-2">' + imgHtml + '</div>') : imgHtml;
        }
        if (type.indexOf('video/') === 0) {
            var vidHtml = '<video controls class="bcc-video-attachment"><source src="' + escapeHtml(url) + '"></video>';
            return bodyHtml ? (bodyHtml + '<div class="mt-2">' + vidHtml + '</div>') : vidHtml;
        }

        var name = attachmentFileName(msg, url);
        var fileHtml = '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener" class="bcc-file-attachment">'
            + '<i class="fas ' + attachmentFileIcon(type) + '"></i>'
            + '<span>' + escapeHtml(name) + '</span></a>';
        return bodyHtml ? (bodyHtml + '<div class="mt-2">' + fileHtml + '</div>') : fileHtml;
    }

    function renderMessages(list) {
        var box = document.getElementById('bccChatMessages');
        if (!list.length) {
            box.innerHTML = '<div class="text-center text-muted mt-5"><p>No messages yet. Say hello!</p></div>';
            return;
        }
        box.innerHTML = '';
        list.forEach(function (msg) {
            var el = document.createElement('div');
            el.className = 'bcc-message ' + (isSent(msg) ? 'sent' : 'received');
            var senderHtml = '';
            if (isGroupThread() && !isSent(msg) && msg.sender_name) {
                senderHtml = '<div class="bcc-message-sender">' + escapeHtml(msg.sender_name) + '</div>';
            }
            var contentHtml = renderAttachmentContent(msg);
            if (!contentHtml || !String(contentHtml).trim()) {
                contentHtml = '<span class="text-muted small">Empty message</span>';
            }
            el.innerHTML = senderHtml
                + '<div class="bcc-message-content">' + contentHtml + '</div>'
                + '<div class="bcc-message-time">' + escapeHtml(msg.time_label || '') + '</div>';
            box.appendChild(el);
        });
        box.scrollTop = box.scrollHeight;
    }

    function setUnreadBadge(key, count) {
        var badge = document.querySelector('.bcc-contact-item[data-sidebar-key="' + key + '"] .bcc-unread');
        if (!badge) {
            badge = document.querySelector('.bcc-contact-item[data-contact-id="' + key + '"] .bcc-unread');
        }
        if (!badge) return;
        badge.style.display = count > 0 ? '' : 'none';
        badge.textContent = count;
    }

    function loadMessages() {
        if (!currentContactId) return;
        fetch(messagesUrl(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                renderMessages(data);
                if (currentSidebarKey) setUnreadBadge(currentSidebarKey, 0);
            });
    }

    function updateHeaderClickable() {
        var info = document.getElementById('bccHeaderInfo');
        if (!info) return;
        if (chatMode === 'corporate' && isGroupThread()) {
            info.classList.add('bcc-header-clickable');
            info.title = 'View group members — start private chat';
        } else {
            info.classList.remove('bcc-header-clickable');
            info.title = '';
        }
    }

    function selectContactElement(item) {
        if (!item) return;

        var sidebarKey = item.getAttribute('data-sidebar-key');
        if (!sidebarKey) return;

        currentContactId = String(item.getAttribute('data-contact-id'));
        currentSidebarKey = String(sidebarKey);
        currentThreadKind = item.getAttribute('data-thread-kind') || 'direct';
        currentContactMobile = item.getAttribute('data-mobile') || null;
        document.getElementById('bccActiveContactId').value = currentContactId;
        document.querySelectorAll('.bcc-contact-item').forEach(function (el) {
            el.classList.toggle('active', el.getAttribute('data-sidebar-key') === String(sidebarKey));
        });

        var c = contactBySidebarKey(sidebarKey);
        if (!c) return;

        document.getElementById('bccChatHeader').textContent = c.name;
        var sub = chatMode === 'corporate'
            ? (c.subtitle || '')
            : ((c.subtitle || '') + (c.mobile ? ' · ' + c.mobile : ''));
        document.getElementById('bccChatSubheader').textContent = sub;

        var membersEl = document.getElementById('bccChatMembers');
        if (membersEl) {
            membersEl.style.display = 'none';
            membersEl.textContent = '';
        }

        var av = document.getElementById('bccHeaderAvatar');
        av.textContent = c.initial || '?';
        av.style.background = c.color || '{{ $themePrimary }}';
        document.getElementById('bccChatInputWrap').style.display = '';

        var callBtn = document.getElementById('bccCallBtn');
        if (callBtn) {
            var showCall = !isGroupThread() && (
                (chatMode === 'corporate') ||
                (chatMode === 'staff' && currentThreadKind === 'direct')
            );
            callBtn.style.display = showCall ? 'flex' : 'none';
        }

        updateHeaderClickable();
        loadMessages();
        fetch(markReadUrl(), {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }
        });
    }

    document.getElementById('bccContactList')?.addEventListener('click', function (e) {
        var item = e.target.closest('.bcc-contact-item');
        if (!item) return;
        selectContactElement(item);
    });

    document.getElementById('bccHeaderInfo')?.addEventListener('click', function () {
        if (chatMode !== 'corporate' || !isGroupThread()) return;
        renderMembersModal();
        showModal('#bccMembersModal');
    });

    document.getElementById('bccSearchContact')?.addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.bcc-contact-item').forEach(function (el) {
            el.style.display = (el.getAttribute('data-name') || '').indexOf(q) !== -1 ? '' : 'none';
        });
    });

    document.getElementById('bccMessageForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        var body = document.getElementById('bccMessageInput').value.trim();
        if (!body || !currentContactId) return;
        var payload = { body: body };
        if (chatMode === 'staff') {
            payload.b2b_user_id = parseInt(currentContactId, 10);
            payload.thread = currentThreadKind;
        } else if (!isGroupThread()) {
            payload.user_id = parseInt(currentContactId, 10);
        }

        fetch(routes.send, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json(); }).then(function () {
            document.getElementById('bccMessageInput').value = '';
            loadMessages();
        });
    });

    document.getElementById('bccCallBtn')?.addEventListener('click', function () {
        if (!currentContactId || isGroupThread()) return;
        if (!confirm('Start IVR call to this contact?')) return;
        var btn = this;
        btn.disabled = true;
        var url = routes.call + '/' + (chatMode === 'corporate' ? currentContactId : currentContactId);
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) notify(res.message || 'Call initiated successfully', 'success');
                else notify(res.message || 'Failed to initiate call', 'error');
            })
            .catch(function () { notify('Failed to initiate call', 'error'); })
            .finally(function () { btn.disabled = false; });
    });

    document.getElementById('bccAttachmentBtn')?.addEventListener('click', function () {
        document.getElementById('bccAttachmentInput').click();
    });

    document.getElementById('bccAttachmentInput')?.addEventListener('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;
        pendingAttachmentFile = file;
        var preview = document.getElementById('bccAttachmentPreview');
        if (file.type.indexOf('image/') === 0) {
            preview.innerHTML = '<img src="' + URL.createObjectURL(file) + '" class="img-fluid" style="max-height:280px;">';
        } else if (file.type.indexOf('video/') === 0) {
            preview.innerHTML = '<video controls style="max-width:100%;max-height:280px;"><source src="' + URL.createObjectURL(file) + '"></video>';
        } else {
            preview.innerHTML = '<i class="fas fa-file fa-3x text-primary mb-2"></i><div>' + escapeHtml(file.name) + '</div>';
        }
        if (typeof jQuery !== 'undefined' && jQuery.fn.modal) jQuery('#bccAttachmentModal').modal('show');
        else if (window.bootstrap) new bootstrap.Modal(document.getElementById('bccAttachmentModal')).show();
    });

    document.getElementById('bccSendAttachmentBtn')?.addEventListener('click', function () {
        if (!pendingAttachmentFile || !currentContactId) return;
        var fd = new FormData();
        fd.append('attachment', pendingAttachmentFile);
        if (chatMode === 'staff') {
            fd.append('b2b_user_id', currentContactId);
            fd.append('thread', currentThreadKind);
        } else if (!isGroupThread()) {
            fd.append('user_id', currentContactId);
        }
        fd.append('_token', csrf);

        fetch(routes.send_attachment, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    notify('Attachment sent', 'success');
                    pendingAttachmentFile = null;
                    document.getElementById('bccAttachmentInput').value = '';
                    if (typeof jQuery !== 'undefined') jQuery('#bccAttachmentModal').modal('hide');
                    loadMessages();
                } else notify(res.message || 'Failed to send', 'error');
            });
    });

    function pollUnread() {
        if (!routes.unread_counts) return;
        fetch(routes.unread_counts, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (counts) {
                if (chatMode === 'corporate') {
                    setUnreadBadge('group', counts.group || 0);
                    Object.keys(counts || {}).forEach(function (id) {
                        if (id === 'group') return;
                        setUnreadBadge(id, counts[id]);
                    });
                } else {
                    Object.keys(counts || {}).forEach(function (key) {
                        if (key !== currentSidebarKey) setUnreadBadge(key, counts[key]);
                    });
                }
            });
    }

    renderMembersModal();
    if (contacts.length) {
        var firstKey = contacts[0].sidebar_key || contacts[0].id;
        var firstItem = document.querySelector('.bcc-contact-item[data-sidebar-key="' + firstKey + '"]');
        selectContactElement(firstItem);
    }
    setInterval(function () {
        pollUnread();
        if (currentContactId) loadMessages();
    }, 8000);
    setInterval(pollUnread, 5000);
})();
</script>
