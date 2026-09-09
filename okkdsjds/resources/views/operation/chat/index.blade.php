@extends('operation.layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="container-fluid d-flex justify-content-center align-items-start">
            <div class="chat-card row w-100 justify-content-center align-items-stretch mobile-chat-wrapper"
                style="overflow: hidden;">
                <!-- Sidebar: User List -->
                <div class="col-md-4 col-lg-4 chat-sidebar p-0 d-flex flex-column border-end mobile-user-list"
                    style="background: #f9f9f9; min-width: 270px; max-width: 340px;">
                    <div
                        class="chat-sidebar-header d-flex flex-column px-3 py-2 border-bottom bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h5 class="mb-0" style="font-size:18px;">Chat</h5>
                            <input type="text" class="form-control" id="searchUser" placeholder="Search..."
                                style="width: 150px; font-size:12px;">
                        </div>
                        <div class="d-flex align-items-center justify-content-start">
                            <button id="showFavoritesBtn" class="action-btn favorites-btn me-2" title="Show Favorites">
                                <i class="fa fa-star"></i>
                            </button>
                            <button id="showGroupsBtn" class="action-btn groups-btn me-2" title="Show Groups">
                                <i class="fa fa-users"></i>
                            </button>
                            <button class="action-btn create-group-btn me-2" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                                <i class="fa fa-users"></i>
                                <span class="btn-text">Create Group</span>
                            </button>
                        </div>
                    </div>

                    <div class="chat-user-list overflow-auto flex-grow-1" id="usersList" style="background: #f9f9f9;">
                        @foreach ($users as $user)
                            <div href="#" class="chat-user-item d-flex align-items-center px-3 py-2"
                                data-user-id="{{ $user->id }}" data-mobile="{{ $user->mobile }}"
                                style="text-decoration:none;">
                                <i class="fa fa-star favorite-star me-2" data-user-id="{{ $user->id }}" style="color: #ccc; cursor:pointer;"></i>
                                @if ($user->profile_image)
                                    <div class="position-relative" style="display:inline-block;">
                                        <img src="{{ asset('storage/' . $user->profile_image) }}"
                                            class="rounded-circle user-avatar me-3" height="44" width="44"
                                            alt="{{ $user->f_name }}">
                                        @if($user->last_online && \Carbon\Carbon::parse($user->last_online)->gt(now()->subMinutes(3)))
                                            {{-- <span class="position-absolute"
                                                  style="right: 6px; bottom: 6px; width: 10px; height: 10px; background: #28c76f; border: 2px solid #fff; border-radius: 50%;"></span> --}}
                                        @endif
                                    </div>
                                @else
                                    <div class="position-relative" style="display:inline-block;">
                                        <div class="user-initial-avatar me-3"
                                            style="background: {{ $user->color ?? '#F7941D' }};">
                                            {{ strtoupper(substr($user->f_name, 0, 1)) }}
                                        </div>
                                        @if($user->last_online && \Carbon\Carbon::parse($user->last_online)->gt(now()->subMinutes(3)))
                                            <span class="position-absolute"
                                                  style="right: 6px; bottom: 6px; width: 10px; height: 10px; background: #28c76f; border: 2px solid #fff; border-radius: 50%;"></span>
                                        @endif
                                    </div>
                                @endif
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold user-name"
                                            style="font-size:14px;">{{ $user->f_name }}</span>
                                        <small class="text-muted last-message-time" style="font-size:0.85em;">
                                            @if($user->last_message)
                                                {{ \Carbon\Carbon::parse($user->last_message->created_at)->format('h:i A') }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="text-muted small last-message-preview"
                                        style="font-size:0.97em; white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px;">
                                        @if($user->last_message)
                                            @if($user->last_message->attachment)
                                                <i class="fa fa-paperclip"></i> Attachment
                                            @elseif($user->last_message->message)
                                                {{ $user->last_message->message }}
                                            @endif
                                        @endif
                                    </div>
                                    @if($user->last_online)
                                        <div class="text-muted" style="font-size:12px;">
                                            {{ \Carbon\Carbon::parse($user->last_online)->format('d M, h:i A') }}
                                        </div>
                                    @endif
                                </div>
                                <span class="badge unread-count ms-2" style="{{ ($user->unread_count ?? 0) > 0 ? '' : 'display: none;' }} background: #25d366; color: #fff; font-weight: bold;">{{ $user->unread_count ?? 0 }}</span>
                            </div>
                        @endforeach
                        <!-- Group items will be appended here by JS -->
                    </div>
                </div>
                <!-- Main Chat Area -->
                <div class="col-md-8 col-lg-8 chat-main p-0 d-flex flex-column mobile-chat-area" style="background: #fff;">
                    <div class="chat-header d-flex align-items-center px-2 py-2 border-bottom bg-white">
                        <!-- Back button for mobile -->
                        <button class="btn btn-light rounded-circle d-md-none me-2 mobile-back-btn" style="width: 40px; height: 40px; display: none;" onclick="showUserListMobile()">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <div class="position-relative me-3">
                            <img src="" alt="" class="rounded-circle chat-user-avatar"
                                style="width: 44px; height: 44px;">
                            {{-- <span
                                class="position-absolute bottom-0 end-0 p-1 bg-success border border-light rounded-circle online-status"></span> --}}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-0" id="chatHeader">Select a user to start chatting</div>
                            <div id="groupMembers" class="small text-muted"></div>
                        </div>
                        <button class="btn btn-light rounded-circle call-btn"
                            style="width: 40px; height: 40px; display: none;" onclick="makeCall()">
                            <i class="fas fa-phone-alt"></i>
                        </button>
                        <button id="addUserToGroupBtn" class="btn btn-sm btn-outline-primary ms-2" style="display:none;">Add User</button>
                        <button id="showGroupMembersBtn" class="btn btn-sm btn-outline-info ms-2" style="display:none;">Users</button>
                    </div>
                    <div class="chat-messages flex-grow-1 px-4 py-3" id="chatMessages">
                        <div class="chat-welcome text-center mt-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h4>Welcome to Chat</h4>
                            <p class="text-muted">Select a user to start chatting</p>
                        </div>
                    </div>
                    <div class="chat-input border-top px-4 py-3">
                        <form id="messageForm" class="d-flex align-items-center">
                            <input type="hidden" id="receiverId" name="receiver_id">
                            <input type="text" class="form-control me-2" id="messageInput"
                                placeholder="Type your message..." disabled>
                            <button type="button" class="btn btn-secondary rounded-circle me-2" id="attachmentBtn" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input type="file" id="attachmentInput" style="display:none;" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" />
                            <button type="submit" class="btn btn-primary rounded-circle" disabled
                                style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Group Creation Modal -->
    <div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="createGroupForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createGroupModalLabel">Create Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="groupName" class="form-label">Group Name</label>
                        <input type="text" class="form-control" id="groupName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="groupUsers" class="form-label">Select Users (min 3)</label>
                        <select class="form-control" id="groupUsers" name="user_ids[]" multiple required>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->f_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="groupCreateError" class="text-danger small"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add User to Group Modal -->
    <div class="modal fade" id="addUserToGroupModal" tabindex="-1" aria-labelledby="addUserToGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="addUserToGroupForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserToGroupModalLabel">Add Users to Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <select class="form-control" id="addGroupUsers" name="user_id[]" multiple required>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->f_name }}</option>
                        @endforeach
                    </select>
                    <div id="addUserGroupError" class="text-danger small mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Group Members Modal -->
    <div class="modal fade" id="groupMembersModal" tabindex="-1" aria-labelledby="groupMembersModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="groupMembersModalLabel">Group Members (<span id="groupMembersCount"></span>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body" id="groupMembersList">
                    <!-- Members will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Attachment Preview Modal -->
    <div class="modal fade" id="attachmentPreviewModal" tabindex="-1" aria-labelledby="attachmentPreviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="attachmentPreviewModalLabel">Preview Attachment</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center" id="attachmentPreviewBody"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="sendAttachmentBtn">Send</button>
          </div>
        </div>
      </div>
    </div>

    <style>
        body,
        .content-wrapper {
            background: #f8f9fc !important;
        }

        .chat-card {
            background: transparent;
            overflow: hidden;
            height: 83vh;
        }

        .chat-sidebar {
            background: #f9f9f9;
            border-right: 1px solid #f0f0f0;
            border-radius: 18px 0 0 18px;
            min-width: 270px;
            max-width: 340px;
            padding: 0;
        }

        .chat-sidebar-header {
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 20px 24px;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        #searchUser {
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            padding: 8px 14px;
            font-size: 1em;
            margin-left: 10px;
        }

        .chat-user-list {
            overflow-y: auto;
            flex-grow: 1;
            background: #f9f9f9;
            padding: 0 0 10px 0;
            height: 40vh; /* Adjust for sticky header height */
        }

        .chat-user-item {
            display: flex;
            align-items: center;
            padding: 16px 24px;
            border-radius: 10px;
            margin: 4px 8px;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            border: 2px solid transparent;
            background: #fff;
        }

        .chat-user-item.active,
        .chat-user-item:hover {
            background: #f0f8ff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.08);
        }

        .user-avatar,
        .user-initial-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 16px;
            background: #F7941D;
            color: #fff;
            font-weight: 600;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-initial-avatar {
            background: #F7941D;
        }

        .chat-main {
            background: #fff;
            border-radius: 0 18px 18px 0;
            display: flex;
            flex-direction: column;
            padding: 0;
            height: 100%;
            overflow-y: auto;
        }

        .chat-header {
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .chat-user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
        }

        .online-status {
            width: 10px;
            height: 10px;
            background: #1abc9c;
            border: 2px solid #fff;
            border-radius: 50%;
            position: absolute;
            bottom: 2px;
            right: 2px;
        }

        .chat-messages {
            background: #f8f9fa;
            overflow-y: auto;
            flex-grow: 1;
            padding: 32px 32px 16px 32px;
            min-height: 350px;
            max-height: 60vh;
            display: flex;
            flex-direction: column;
            font-size: 12px;
        }

        .message {
            margin-bottom: 1.2rem;
            max-width: 70%;
            display: flex;
            flex-direction: column;
            word-break: break-word;
        }

        .message.sent {
            margin-left: auto;
            align-items: flex-end;
        }

        .message.received {
            margin-right: auto;
            align-items: flex-start;
        }

        .message-content {
            padding: 0.75rem 1.2rem;
            border-radius: 1.2rem;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.07);
            font-size: 1.05em;
            background: #e6f0ff;
            color: #222;
        }

        .message.sent .message-content {
            background-color: #F7941D;
            color: #fff;
            border-bottom-right-radius: 0.4rem;
        }

        .message.received .message-content {
            background-color: #fff;
            color: #222;
            border-bottom-left-radius: 0.4rem;
            border: 1px solid #e6e6e6;
        }

        .message-time {
            font-size: 0.8em;
            color: #b0b0b0;
            margin-top: 0.2rem;
            text-align: right;
        }

        .chat-input {
            background-color: #F8F9FA;
            border-top: 1px solid #f0f0f0;
            padding: 18px 24px;
        }

        #messageInput {
            border-radius: 18px;
            border: 1px solid #e0e0e0;
            padding: 14px 18px;
            font-size: 14px;
            min-height: 48px;
            background: #ffffff;
        }

        #messageInput:focus {
            border-color: #F7941D;
            background: #ffffff;
        }

        .chat-input .btn {
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            background: #F7941D;
            color: #fff;
            border: none;
            margin-left: 10px;
            transition: background 0.2s;
        }

        .chat-input .btn:hover {
            background: #d97706;
            color: #fff;
        }

        @media (max-width: 991px) {
            .chat-card {
                max-width: 100%;
                border-radius: 0;
            }

            .chat-sidebar {
                border-radius: 0;
            }

            .chat-main {
                border-radius: 0;
            }
        }

        @media (max-width: 767px) {
            .chat-card {
                min-height: 400px;
            }

            .chat-sidebar,
            .chat-main {
                border-radius: 0;
                box-shadow: none;
            }

            .chat-user-list {
                min-height: 650px;
            }
        }

        .call-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .call-btn:hover {
            background-color: #F7941D;
            color: white;
        }

        .message-seen {
            font-size: 0.8em;
            color: #28c76f;
            margin-top: 2px;
        }

        .chat-user-item.group-item {
            background: #eaf6ff;
            border-left: 4px solid #0d6efd;
        }
        .chat-user-item.group-item .fa-users {
            color: #0d6efd;
        }

        .delete-group-btn {
            background: none;
            border: none;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0.6;
            margin-left: 8px;
        }

        .delete-group-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
            opacity: 1;
            transform: scale(1.1);
        }

        .delete-group-btn:active {
            transform: scale(0.95);
        }

        .delete-group-btn .fa-trash {
            font-size: 12px;
            transition: color 0.2s ease;
        }

        .delete-group-btn:hover .fa-trash {
            color: #dc3545 !important;
        }

        .chat-user-item.group-item:hover .delete-group-btn {
            opacity: 1;
        }

        .chat-user-item.group-item {
            position: relative;
        }

        /* Modern Action Buttons */
        .action-btn {
            background: #ffffff;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-color: #F7941D;
            color: #F7941D;
        }

        .action-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .action-btn i {
            font-size: 14px;
            transition: transform 0.2s ease;
        }

        .action-btn:hover i {
            transform: scale(1.1);
        }

        /* Favorites Button Specific */
        .favorites-btn {
            border-color: #ffc107;
            color: #ffc107;
        }

        .favorites-btn:hover {
            background: linear-gradient(135deg, #ffc107, #ff8f00);
            border-color: #ffc107;
            color: #ffffff;
        }

        .favorites-btn.active {
            background: linear-gradient(135deg, #ffc107, #ff8f00);
            border-color: #ffc107;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }

        /* Groups Button Specific */
        .groups-btn {
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .groups-btn:hover {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border-color: #0d6efd;
            color: #ffffff;
        }

        .groups-btn.active {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border-color: #0d6efd;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }

        /* Create Group Button Specific */
        .create-group-btn {
            border-color: #28a745;
            color: #28a745;
        }

        .create-group-btn:hover {
            background: linear-gradient(135deg, #28a745, #20c997);
            border-color: #28a745;
            color: #ffffff;
        }

        /* Responsive text hiding */
        @media (max-width: 768px) {
            .btn-text {
                display: none;
            }
            .action-btn {
                padding: 8px 12px;
            }
        }

        /* Button loading state */
        .action-btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .action-btn.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .badge.unread-count {
            background: #25d366 !important;
            color: #fff !important;
            font-weight: bold;
        }

        @media (max-width: 480px) {
            .mobile-chat-wrapper {
                flex-direction: column !important;
                height: 100vh !important;
                min-height: 100vh !important;
                max-width: 100vw !important;
                border-radius: 0 !important;
            }
            .chat-sidebar, .chat-main {
                min-width: 100vw !important;
                max-width: 100vw !important;
                width: 100vw !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                height: 100vh !important;
                position: absolute;
                left: 0;
                top: 0;
                z-index: 10;
                display: none;
            }
            .mobile-user-list {
                display: block !important;
            }
            .mobile-chat-area {
                display: none !important;
            }
            .mobile-chat-wrapper.show-chat .mobile-chat-area {
                display: block !important;
                z-index: 20;
            }
            .mobile-chat-wrapper.show-chat .mobile-user-list {
                display: none !important;
            }
            .mobile-chat-wrapper.show-list .mobile-user-list {
                display: block !important;
                z-index: 20;
            }
            .mobile-chat-wrapper.show-list .mobile-chat-area {
                display: none !important;
            }
            .chat-header .mobile-back-btn {
                display: inline-flex !important;
            }
            .chat-header {
                gap: 8px !important;
                padding: 12px 10px !important;
            }
                         .chat-messages {
                padding: 16px 6px 8px 6px !important;
                height: 100% !important;
                font-size: 13px !important;
            }
            .chat-input {
                padding: 10px 6px !important;
            }
            #messageInput {
                font-size: 13px !important;
                min-height: 38px !important;
                padding: 10px 12px !important;
            }
            .chat-user-item {
                padding: 12px 10px !important;
                font-size: 15px !important;
            }
            .user-avatar, .user-initial-avatar {
                width: 36px !important;
                height: 36px !important;
                font-size: 1em !important;
                margin-right: 10px !important;
            }
            .chat-header .chat-user-avatar {
                width: 36px !important;
                height: 36px !important;
                margin-right: 6px !important;
            }
            .action-btn, .favorites-btn, .groups-btn, .create-group-btn {
                padding: 6px 10px !important;
                font-size: 12px !important;
            }
            .chat-main, .chat-sidebar {
                min-height: 100vh !important;
                max-height: 100vh !important;
                overflow-y: auto !important;
            }
        }
    </style>
    <script>
        let currentUserId = null;
        let currentUserMobile = null;
        let messagePollingInterval = null;
        let currentGroupId = null; // Track current group for group chat
        let lastGroupUsers = [];

        document.addEventListener('DOMContentLoaded', function() {
            // Search users
            const searchInput = document.getElementById('searchUser');
            searchInput.addEventListener('input', function() {
                const searchText = this.value.toLowerCase();
                document.querySelectorAll('.chat-user-item').forEach(function(item) {
                    const userName = item.querySelector('.user-name').textContent.toLowerCase();
                    if (userName.includes(searchText)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            // User selection (event delegation)
            const usersList = document.getElementById('usersList');
            if (usersList) {
                usersList.addEventListener('click', function(e) {
                    let target = e.target;
                    while (target && !target.classList.contains('chat-user-item')) {
                        target = target.parentElement;
                    }
                    if (!target) return;
                    if (target.classList.contains('group-item')) return;
                    $('#addUserToGroupBtn').hide();
                    $('#showGroupMembersBtn').hide();
                    $('#groupMembers').text('');
                });
            }

            // User selection (event delegation)
            const usersList2 = document.getElementById('usersList');
            console.log('Users list element:', usersList2);

            usersList2.addEventListener('click', function(e) {
                console.log('Click event fired on:', e.target);

                let target = e.target;
                // Find the closest .chat-user-item
                while (target && !target.classList.contains('chat-user-item')) {
                    target = target.parentElement;
                }
                if (!target) {
                    console.log('No chat-user-item found');
                    return;
                }

                console.log('Chat user item clicked:', target);
                e.preventDefault();

                // If group item, skip (handled separately)
                if (target.classList.contains('group-item')) return;

                const userId = target.getAttribute('data-user-id');
                console.log('User ID from data attribute:', userId);

                if (!userId) {
                    console.log('No user ID found!');
                    return;
                }

                // Get mobile number from the users array
                currentUserMobile = target.getAttribute('data-mobile');
                console.log('User mobile:', currentUserMobile);

                const userName = target.querySelector('.user-name').textContent;
                const userImg = target.querySelector('img.user-avatar');
                const userInitial = target.querySelector('.user-initial-avatar');
                const chatHeader = document.getElementById('chatHeader');
                const chatUserAvatar = document.querySelector('.chat-user-avatar');
                const chatMessages = document.getElementById('chatMessages');

                console.log('About to clear polling and set currentUserId');

                // Clear previous polling immediately
                if (messagePollingInterval) {
                    clearInterval(messagePollingInterval);
                    messagePollingInterval = null;
                }

                // Clear chat messages immediately when switching users
                chatMessages.innerHTML = '';

                // Set currentUserId BEFORE any operations
                currentUserId = String(userId);
                console.log('Current user ID set to:', currentUserId);

                // Remove active from all, add to selected
                document.querySelectorAll('.chat-user-item').forEach(function(item) {
                    item.classList.remove('active');
                });
                target.classList.add('active');
                chatHeader.textContent = userName;

                // Update chat header avatar
                if (userImg) {
                    chatUserAvatar.src = userImg.src;
                    chatUserAvatar.style.display = '';
                    chatUserAvatar.style.background = '';
                    chatUserAvatar.alt = userName;
                } else if (userInitial) {
                    chatUserAvatar.src = '';
                    chatUserAvatar.style.display = '';
                    chatUserAvatar.style.background = userInitial.style.background;
                    chatUserAvatar.alt = userInitial.textContent;
                }

                document.getElementById('receiverId').value = userId;
                document.getElementById('messageInput').disabled = false;
                const submitButton = document.querySelector('#messageForm button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = false;
                    console.log('Send button enabled');
                }
                const chatWelcome = document.querySelector('.chat-welcome');
                if (chatWelcome) {
                    chatWelcome.style.display = 'none';
                }

                // Load messages immediately for the selected user
                console.log('About to call loadMessages with userId:', userId);
                loadMessages(userId);

                // Start new polling for the selected user after a small delay
                setTimeout(() => {
                    messagePollingInterval = setInterval(() => {
                        if (String(currentUserId) === String(userId)) {
                            loadMessages(userId);
                        }
                    }, 3000);
                }, 100);

                // Mark messages as read
                markAsRead(userId);

                const callBtn = document.querySelector('.call-btn');
                if (callBtn) {
                    callBtn.style.display = 'flex';
                }

                currentGroupId = null; // Not in group chat
            });

            // Handle message sending
            document.getElementById('messageForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const message = document.getElementById('messageInput').value;
                if (!message.trim()) return;

                // Group chat
                if (currentGroupId) {
                    fetch('/group-chat/send/' + currentGroupId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ message })
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('messageInput').value = '';
                        // Reload group messages without re-triggering click
                        if (currentGroupId) {
                            if (typeof loadGroupMessages === 'function') {
                                loadGroupMessages(true);
                            } else {
                                $(`.chat-user-item.group-item[data-group-id=\"${currentGroupId}\"]`).click();
                            }
                        }
                    });
                }
                // User-to-user chat
                else if (currentUserId) {
                    const receiverId = document.getElementById('receiverId').value;
                    fetch('{{ route('operation.chat.send') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: `receiver_id=${encodeURIComponent(receiverId)}&message=${encodeURIComponent(message)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('messageInput').value = '';
                        if (String(currentUserId) === String(receiverId)) {
                            loadMessages(receiverId);
                        }
                    });
                }
            });
            // Handle enter key
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    document.getElementById('messageForm').dispatchEvent(new Event('submit'));
                }
            });

            // FAVORITE SYSTEM
            function updateFavoriteStars(favorites) {
                console.log('updateFavoriteStars called with', favorites);
                document.querySelectorAll('.chat-user-item').forEach(item => {
                    var star = item.querySelector('.favorite-star');
                    var userId = parseInt(item.getAttribute('data-user-id'));
                    if (star && favorites.includes(userId)) {
                        star.style.color = 'gold';
                        star.classList.add('favorited');
                        star.setAttribute('data-favorited', '1');
                        item.classList.add('is-favorite');
                    } else {
                        if (star) {
                            star.style.color = '#ccc';
                            star.classList.remove('favorited');
                            star.setAttribute('data-favorited', '0');
                        }
                        item.classList.remove('is-favorite');
                    }
                });
                // Log which items are favorite
                console.log('Favorite items:', $('.chat-user-item.is-favorite').map(function(){return $(this).data('user-id');}).get());
            }

            // Show only favorites in the user list
            function showOnlyFavorites() {
                var $all = $('.chat-user-item').not('.group-item');
                var $fav = $('.chat-user-item.is-favorite').not('.group-item');
                var $groups = $('.chat-user-item.group-item');
                $all.each(function() {
                    this.style.setProperty('display', 'none', 'important');
                });
                $fav.each(function() {
                    this.style.setProperty('display', '', 'important');
                });
                $groups.each(function() {
                    this.style.setProperty('display', 'none', 'important');
                });
                if ($fav.length > 0) {
                    $('#noFavoritesMsg').remove();
                } else {
                    if ($('#noFavoritesMsg').length === 0) {
                        $('.chat-user-list').append('<div id="noFavoritesMsg" class="text-center text-muted py-4">No favorite users</div>');
                    }
                }
                console.log('showOnlyFavorites: hiding', $all.length - $fav.length, 'showing', $fav.length, 'groups hidden:', $groups.length);
            }

            // Show only groups in the user list
            function showOnlyGroups() {
                var $all = $('.chat-user-item').not('.group-item');
                var $groups = $('.chat-user-item.group-item');
                $all.each(function() {
                    this.style.setProperty('display', 'none', 'important');
                });
                $groups.each(function() {
                    this.style.setProperty('display', '', 'important');
                });
                if ($groups.length > 0) {
                    $('#noGroupsMsg').remove();
                } else {
                    if ($('#noGroupsMsg').length === 0) {
                        $('.chat-user-list').append('<div id="noGroupsMsg" class="text-center text-muted py-4">No groups available</div>');
                    }
                }
                console.log('showOnlyGroups: hiding', $all.length, 'showing', $groups.length, 'groups');
            }

            // Show all users and groups
            function showAllUsers() {
                $('.chat-user-item').not('.group-item').show();
                $('.chat-user-item.group-item').show();
                $('#noFavoritesMsg').remove();
                $('#noGroupsMsg').remove();
                console.log('showAllUsers: showing all users and groups');
            }

            // Filter button logic
            let filterActive = false;
            let groupsFilterActive = false;
            // Use delegated event for robustness
            $(document).on('click', '#showFavoritesBtn', function() {
                console.log('Show Favorites button clicked');
                const $btn = $(this);
                filterActive = !$btn.hasClass('active');
                $btn.toggleClass('active', filterActive);

                // Turn off groups filter if favorites is active
                if (filterActive) {
                    $('#showGroupsBtn').removeClass('active');
                    groupsFilterActive = false;
                }

                if (filterActive) {
                    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
                    fetch('/operation/chat/favorites')
                        .then(res => res.json())
                        .then(favorites => {
                            updateFavoriteStars(favorites);
                            showOnlyFavorites();
                        })
                        .finally(() => {
                            $btn.prop('disabled', false).html('<i class="fa fa-star"></i>');
                        });
                } else {
                    showAllUsers();
                }
            });

            // Groups filter button logic
            $(document).on('click', '#showGroupsBtn', function() {
                console.log('Show Groups button clicked');
                const $btn = $(this);
                groupsFilterActive = !$btn.hasClass('active');
                $btn.toggleClass('active', groupsFilterActive);

                // Turn off favorites filter if groups is active
                if (groupsFilterActive) {
                    $('#showFavoritesBtn').removeClass('active');
                    filterActive = false;
                }

                if (groupsFilterActive) {
                    showOnlyGroups();
                } else {
                    showAllUsers();
                }
            });

            // On star click, toggle favorite and re-apply filter if active
            $(document).on('click', '.favorite-star', function(e) {
                e.stopPropagation();
                var userId = $(this).data('userId');
                fetch('/operation/chat/favorite/' + userId, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
                })
                .then(res => res.json())
                .then(data => {
                    fetch('/operation/chat/favorites')
                        .then(res => res.json())
                        .then(favorites => {
                            updateFavoriteStars(favorites);
                            if (filterActive) {
                                showOnlyFavorites();
                            }
                        });
                });
            });

            // On page load, fetch and update favorites (but don't filter)
            fetch('/operation/chat/favorites')
                .then(res => res.json())
                .then(favorites => {
                    updateFavoriteStars(favorites);
                });

            updateUnreadCounts(); // Ensure unread counts show on page load
        });

        function loadMessages(userId) {
            console.log('=== loadMessages function called ===');
            console.log('Input userId:', userId, 'type:', typeof userId);
            console.log('Current userId before conversion:', currentUserId, 'type:', typeof currentUserId);

            // Convert to string to ensure proper comparison
            userId = String(userId);
            currentUserId = String(currentUserId);

            console.log('After conversion - userId:', userId, 'currentUserId:', currentUserId);

            // Only load messages if this user is still the currently selected one
            if (currentUserId !== userId) {
                console.log('Skipping load - user changed. Current:', currentUserId, 'Requested:', userId);
                return;
            }

            console.log('Starting API call for userId:', userId);
            const apiUrl = `{{ url('operation/chat/messages') }}/${userId}`;
            console.log('API URL:', apiUrl);

            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(messages => {
                    // Double check - only update if this user is still selected
                    if (String(currentUserId) !== String(userId)) {
                        console.log('Skipping display - user changed during fetch');
                        return;
                    }

                    console.log('Messages received:', messages.length, 'messages');

                    const chatMessages = document.getElementById('chatMessages');
                    chatMessages.innerHTML = '';

                    if (messages.length === 0) {
                        chatMessages.innerHTML = `
                    <div class="chat-welcome text-center mt-5">
                        <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                        <h4>No messages yet</h4>
                        <p class="text-muted">Start the conversation!</p>
                    </div>
                `;
                        return;
                    }

                    let currentDate = null;
                    messages.forEach(function(message) {
                        const messageDate = new Date(message.created_at);
                        const messageDateStr = messageDate.toLocaleDateString();

                        if (currentDate !== messageDateStr) {
                            currentDate = messageDateStr;
                            chatMessages.innerHTML += `
                        <div class="text-center my-3">
                            <span class="badge bg-light text-dark">${messageDateStr}</span>
                        </div>
                    `;
                        }

                        const isSent = message.sender_id == {{ Auth::id() }};
                        let seenHtml = '';
                        if (isSent && message.is_read) {
                            seenHtml = `<div class="message-seen text-end" style="font-size: 0.8em; color: #28c76f;">Seen</div>`;
                        }
                        let messageHtml = '';
                        if (message.attachment) {
                            let attachmentHtml = '';
                            if (message.attachment_type && message.attachment_type.startsWith('image/')) {
                                attachmentHtml = `
                                    <div style="position:relative; display:inline-block;">
                                        <img src="/storage/${message.attachment}" class="img-fluid" style="max-width:200px;max-height:200px; border-radius:8px;" alt="Attachment">
                                        <a href="/storage/${message.attachment}" download class="btn btn-sm btn-primary" style="position:absolute;bottom:8px;right:8px;z-index:2; border-radius:50%; background:#F7941D; color:#fff; padding:8px;">
                                            <i class="fa fa-download"></i>
                                        </a>
                                    </div>
                                `;
                            } else if (message.attachment_type && message.attachment_type.startsWith('video/')) {
                                attachmentHtml = `
                                    <div style="position:relative; display:inline-block;">
                                        <video controls style="max-width:200px;max-height:200px; border-radius:8px;"><source src="/storage/${message.attachment}"></video>
                                        <a href="/storage/${message.attachment}" download class="btn btn-sm btn-primary" style="position:absolute;bottom:8px;right:8px;z-index:2; border-radius:50%; background:#F7941D; color:#fff; padding:8px;">
                                            <i class="fa fa-download"></i>
                                        </a>
                                    </div>
                                `;
                            } else {
                                attachmentHtml = `
                                    <div style="background:#e6f0ff; border-radius:8px; padding:16px; display:flex;color:#000; align-items:center; gap:12px;">
                                        <i class="fa fa-file fa-2x text-primary"></i>
                                        <span style="flex:1;">${message.attachment.split('/').pop()}</span>
                                        <a href="/storage/${message.attachment}" download class="btn btn-sm btn-primary d-flex align-items-center" style="background:#F7941D; color:#fff;">
                                            <i class="fa fa-download me-1"></i> Download
                                        </a>
                                    </div>
                                `;
                            }
                            messageHtml = `
                        <div class="message ${isSent ? 'sent' : 'received'}">
                            <div class="message-content">${attachmentHtml}</div>
                            <div class="message-time">${messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                            ${seenHtml}
                        </div>
                    `;
                        } else {
                            messageHtml = `
                        <div class=\"message ${isSent ? 'sent' : 'received'}\">
                            <div class=\"message-content ${isSent ? 'sent-bg' : 'received-bg'}\">${message.message}</div>
                            <div class=\"message-time\">${messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                            ${seenHtml}
                        </div>
                    `;
                        }
                        chatMessages.innerHTML += messageHtml;
                    });

                    chatMessages.scrollTop = chatMessages.scrollHeight;
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    // Only show error if this user is still selected
                    if (String(currentUserId) === String(userId)) {
                        const chatMessages = document.getElementById('chatMessages');
                        chatMessages.innerHTML = `
                    <div class="chat-welcome text-center mt-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h4>Error loading messages</h4>
                        <p class="text-muted">Please try again. Error: ${error.message}</p>
                    </div>
                `;
                    }
                });
        }

        function markAsRead(userId) {
            fetch(`{{ url('operation/chat/mark-read') }}/${userId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
        }
        // Update unread message counts (users and groups)
        function updateUnreadCounts() {
            fetch('/group-chat/groups')
                .then(res => res.json())
                .then(groups => {
                    groups.forEach(group => {
                        const badge = document.querySelector(`.chat-user-item.group-item[data-group-id="${group.id}"] .unread-count`);
                        if (badge) {
                            if (group.unread_count > 0) {
                                badge.textContent = group.unread_count;
                                badge.style.display = '';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                    });
                });
            fetch('{{ route('operation.chat.unread-counts') }}')
                .then(response => response.json())
                .then(counts => {
                    Object.entries(counts).forEach(([userId, count]) => {
                        const badge = document.querySelector(
                            `.chat-user-item[data-user-id="${userId}"] .unread-count`);
                        if (badge) {
                            if (count > 0) {
                                badge.textContent = count;
                                badge.style.display = '';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                    });
                });
        }
        // Poll for unread counts
        setInterval(updateUnreadCounts, 5000);

        function makeCall() {
            if (!confirm('Are you sure you want to make this call?')) return;
            const $btn = $('.call-btn');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: `/call-outbound/${currentUserMobile}`,
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
                    $btn.prop('disabled', false).html('<i class="fas fa-phone-alt"></i>');
                }
            });
        }

        // Group Chat: Fetch and render groups in user list
        function fetchGroups() {
            fetch('/group-chat/groups')
                .then(res => res.json())
                .then(groups => {
                    // Remove old group items and old heading
                    $('.chat-user-item.group-item').remove();
                    $('#usersList .group-heading').remove();
                    // Add group heading and group items after user items
                    if (groups.length > 0) {
                        $('#usersList').append('<div class="w-100 text-muted small px-3 mt-2 mb-1 group-heading">Groups</div>');
                    }
                    groups.forEach(group => {
                        const isCreator = group.created_by == {{ Auth::id() }};
                        $('#usersList').append(
                            `<div class="chat-user-item group-item d-flex align-items-center px-3 py-2"
                                data-group-id="${group.id}" style="background: #eaf6ff;">
                                <i class="fa fa-users me-2"></i>
                                <span class="fw-semibold flex-grow-1" style="font-size:14px;">${group.name}</span>
                                <span class="badge unread-count ms-2" style="${group.unread_count > 0 ? 'background: #dc3545; color: #fff; font-weight: bold;' : 'display: none;'}">${group.unread_count || 0}</span>
                                ${isCreator ? `<button class="delete-group-btn" data-group-id="${group.id}" data-group-name="${group.name}" title="Delete Group">
                                    <i class="fa fa-trash text-danger"></i>
                                </button>` : ''}
                            </div>`
                        );
                    });
                });
        }
        fetchGroups();

        // Handle group creation
        document.getElementById('createGroupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('groupName').value;
            const userIds = Array.from(document.getElementById('groupUsers').selectedOptions).map(opt => opt.value);
            if (userIds.length < 3) {
                document.getElementById('groupCreateError').textContent = 'Select at least 3 users.';
                return;
            }
            fetch('/group-chat/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ name, user_ids: userIds })
            })
            .then(res => res.json())
            .then(group => {
                var modal = bootstrap.Modal.getInstance(document.getElementById('createGroupModal'));
                if(modal) modal.hide();
                fetchGroups();
                document.getElementById('groupCreateError').textContent = '';
                document.getElementById('groupName').value = '';
                document.getElementById('groupUsers').selectedIndex = -1;
            })
            .catch(() => {
                document.getElementById('groupCreateError').textContent = 'Failed to create group.';
            });
        });

        // Click handler for group chat items
        $(document).on('click', '.chat-user-item.group-item', function(e) {
            e.preventDefault();
            const groupId = $(this).data('group-id');
            // If already selected, do nothing (but still start polling)
            if (currentGroupId == groupId) {
                // Still clear and restart polling
                if (messagePollingInterval) {
                    clearInterval(messagePollingInterval);
                    messagePollingInterval = null;
                }
                console.log('Polling restarted for group', groupId);
            } else {
                // Remove active from all, add to selected group
                document.querySelectorAll('.chat-user-item').forEach(function(item) {
                    item.classList.remove('active');
                });
                this.classList.add('active');
                // Set chat header
                $('#chatHeader').text($(this).find('span.fw-semibold').text());
                // Show group chat input
                $('#messageInput').prop('disabled', false);
                $('button[type="submit"]').prop('disabled', false);
                currentGroupId = groupId;
                currentUserId = null;
            }
            // Mark all group messages as read
            fetch(`/group-chat/mark-read/${groupId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(() => updateUnreadCounts());
            // Clear previous polling
            if (messagePollingInterval) {
                clearInterval(messagePollingInterval);
                messagePollingInterval = null;
            }
            // Function to load group messages
            function loadGroupMessages(scrollToBottom = false) {
                fetch('/group-chat/messages/' + groupId)
                    .then(res => res.json())
                    .then(messages => {
                        let html = '';
                        if (messages.length === 0) {
                            html = `<div class=\"chat-welcome text-center mt-5\">
                                <i class=\"fas fa-comments fa-3x text-muted mb-3\"></i>
                                <h4>No messages yet</h4>
                                <p class=\"text-muted\">Start the conversation!</p>
                            </div>`;
                        } else {
                            messages.forEach(msg => {
                                html += `<div class=\"message ${msg.sender_id == {{ Auth::id() }} ? 'sent' : 'received'}\">\n                                <div class=\"fw-bold\">${msg.sender.f_name}</div>\n                                <div class=\"message-content\">${msg.message}</div>\n                                <div class=\"message-time\">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>\n                            </div>`;
                            });
                        }
                        $('#chatMessages').html(html);
                        if (scrollToBottom) {
                            $('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight);
                        }
                    });
            }
            // Initial load
            loadGroupMessages(true);
            // Start polling for group messages
            messagePollingInterval = setInterval(() => {
                if (currentGroupId == groupId) {
                    loadGroupMessages();
                }
            }, 3000);
            console.log('Polling started for group', groupId);
            // Fetch group members and show, and show/hide add/remove buttons based on creator
            fetch('/group-chat/groups')
                .then(res => res.json())
                .then(groups => {
                    const group = groups.find(g => g.id == groupId);
                    if (group) {
                        lastGroupUsers = group.users.map(u => u.id);
                        const members = group.users.map(u => u.f_name).join(', ');
                        $('#groupMembers').text('Members: ' + members);
                        // Only show add/remove buttons if current user is creator
                        if (group.created_by == {{ Auth::id() }}) {
                            $('#addUserToGroupBtn').show().data('group-id', groupId);
                            $('#showGroupMembersBtn').show().data('group-id', groupId);
                        } else {
                            $('#addUserToGroupBtn').hide();
                            $('#showGroupMembersBtn').hide();
                        }
                    }
                });
        });

        // Open add user modal
        $('#addUserToGroupBtn').on('click', function() {
            // Only show users not already in group
            $('#addGroupUsers option').each(function() {
                if (lastGroupUsers.includes(parseInt($(this).val()))) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
            $('#addUserToGroupModal').modal('show');
        });

        // Handle add user to group form
        $('#addUserToGroupForm').on('submit', function(e) {
            e.preventDefault();
            const groupId = $('#addUserToGroupBtn').data('group-id');
            const userIds = $('#addGroupUsers').val();
            if (!userIds || userIds.length === 0) {
                $('#addUserGroupError').text('Select at least one user.');
                return;
            }
            $('#addUserGroupError').text('');
            Promise.all(userIds.map(userId =>
                fetch('/group-chat/add-user/' + groupId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ user_id: userId })
                })
            )).then(() => {
                $('#addUserToGroupModal').modal('hide');
                fetchGroups();
                // Wait a moment for the DOM to update, then trigger click
                setTimeout(function() {
                    $('.chat-user-item.group-item[data-group-id="' + groupId + '"]').click();
                }, 300);
                // Show notification
                if (typeof toastr !== 'undefined') {
                    toastr.success('User(s) added successfully!');
                } else {
                    alert('User(s) added successfully!');
                }
            });
        });

        // Open group members modal
        $('#showGroupMembersBtn').on('click', function() {
            const groupId = $(this).data('group-id');
            fetch('/group-chat/groups')
                .then(res => res.json())
                .then(groups => {
                    const group = groups.find(g => g.id == groupId);
                    if (group) {
                        $('#groupMembersCount').text(group.users.length);
                        let html = '<ul class="list-group">';
                        group.users.forEach(u => {
                            html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>${u.f_name} ${u.id == {{ Auth::id() }} ? '(You)' : ''}</span>
                                ${(group.created_by == {{ Auth::id() }} && u.id != {{ Auth::id() }}) ? `<button class="btn btn-danger btn-sm remove-member-btn" data-user-id="${u.id}" data-group-id="${groupId}">Remove</button>` : ''}
                            </li>`;
                        });
                        html += '</ul>';
                        $('#groupMembersList').html(html);
                        $('#groupMembersModal').modal('show');
                    }
                });
        });

        // Remove member from group
        $(document).on('click', '.remove-member-btn', function() {
            const userId = $(this).data('user-id');
            const groupId = $(this).data('group-id');
            if (confirm('Remove this user from the group?')) {
                fetch('/group-chat/remove-user/' + groupId, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(res => res.json())
                .then(data => {
                    // Refresh group members modal and group list
                    $('#showGroupMembersBtn').click();
                    fetchGroups();
                    if (typeof toastr !== 'undefined') {
                        toastr.success('User removed from group!');
                    }
                });
            }
        });

        // Delete group functionality
        $(document).on('click', '.delete-group-btn', function(e) {
            e.stopPropagation(); // Prevent group selection when clicking delete
            const groupId = $(this).data('group-id');
            const groupName = $(this).data('group-name');

            if (confirm(`Are you sure you want to delete the group "${groupName}"? This action cannot be undone.`)) {
                fetch('/group-chat/delete/' + groupId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Remove the group from the list
                        $(`.chat-user-item.group-item[data-group-id="${groupId}"]`).remove();

                        // If this group was currently selected, clear the chat
                        if (currentGroupId == groupId) {
                            currentGroupId = null;
                            $('#chatHeader').text('Select a user or group to start chatting');
                            $('#chatMessages').html(`
                                <div class="chat-welcome text-center mt-5">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <h4>Welcome to Chat</h4>
                                    <p class="text-muted">Select a user or group to start chatting</p>
                                </div>
                            `);
                            $('#messageInput').prop('disabled', true);
                            $('button[type="submit"]').prop('disabled', true);
                            $('#addUserToGroupBtn').hide();
                            $('#showGroupMembersBtn').hide();
                        }

                        // Show success message
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Group deleted successfully!');
                        } else {
                            alert('Group deleted successfully!');
                        }
                    } else {
                        throw new Error(data.message || 'Failed to delete group');
                    }
                })
                .catch(error => {
                    console.error('Error deleting group:', error);
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Failed to delete group. Please try again.');
                    } else {
                        alert('Failed to delete group. Please try again.');
                    }
                });
            }
        });

        // Attachment button click opens file input
        document.getElementById('attachmentBtn').addEventListener('click', function() {
            document.getElementById('attachmentInput').click();
        });

        // File input change: preview the file
        document.getElementById('attachmentInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            let previewHtml = '';
            if (file.type.startsWith('image/')) {
                previewHtml = `<img src="${URL.createObjectURL(file)}" class="img-fluid" style="max-height:300px;" alt="Image Preview">`;
            } else if (file.type.startsWith('video/')) {
                previewHtml = `<video controls style="max-width:100%;max-height:300px;"><source src="${URL.createObjectURL(file)}"></video>`;
            } else {
                previewHtml = `<div class="mb-2"><i class="fa fa-file fa-3x text-primary"></i></div>\n                       <div>${file.name}</div>`;
            }
            document.getElementById('attachmentPreviewBody').innerHTML = previewHtml;
            var modal = new bootstrap.Modal(document.getElementById('attachmentPreviewModal'));
            modal.show();
        });

        // Send attachment
        document.getElementById('sendAttachmentBtn').addEventListener('click', function() {
            const fileInput = document.getElementById('attachmentInput');
            const file = fileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('attachment', file);

            // Add receiver info
            if (currentUserId) {
                formData.append('receiver_id', currentUserId);
                var url = '{{ route('operation.chat.send.attachment') }}';
            } else {
                toastr.error('Select a user first!');
                return;
            }

            // Add CSRF token
            formData.append('_token', '{{ csrf_token() }}');

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Attachment sent!');
                    loadMessages(currentUserId);
                } else {
                    toastr.error(data.message || 'Failed to send attachment');
                }
            })
            .catch(() => {
                toastr.error('Failed to send attachment');
            })
            .finally(() => {
                // Reset file input and close modal
                fileInput.value = '';
                var modal = bootstrap.Modal.getInstance(document.getElementById('attachmentPreviewModal'));
                if (modal) modal.hide();
            });
        });

        // --- MOBILE CHAT TOGGLE LOGIC ---
        function showChatMobile() {
            if (window.innerWidth <= 480) {
                document.querySelector('.mobile-chat-wrapper').classList.add('show-chat');
                document.querySelector('.mobile-chat-wrapper').classList.remove('show-list');
            }
        }
        function showUserListMobile() {
            if (window.innerWidth <= 480) {
                document.querySelector('.mobile-chat-wrapper').classList.add('show-list');
                document.querySelector('.mobile-chat-wrapper').classList.remove('show-chat');
            }
        }
        if (window.innerWidth <= 480) {
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.mobile-chat-wrapper').classList.add('show-list');
                document.querySelector('.mobile-chat-wrapper').classList.remove('show-chat');
            });
        }
        const usersList2 = document.getElementById('usersList');
        if (usersList2) {
            usersList2.addEventListener('click', function(e) {
                // ... existing user click logic ...
                showChatMobile();
            });
        }
        window.addEventListener('resize', function() {
            if (window.innerWidth > 480) {
                document.querySelector('.mobile-chat-wrapper').classList.remove('show-list', 'show-chat');

            }
        });
    </script>
@endsection
