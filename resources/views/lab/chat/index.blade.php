@extends('lab.layouts.app')

@section('main')
<link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <div class="content-wrapper">
        <div class="container-fluid d-flex justify-content-center align-items-start">
            <div class="chat-card row w-100 justify-content-center align-items-stretch"
                style="overflow: hidden;">
                <!-- Sidebar: User List -->
                <div class="col-md-4 col-lg-4 chat-sidebar p-0 d-flex flex-column border-end"
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
                        </div>
                    </div>

                    <div class="chat-user-list overflow-auto flex-grow-1" id="usersList" style="background: #fff;">
                        @foreach ($users as $user)
                            <div href="#" class="chat-user-item d-flex align-items-center"
                                data-user-id="{{ $user->id }}" data-mobile="{{ $user->mobile }}"
                                style="text-decoration:none;">
                                <i class="fa fa-star favorite-star me-2" data-user-id="{{ $user->id }}" style="color: #ccc; cursor:pointer; font-size: 16px;"></i>
                                @if ($user->profile_image)
                                    <div class="position-relative" style="display:inline-block;">
                                        <img src="{{ asset('storage/' . $user->profile_image) }}"
                                            class="user-avatar" height="50" width="50"
                                            alt="{{ $user->f_name }}">
                                        @if($user->last_online && \Carbon\Carbon::parse($user->last_online)->gt(now()->subMinutes(3)))
                                            <span class="position-absolute"
                                                  style="right: 2px; bottom: 2px; width: 12px; height: 12px; background: #25d366; border: 2px solid #fff; border-radius: 50%;"></span>
                                        @endif
                                    </div>
                                @else
                                    <div class="position-relative" style="display:inline-block;">
                                        <div class="user-initial-avatar"
                                            style="background: {{ $user->color ?? '#25d366' }};">
                                            {{ strtoupper(substr($user->f_name, 0, 1)) }}
                                        </div>
                                        @if($user->last_online && \Carbon\Carbon::parse($user->last_online)->gt(now()->subMinutes(3)))
                                            <span class="position-absolute"
                                                  style="right: 2px; bottom: 2px; width: 12px; height: 12px; background: #25d366; border: 2px solid #fff; border-radius: 50%;"></span>
                                        @endif
                                    </div>
                                @endif
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold user-name"
                                            style="font-size:16px; color: #111;">{{ $user->f_name }}</span>
                                        <small class="text-muted last-message-time" style="font-size:12px; color: #667781;" data-last-message-time="{{ $user->last_message ? $user->last_message->created_at : $user->created_at }}">
                                            @if($user->last_message)
                                                {{ \Carbon\Carbon::parse($user->last_message->created_at)->addHours(5)->addMinutes(30)->format('h:i A') }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="text-muted small last-message-preview"
                                        style="font-size:13px; white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px; color: #667781;">
                                        @if($user->last_message)
                                            @php
                                                $isMe = $user->last_message->sender_id == Auth::id();
                                                $msgPrefix = $isMe ? 'You: ' : '';
                                                if ($user->last_message->attachment) {
                                                    $msgText = '[Attachment]';
                                                } else {
                                                    $msgText = $user->last_message->message;
                                                }
                                            @endphp
                                            <span>{{ $msgPrefix }}{{ $msgText }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="badge unread-count ms-2" style="display: none; background: #25d366; color: #fff; font-weight: bold; border-radius: 10px; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px;">0</span>
                            </div>
                        @endforeach
                        <!-- Group items will be appended here by JS -->
                    </div>
                </div>
                <!-- Main Chat Area -->
                <div class="col-md-8 col-lg-8 chat-main p-0 d-flex flex-column" style="background: #fff; display: flex;">
                    <div class="chat-main-inner d-flex flex-column" style="height:100%;">
                        <div class="chat-header d-flex align-items-center px-2 py-2 border-bottom bg-white">
                            <button class="back-btn" style="display:none;" id="chatBackBtn" type="button"><i class="fa fa-arrow-left"></i></button>
                            <div class="position-relative me-3">
                                <img src="" alt="" class="rounded-circle chat-user-avatar"
                                    style="width: 44px; height: 44px;">
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold mb-0" id="chatHeader">Select a user to start chatting</div>
                                <div id="groupMembers" class="small text-muted"></div>
                            </div>
                            {{-- <button class="btn btn-light rounded-circle call-btn"
                                style="width: 40px; height: 40px; display: none;" onclick="makeCall()">
                                <i class="fas fa-phone-alt"></i>
                            </button> --}}
                            <button id="addUserToGroupBtn" class="btn btn-sm btn-outline-primary ms-2" style="display:none;">Add User</button>
                            <button id="showGroupMembersBtn" class="btn btn-sm btn-outline-info ms-2" style="display:none;">Users</button>
                            <button id="renameGroupBtn" class="btn btn-sm btn-outline-warning ms-2" style="display:none;" title="Rename Group">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button id="chatSearchBtn" class="btn btn-sm btn-outline-secondary ms-2" style="display:none;" title="Search Messages">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                        
                        <!-- Chat Search Bar -->
                        <div id="chatSearchBar" class="chat-search-bar px-3 py-2 border-bottom bg-light" style="display:none;">
                            <div class="d-flex align-items-center">
                                <input type="text" id="chatSearchInput" class="form-control me-2" placeholder="Search messages..." style="border-radius: 20px; border: 1px solid #ddd; padding: 8px 16px;">
                                <button id="chatSearchPrev" class="btn btn-sm btn-outline-secondary me-1" title="Previous">
                                    <i class="fa fa-chevron-up"></i>
                                </button>
                                <button id="chatSearchNext" class="btn btn-sm btn-outline-secondary me-2" title="Next">
                                    <i class="fa fa-chevron-down"></i>
                                </button>
                                <button id="chatSearchClose" class="btn btn-sm btn-outline-danger" title="Close Search">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                            <div id="chatSearchResults" class="small text-muted mt-1" style="display:none;"></div>
                        </div>
                        <div class="chat-messages flex-grow-1 px-4 py-3" id="chatMessages">
                            <div class="chat-welcome text-center mt-5">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h4>Welcome to Chat</h4>
                                <p class="text-muted">Select a user to start chatting</p>
                            </div>
                        </div>
                        <!-- Reply Preview Box -->
                        <div id="replyPreview" class="reply-preview-box" style="display:none;">
                            <div class="d-flex align-items-center justify-content-between px-3 py-2 bg-light border-top">
                                <div class="flex-grow-1">
                                    <small class="text-muted d-block"><i class="fa fa-reply me-1"></i>Replying to <span id="replyToUser"></span></small>
                                    <div id="replyToMessage" class="text-truncate small" style="max-width: 400px;"></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-link text-danger" id="cancelReply">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chat-input border-top px-4 py-3">
                            <form id="messageForm" class="d-flex align-items-center">
                                <input type="hidden" id="receiverId" name="receiver_id">
                                <input type="hidden" id="replyToMessageId" name="reply_to_id">
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
                        <label for="groupUsers" class="form-label">Select Users (min 1)</label>
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

    <!-- Rename Group Modal -->
    <div class="modal fade" id="renameGroupModal" tabindex="-1" aria-labelledby="renameGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="renameGroupForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="renameGroupModalLabel">Rename Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newGroupName" class="form-label">New Group Name</label>
                        <input type="text" class="form-control" id="newGroupName" name="name" required maxlength="255">
                    </div>
                    <div id="renameGroupError" class="text-danger small"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Rename</button>
                </div>
            </form>
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

    <!-- Image Zoom Modal -->
    <div id="imageZoomModal" class="image-zoom-modal" style="display:none;">
        <span class="image-zoom-close">&times;</span>
        <img class="image-zoom-content" id="zoomedImage">
    </div>

    <style>
        body,
        .content-wrapper {
            background: #e5ddd5 !important;
        }

        .chat-card {
            background: transparent;
            overflow: hidden;
            height: 83vh;
            border-radius: 0;
        }

        .chat-sidebar {
            background: #fff;
            border-right: 1px solid #e9edef;
            border-radius: 0;
            min-width: 270px;
            max-width: 340px;
            padding: 0;
        }

        .chat-sidebar-header {
            background: #f0f2f5;
            border-bottom: 1px solid #e9edef;
            padding: 16px 20px;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        #searchUser {
            border-radius: 20px;
            border: none;
            background: #fff;
            padding: 8px 16px;
            font-size: 14px;
            margin-left: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        #searchUser:focus {
            outline: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }

        .chat-user-list {
            overflow-y: auto;
            flex-grow: 1;
            background: #fff;
            padding: 0;
            height: 40vh; /* Adjust for sticky header height */
        }

        .chat-user-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 0;
            margin: 0;
            cursor: pointer;
            transition: background 0.2s ease;
            border: none;
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
        }

        .chat-user-item:last-child {
            border-bottom: none;
        }

        .chat-user-item.active,
        .chat-user-item:hover {
            background: #f5f5f5;
            box-shadow: none;
        }

        .chat-user-item.active {
            background: #e7f3ff;
        }

        .user-avatar,
        .user-initial-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
            background: #25d366;
            color: #fff;
            font-weight: 600;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .user-initial-avatar {
            background: #25d366;
        }

        .group-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #128c7e;
            color: #fff;
            font-weight: 600;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .chat-main {
            background: #e5ddd5;
            border-radius: 0;
            display: flex;
            flex-direction: column;
            padding: 0;
            height: 100%;
            overflow-y: auto;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23ffffff" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="%23ffffff" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="%23ffffff" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="%23ffffff" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="%23e5ddd5"/><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        .chat-header {
            background: #f0f2f5;
            border-bottom: 1px solid #e9edef;
            padding: 16px 20px;
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
            background: transparent;
            overflow-y: auto;
            flex-grow: 1;
            padding: 20px 20px 16px 20px;
            min-height: 350px;
            max-height: 60vh;
            display: flex;
            flex-direction: column;
            font-size: 14px;
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
            padding: 8px 12px;
            border-radius: 7.5px;
            position: relative;
            box-shadow: 0 1px 0.5px rgba(0, 0, 0, 0.13);
            font-size: 14px;
            background: #fff;
            color: #303030;
            word-wrap: break-word;
        }

        .message.sent .message-content {
            background-color: #d9fdd3;
            color: #303030;
            border-bottom-right-radius: 0;
        }

        .message.received .message-content {
            background-color: #fff;
            color: #303030;
            border-bottom-left-radius: 0;
        }

        .message-time {
            font-size: 11px;
            color: #667781;
            margin-top: 4px;
            text-align: right;
        }

        .chat-input {
            background-color: #f0f2f5;
            border-top: 1px solid #e9edef;
            padding: 16px 20px;
        }

        #messageInput {
            border-radius: 21px;
            border: none;
            padding: 12px 16px;
            font-size: 15px;
            min-height: 42px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        #messageInput:focus {
            outline: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }

        .chat-input .btn {
            border-radius: 50%;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            background: #25d366;
            color: #fff;
            border: none;
            margin-left: 8px;
            transition: background 0.2s;
        }

        .chat-input .btn:hover {
            background: #128c7e;
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
            /* Hide chat-main-inner unless .show-chat is present */
            .chat-main-inner {
                display: none !important;
            }
            .chat-card.show-chat .chat-main-inner {
                display: flex !important;
                flex-direction: column;
                height: 100%;
            }
        }

        @media (max-width: 767px) {
            .chat-card {
                min-height: 100vh;
            }

            .chat-sidebar,
            .chat-main {
                border-radius: 0;
                box-shadow: none;
            }

            .chat-user-list {
                min-height: 400px;
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
            background: #fff;
            border-left: none;
        }

        .chat-user-item.group-item .fa-users {
            color: #128c7e;
        }

        .chat-user-item.group-item:hover {
            background: #f5f5f5;
        }

        .chat-user-item.group-item.active {
            background: #e7f3ff;
        }

        .delete-group-btn {
            background: none;
            border: none;
            padding: 8px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s ease;
            opacity: 0;
            margin-left: auto;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .delete-group-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
            opacity: 1;
        }

        .delete-group-btn .fa-trash {
            font-size: 14px;
            color: #dc3545;
            transition: color 0.2s ease;
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

        /* Image Zoom Modal Styles */
        .image-zoom-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            padding-top: 50px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.95);
            animation: zoomIn 0.3s;
        }

        @keyframes zoomIn {
            from {opacity: 0}
            to {opacity: 1}
        }

        .image-zoom-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 85vh;
            object-fit: contain;
            animation: zoomImage 0.3s;
        }

        @keyframes zoomImage {
            from {transform: scale(0)}
            to {transform: scale(1)}
        }

        .image-zoom-close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 50px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
            z-index: 10000;
            background: rgba(0,0,0,0.5);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .image-zoom-close:hover,
        .image-zoom-close:focus {
            color: #bbb;
            background: rgba(0,0,0,0.8);
            transform: scale(1.1);
        }

        .chat-message-image {
            cursor: pointer;
            transition: transform 0.2s;
        }

        .chat-message-image:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        /* Responsive text hiding */
        @media (max-width: 768px) {
            .image-zoom-close {
                top: 10px;
                right: 10px;
                font-size: 40px;
                width: 50px;
                height: 50px;
            }
        }

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
            border-radius: 10px;
            min-width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            padding: 0 6px;
        }

        /* Chat Search Styles */
        .chat-search-bar {
            background: #f8f9fa;
            border-bottom: 1px solid #e9edef;
            transition: all 0.3s ease;
        }

        #chatSearchInput {
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 14px;
            background: #fff;
            transition: all 0.2s ease;
        }

        #chatSearchInput:focus {
            outline: none;
            border-color: #25d366;
            box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.2);
        }

        .search-highlight {
            background-color: #007bff;
            color: #ffffff;
            padding: 1px 3px;
            border-radius: 2px;
            font-weight: normal;
        }

        .search-result-active {
            background-color: #007bff !important;
            color: #ffffff !important;
            border-radius: 3px;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.3);
            animation: searchPulse 1.5s ease-in-out infinite;
        }

        .search-result-active .search-highlight {
            background-color: #0056b3;
            color: #ffffff;
            font-weight: bold;
        }

        @keyframes searchPulse {
            0% { 
                background-color: #007bff;
                box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.3);
            }
            50% { 
                background-color: #0056b3;
                box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.5);
            }
            100% { 
                background-color: #007bff;
                box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.3);
            }
        }

        .chat-search-bar .btn {
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        #chatSearchResults {
            font-size: 12px;
            color: #6c757d;
        }
        .hide-user-item { display: none !important; }

        /* Reply Feature Styles */
        .reply-preview-box { background: #f0f2f5; border-top: 2px solid #25d366; animation: slideDown 0.2s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .message-reply-btn { opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px; }
        .message:hover .message-reply-btn { opacity: 1; }
        .message-reply-btn:hover { background: rgba(0,0,0,0.1); color: #25d366; }
        .replied-message { background: rgba(37, 211, 102, 0.1); border-left: 3px solid #25d366; padding: 8px 12px; margin-bottom: 8px; border-radius: 4px; font-size: 13px; cursor: pointer; transition: background 0.2s; }
        .replied-message:hover { background: rgba(37, 211, 102, 0.2); }
        .replied-message-sender { color: #25d366; font-weight: 600; font-size: 12px; margin-bottom: 4px; }
        .replied-message-text { color: #667781; font-size: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .message-highlight { animation: highlightPulse 2s ease; }
        @keyframes highlightPulse { 0% { background-color: transparent; } 10% { background-color: rgba(247, 148, 29, 0.3); transform: scale(1.02); } 50% { background-color: rgba(247, 148, 29, 0.2); } 100% { background-color: transparent; transform: scale(1); } }

        @media (max-width: 767px) {
            .chat-card {
                flex-direction: column !important;
                min-height: 400px;
                height: auto;
            }
            .chat-sidebar {
                width: 100vw;
                max-width: 100vw;
                position: static;
                left: 0;
                top: 0;
                height: auto;
                z-index: 1;
                display: block;
            }
            .chat-main {
                width: 100vw;
                max-width: 100vw;
                border-radius: 0;
                height: auto;
                display: none;
            }
            .chat-card.show-chat .chat-sidebar {
                display: none !important;
            }
            .chat-card.show-chat .chat-main {
                display: flex !important;
            }
            .chat-header .back-btn {
                display: inline-block !important;
                margin-right: 10px;
                background: none;
                border: none;
                font-size: 22px;
                color: #333;
            }
        }
        @media (min-width: 768px) {
            .chat-header .back-btn {
                display: none !important;
            }
        }
        @media (max-width: 480px) {
            .chat-card {
                min-height: 200px;
            }
            .chat-sidebar, .chat-main {
                width: 100%;
                max-width: 100% !important;
                padding: 0 !important;
            }
            .chat-header {
                font-size: 13px;
                padding: 8px 4px;
            }
            .chat-user-item {
                font-size: 11px;
                padding: 7px 6px;
            }
            .user-avatar, .user-initial-avatar, .chat-user-avatar {
                width: 28px;
                height: 28px;
                font-size: 0.9em;
                margin-right: 7px;
            }
            .action-btn, .favorites-btn, .groups-btn, .create-group-btn {
                padding: 4px 7px;
                font-size: 10px;
            }
            .chat-messages {
                padding: 6px 2px 4px 2px;
                font-size: 9px;
            }
            .chat-input {
                padding: 6px 2px;
            }
            #messageInput {
                font-size: 11px;
                padding: 8px 10px;
                min-height: 32px;
            }
            .chat-input .btn {
                width: 32px;
                height: 32px;
                font-size: 1em;
                margin-left: 4px;
            }
            .chat-header .back-btn {
                font-size: 18px;
                margin-right: 5px;
            }
            .badge.unread-count {
                font-size: 10px !important;
                padding: 2px 5px !important;
            }
        }
    </style>
    <script>
        let currentUserId = null;
        let currentUserMobile = null;
        let messagePollingInterval = null;
        let currentGroupId = null; // Track current group for group chat
        let lastGroupUsers = [];
        let replyToMessage = null; // Track message being replied to
        let userHasScrolledManually = false; // Track manual scroll to prevent auto-scroll

        $(document).ready(function() {
            // SEARCH FUNCTIONALITY
            $('#searchUser').on('input', function() {
                const searchText = $(this).val().toLowerCase();
                $('#usersList .chat-user-item').each(function() {
                    if ($(this).hasClass('group-item')) {
                        // For group items, search by group name
                        const groupNameElem = $(this).find('.fw-semibold');
                        if (groupNameElem.length) {
                            const groupName = groupNameElem.text().toLowerCase();
                            if (groupName.includes(searchText)) {
                                $(this).removeClass('hide-user-item');
                            } else {
                                $(this).addClass('hide-user-item');
                            }
                        }
                        return;
                    }
                    const userNameElem = $(this).find('.user-name');
                    if (userNameElem.length) {
                        const userName = userNameElem.text().toLowerCase();
                        if (userName.includes(searchText)) {
                            $(this).removeClass('hide-user-item');
                        } else {
                            $(this).addClass('hide-user-item');
                        }
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Convert all initial user times to IST
            document.querySelectorAll('.chat-user-item:not(.group-item) .last-message-time').forEach(function(timeElement) {
                const originalTime = timeElement.getAttribute('data-last-message-time');
                if (originalTime && timeElement.textContent.trim()) {
                    const date = new Date(originalTime);
                    date.setHours(date.getHours() + 5);
                    date.setMinutes(date.getMinutes() + 30);
                    timeElement.textContent = date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                }
            });

            // Search users
            const searchInput = document.getElementById('searchUser');
            searchInput.addEventListener('input', function() {
                const searchText = this.value.toLowerCase();
                document.querySelectorAll('.chat-user-item').forEach(function(item) {
                    let userName = '';
                    const userNameElem = item.querySelector('.user-name');
                    if (userNameElem) {
                        userName = userNameElem.textContent.toLowerCase();
                    } else {
                        userName = item.textContent.toLowerCase();
                    }
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
                const submitButton = document.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = false;
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
                setUserUnreadCount(userId, 0);
                markAsRead(userId);

                const callBtn = document.querySelector('.call-btn');
                if (callBtn) {
                    callBtn.style.display = 'flex';
                }

                currentGroupId = null; // Not in group chat
                
                // Hide group management buttons
                $('#addUserToGroupBtn').hide();
                $('#showGroupMembersBtn').hide();
                $('#renameGroupBtn').hide();
                $('#groupMembers').text('');
                
                // Clear reply when switching users
                cancelReply();
                userHasScrolledManually = false;
                
                // Show search button
                toggleSearchButton();
            });

            // Handle message sending
            document.getElementById('messageForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const message = document.getElementById('messageInput').value;
                if (!message.trim()) return;

                // Group chat
                if (currentGroupId) {
                    const replyToId = document.getElementById('replyToMessageId').value;
                    const requestBody = { message };
                    if (replyToId) {
                        requestBody.reply_to_id = replyToId;
                    }
                    fetch('/group-chat/send/' + currentGroupId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(requestBody)
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('messageInput').value = '';
                        cancelReply(); // Clear reply after sending
                        userHasScrolledManually = false;
                        // Reload group messages
                        if (currentGroupId) {
                            loadGroupMessages(true);
                        }
                        // Refresh the sorted chat list to update positions
                        setTimeout(() => {
                            fetchGroups();
                        }, 500);
                    });
                }
                // User-to-user chat
                else if (currentUserId) {
                    const receiverId = document.getElementById('receiverId').value;
                    const replyToId = document.getElementById('replyToMessageId').value;
                    let bodyData = `receiver_id=${encodeURIComponent(receiverId)}&message=${encodeURIComponent(message)}`;
                    if (replyToId) {
                        bodyData += `&reply_to_id=${encodeURIComponent(replyToId)}`;
                    }
                    fetch('{{ route('lab.chat.send') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: bodyData
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('messageInput').value = '';
                        cancelReply(); // Clear reply after sending
                        userHasScrolledManually = false;
                        if (String(currentUserId) === String(receiverId)) {
                            loadMessages(currentUserId);
                            setUserUnreadCount(receiverId, 0);
                        }
                        updateUnreadCounts();
                        // Refresh the sorted chat list to update positions
                        setTimeout(() => {
                            fetchGroups();
                        }, 500);
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

            // GROUP FAVORITE SYSTEM
            function updateGroupFavoriteStars(favorites) {
                console.log('updateGroupFavoriteStars called with', favorites);
                document.querySelectorAll('.group-item').forEach(item => {
                    var star = item.querySelector('.favorite-group-star');
                    var groupId = parseInt(item.getAttribute('data-group-id'));
                    if (star && favorites.includes(groupId)) {
                        star.style.color = 'gold';
                        star.classList.add('favorited');
                        star.setAttribute('data-favorited', '1');
                        item.classList.add('is-favorite-group');
                    } else {
                        if (star) {
                            star.style.color = '#ccc';
                            star.classList.remove('favorited');
                            star.setAttribute('data-favorited', '0');
                        }
                        item.classList.remove('is-favorite-group');
                    }
                });
                console.log('Favorite groups:', $('.group-item.is-favorite-group').map(function(){return $(this).data('group-id');}).get());
            }

            // Show only favorites in the user list and groups
            function showOnlyFavorites() {
                var $all = $('.chat-user-item').not('.group-item');
                var $fav = $('.chat-user-item.is-favorite').not('.group-item');
                var $allGroups = $('.group-item');
                var $favGroups = $('.group-item.is-favorite-group');
                // Hide all users and groups first
                $all.each(function() {
                    this.style.setProperty('display', 'none', 'important');
                });
                $allGroups.each(function() {
                    this.style.setProperty('display', 'none', 'important');
                });
                
                // Show only favorite users and groups
                $fav.each(function() {
                    this.style.setProperty('display', 'flex', 'important');
                });
                $favGroups.each(function() {
                    this.style.setProperty('display', 'flex', 'important');
                });
                if ($fav.length > 0 || $favGroups.length > 0) {
                    $('#noFavoritesMsg').remove();
                } else {
                    if ($('#noFavoritesMsg').length === 0) {
                        $('.chat-user-list').append('<div id="noFavoritesMsg" class="text-center text-muted py-4">No favorite users or groups</div>');
                    }
                }
                console.log('showOnlyFavorites: showing', $fav.length, 'favorite users and', $favGroups.length, 'favorite groups');
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
                    Promise.all([
                        fetch('/lab/chat/favorites').then(res => res.json()),
                        fetch('/lab/chat/group-favorites').then(res => res.json())
                    ])
                    .then(([userFavorites, groupFavorites]) => {
                        updateFavoriteStars(userFavorites);
                        updateGroupFavoriteStars(groupFavorites);
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
                fetch('/lab/chat/favorite/' + userId, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
                })
                .then(res => res.json())
                .then(data => {
                    Promise.all([
                        fetch('/lab/chat/favorites').then(res => res.json()),
                        fetch('/lab/chat/group-favorites').then(res => res.json())
                    ])
                    .then(([userFavorites, groupFavorites]) => {
                        updateFavoriteStars(userFavorites);
                        updateGroupFavoriteStars(groupFavorites);
                        if (filterActive) {
                            showOnlyFavorites();
                        }
                    });
                });
            });

            // On group star click, toggle group favorite
            $(document).on('click', '.favorite-group-star', function(e) {
                e.stopPropagation();
                var groupId = $(this).data('groupId');
                fetch('/lab/chat/group-favorite/' + groupId, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
                })
                .then(res => res.json())
                .then(data => {
                    fetch('/lab/chat/group-favorites')
                        .then(res => res.json())
                        .then(favorites => {
                            updateGroupFavoriteStars(favorites);
                            // If favorites filter is active, refresh the view
                            if (filterActive) {
                                showOnlyFavorites();
                            }
                        });
                });
            });

            // On page load, fetch and update favorites (but don't filter)
            fetch('/lab/chat/favorites')
                .then(res => res.json())
                .then(favorites => {
                    updateFavoriteStars(favorites);
                });

            // On page load, fetch and update group favorites
            fetch('/lab/chat/group-favorites')
                .then(res => res.json())
                .then(favorites => {
                    updateGroupFavoriteStars(favorites);
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
            const apiUrl = `{{ url('lab/chat/messages') }}/${userId}`;
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
                    
                    // Store current scroll position if search is active
                    let shouldPreserveScroll = isSearchActive;
                    let currentScrollTop = shouldPreserveScroll ? chatMessages.scrollTop : 0;
                    
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
                        let repliedHtml = '';
                        if (message.replied_to) {
                            const repliedText = message.replied_to.message || '[Attachment]';
                            const repliedSender = message.replied_to.sender ? message.replied_to.sender.f_name : 'Unknown';
                            repliedHtml = `<div class="replied-message" onclick="scrollToOriginalMessage(${message.reply_to_id})"><div class="replied-message-sender">${repliedSender}</div><div class="replied-message-text">${repliedText}</div></div>`;
                        }
                        let messageHtml = '';
                        if (message.attachment) {
                            let attachmentHtml = '';
                            if (message.attachment_type && message.attachment_type.startsWith('image/')) {
                                attachmentHtml = `
                                    <div style="position:relative; display:inline-block;">
                                        <img src="/storage/${message.attachment}" class="img-fluid chat-message-image" style="max-width:200px;max-height:200px; border-radius:8px;" alt="Attachment" title="Click to zoom">
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
                                // Use original filename if available, otherwise extract from path
                                const displayName = message.original_filename || message.attachment.split('/').pop();
                                attachmentHtml = `
                                    <div style="background:#e6f0ff; border-radius:8px; padding:16px; display:flex; align-items:center; gap:12px; color:#000;">
                                        <i class="fa fa-file fa-2x text-primary"></i>
                                        <span style="flex:1;">${displayName}</span>
                                        <a href="/storage/${message.attachment}" download="${displayName}" class="btn btn-sm btn-primary d-flex align-items-center" style="background:#F7941D; color:#fff;">
                                            <i class="fa fa-download me-1"></i> Download
                                        </a>
                                    </div>
                                `;
                            }
                            const senderName = isSent ? 'You' : chatHeader.textContent;
                            const msgTextReply = '[Attachment]';
                            messageHtml = `
                        <div class="message ${isSent ? 'sent' : 'received'}" data-message-id="${message.id}">
                            <div class="message-content">${repliedHtml}${attachmentHtml}</div>
                            <div class="message-time">${messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                            ${seenHtml}
                            <button class="message-reply-btn" data-message-id="${message.id}" data-sender-name="${senderName}" data-message-text="${msgTextReply}"><i class="fa fa-reply"></i> Reply</button>
                        </div>
                    `;
                        } else {
                            const senderName = isSent ? 'You' : chatHeader.textContent;
                            messageHtml = `
                        <div class=\"message ${isSent ? 'sent' : 'received'}\" data-message-id=\"${message.id}\">
                            <div class=\"message-content ${isSent ? 'sent-bg' : 'received-bg'}\">${repliedHtml}${message.message}</div>
                            <div class=\"message-time\">${messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                            ${seenHtml}
                            <button class=\"message-reply-btn\" data-message-id=\"${message.id}\" data-sender-name=\"${senderName}\" data-message-text=\"${message.message}\"><i class=\"fa fa-reply\"></i> Reply</button>
                        </div>
                    `;
                        }
                        chatMessages.innerHTML += messageHtml;
                    });

                    // Only auto-scroll if search is not active
                    if (!isSearchActive && !userHasScrolledManually) {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    } else {
                        // Restore search state after messages are loaded
                        restoreSearchState();
                    }
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
            fetch(`{{ url('lab/chat/mark-read') }}/${userId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
        }
        // Update unread message counts
                function setUserUnreadCount(userId, count) {
            const badge = document.querySelector(`.chat-user-item[data-user-id="${userId}"] .unread-count`);
            if (!badge) return;
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = '';
            } else {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        }

        function setGroupUnreadCount(groupId, count) {
            const badge = document.querySelector(`.chat-user-item.group-item[data-group-id="${groupId}"] .unread-count`);
            if (!badge) return;
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = '';
            } else {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        }

        function markVisibleSentMessagesSeen(readerId) {
            if (!currentUserId) return;
            if (readerId && String(readerId) !== String(currentUserId)) return;
            document.querySelectorAll('#chatMessages .message.sent').forEach(msgEl => {
                if (!msgEl.querySelector('.message-seen')) {
                    msgEl.insertAdjacentHTML('beforeend', '<div class="message-seen text-end" style="font-size: 0.8em; color: #28c76f;">Seen</div>');
                }
            });
        }

function updateUnreadCounts() {
            fetch('{{ route('lab.chat.unread-counts') }}')
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
        // Poll for unread counts and refresh sorted list
        setInterval(() => {
            updateUnreadCounts();
            // Refresh sorted list every 30 seconds to catch new messages
            fetchGroups();
        }, 30000);

        function makeCall() {
            if (!currentUserMobile) return;
            if (!confirm('Are you sure you want to call this number?')) return;
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
                    // Store groups data for sorting
                    window.groupsData = groups;

                    // Re-render the entire list with proper sorting
                    renderSortedChatList();

                    // Update group favorites after rendering
                    fetch('/lab/chat/group-favorites')
                        .then(res => res.json())
                        .then(favorites => {
                            updateGroupFavoriteStars(favorites);
                        });
                });
        }

        // Function to render the entire chat list sorted by last message time
        function renderSortedChatList() {
            const usersList = document.getElementById('usersList');
            const existingUserItems = Array.from(document.querySelectorAll('.chat-user-item:not(.group-item)'));
            const groupsData = window.groupsData || [];

            // Create array of all chat items (users + groups) with their last message times
            let allChats = [];

            // Add individual users
            existingUserItems.forEach(userItem => {
                const userId = userItem.getAttribute('data-user-id');
                const userName = userItem.querySelector('.user-name').textContent;
                const lastMessageTime = userItem.querySelector('.last-message-time');
                const lastMessagePreview = userItem.querySelector('.last-message-preview');

                // Get last message time from the user data (we'll need to pass this from backend)
                const lastMessageTimeText = lastMessageTime ? lastMessageTime.textContent : '';
                const lastMessagePreviewText = lastMessagePreview ? lastMessagePreview.textContent : '';

                allChats.push({
                    type: 'user',
                    id: userId,
                    name: userName,
                    element: userItem,
                    lastMessageTime: lastMessageTimeText,
                    lastMessagePreview: lastMessagePreviewText,
                    sortTime: getSortTimeFromUser(userId)
                });
            });

            // Add groups
            groupsData.forEach(group => {
                const isCreator = group.created_by == {{ Auth::id() }};
                const lastMessageTime = group.last_message ? new Date(group.last_message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '';
                const lastMessagePreview = group.last_message ?
                    (group.last_message.attachment ? '[Attachment]' : group.last_message.message) :
                    'Group chat';

                allChats.push({
                    type: 'group',
                    id: group.id,
                    name: group.name,
                    element: null, // Will be created
                    lastMessageTime: lastMessageTime,
                    lastMessagePreview: lastMessagePreview,
                    sortTime: group.last_message ? new Date(group.last_message.created_at) : new Date(group.created_at),
                    isCreator: isCreator
                });
            });

            // Sort by last message time (most recent first)
            allChats.sort((a, b) => {
                if (!a.sortTime && !b.sortTime) return 0;
                if (!a.sortTime) return 1;
                if (!b.sortTime) return -1;
                return b.sortTime - a.sortTime;
            });

            // Clear the list
            usersList.innerHTML = '';

            // Render sorted items
            allChats.forEach(chat => {
                if (chat.type === 'user') {
                    // Update the time display and re-append user item
                    const timeElement = chat.element.querySelector('.last-message-time');
                    if (timeElement) {
                        // Convert time to IST by adding 5:30 hours
                        const originalTime = timeElement.getAttribute('data-last-message-time');
                        if (originalTime) {
                            const date = new Date(originalTime);
                            date.setHours(date.getHours() + 5);
                            date.setMinutes(date.getMinutes() + 30);
                            timeElement.textContent = date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        } else {
                            timeElement.textContent = chat.lastMessageTime;
                        }
                    }
                    usersList.appendChild(chat.element);
                } else {
                    // Create and append group item
                    const groupHtml = `
                        <div class="chat-user-item group-item d-flex align-items-center"
                            data-group-id="${chat.id}">
                            <i class="fa fa-star favorite-group-star me-2" data-group-id="${chat.id}" style="color: #ccc; cursor:pointer; font-size: 16px;"></i>
                            <div class="group-avatar">
                                <i class="fa fa-users"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold user-name" style="font-size:16px; color: #111;">${chat.name}</span>
                                    <small class="text-muted last-message-time" style="font-size:12px; color: #667781;">${chat.lastMessageTime}</small>
                                </div>
                                <div class="text-muted small last-message-preview" style="font-size:13px; white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px; color: #667781;">
                                    ${chat.lastMessagePreview}
                                </div>
                            </div>
                            ${chat.isCreator ? `<button class="delete-group-btn" data-group-id="${chat.id}" data-group-name="${chat.name}" title="Delete Group">
                                <i class="fa fa-trash"></i>
                            </button>` : ''}
                        </div>
                    `;
                    usersList.insertAdjacentHTML('beforeend', groupHtml);
                }
            });
        }

        // Helper function to get sort time from user data
        function getSortTimeFromUser(userId) {
            const userItem = document.querySelector(`[data-user-id="${userId}"]`);
            if (userItem) {
                const timeElement = userItem.querySelector('.last-message-time');
                if (timeElement && timeElement.getAttribute('data-last-message-time')) {
                    return new Date(timeElement.getAttribute('data-last-message-time'));
                }
            }
            return new Date(0); // Return epoch time for users without messages
        }
        fetchGroups();

        // Auto-select first user if no user/group is selected
        setTimeout(() => {
            if (!currentUserId && !currentGroupId) {
                const firstUser = document.querySelector('.chat-user-item:not(.group-item)');
                if (firstUser) {
                    firstUser.click();
                }
            }
        }, 1000);

        // Handle group creation
        document.getElementById('createGroupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('groupName').value;
            const userIds = Array.from(document.getElementById('groupUsers').selectedOptions).map(opt => opt.value);
            if (userIds.length < 1) {
                document.getElementById('groupCreateError').textContent = 'Select at least 1 user.';
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
                // Refresh the sorted chat list
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
            showChatViewMobile();
            const groupId = $(this).data('group-id');
            currentGroupId = groupId;
            currentUserId = null;

            // Clear existing polling interval
            if (messagePollingInterval) {
                clearInterval(messagePollingInterval);
            }

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

            // Load group messages initially
            loadGroupMessages(true);

            // Start polling for group messages
            messagePollingInterval = setInterval(() => {
                console.log('Polling for group:', groupId, 'Current group:', currentGroupId); // Debug log
                if (currentGroupId === groupId) {
                    loadGroupMessages(false);
                }
            }, 3000);
            
            // Clear reply when switching groups
            cancelReply();
            userHasScrolledManually = false;
            
            // Show search button
            toggleSearchButton();
            // Fetch group members and show, and show/hide add/remove buttons based on creator
            fetch('/group-chat/groups')
                .then(res => res.json())
                .then(groups => {
                    const group = groups.find(g => g.id == groupId);
                    if (group) {
                        lastGroupUsers = group.users.map(u => u.id);
                        const members = group.users.map(u => u.f_name).join(', ');
                        $('#groupMembers').text('Members: ' + members);
                        // Show add/remove buttons if current user is creator
                        if (group.created_by == {{ Auth::id() }}) {
                            $('#addUserToGroupBtn').show().data('group-id', groupId);
                            $('#showGroupMembersBtn').show().data('group-id', groupId);
                        } else {
                            $('#addUserToGroupBtn').hide();
                            $('#showGroupMembersBtn').hide();
                        }
                        
                        // Show rename button only for admin
                        @if(in_array('1', explode(',', Auth::user()->role_id)))
                            $('#renameGroupBtn').show().data('group-id', groupId);
                        @else
                            $('#renameGroupBtn').hide();
                        @endif
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

        // Open rename group modal
        $('#renameGroupBtn').on('click', function() {
            const groupId = $(this).data('group-id');
            fetch('/group-chat/groups')
                .then(res => res.json())
                .then(groups => {
                    const group = groups.find(g => g.id == groupId);
                    if (group) {
                        $('#newGroupName').val(group.name);
                        $('#renameGroupForm').data('group-id', groupId);
                        $('#renameGroupModal').modal('show');
                    }
                });
        });

        // Handle rename group form
        $('#renameGroupForm').on('submit', function(e) {
            e.preventDefault();
            const groupId = $(this).data('group-id');
            const newName = $('#newGroupName').val().trim();
            
            if (!newName) {
                $('#renameGroupError').text('Group name cannot be empty.');
                return;
            }

            $('#renameGroupError').text('');
            
            fetch('/group-chat/rename/' + groupId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ name: newName })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    $('#renameGroupModal').modal('hide');
                    $('#newGroupName').val('');
                    
                    // Update the chat header with new name
                    $('#chatHeader').text(newName);
                    
                    // Refresh the group list to show updated name
                    fetchGroups();
                    
                    // Show success message
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Group renamed successfully!');
                    } else {
                        alert('Group renamed successfully!');
                    }
                } else {
                    $('#renameGroupError').text(data.error || 'Failed to rename group.');
                }
            })
            .catch(error => {
                console.error('Error renaming group:', error);
                $('#renameGroupError').text('Failed to rename group. Please try again.');
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
            let url = '';
            if (currentUserId) {
                formData.append('receiver_id', currentUserId);
                url = '{{ route('lab.chat.send.attachment') }}';
            } else if (currentGroupId) {
                url = '/group-chat/send-attachment/' + currentGroupId;
            } else {
                toastr.error('Select a user or group first!');
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
                console.log('Attachment send response:', data); // Debug log
                if (data.success) {
                    toastr.success('Attachment sent!');
                    if (currentUserId) {
                        loadMessages(currentUserId);
                    } else if (currentGroupId) {
                        // Reload group messages
                        loadGroupMessages(true);
                    }
                    // Refresh the sorted chat list to update positions
                    setTimeout(() => {
                        fetchGroups();
                    }, 500);
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

        // Helper function to format date labels
        function formatDateLabel(dateString) {
            const messageDate = new Date(dateString);
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            today.setHours(0, 0, 0, 0);
            yesterday.setHours(0, 0, 0, 0);
            messageDate.setHours(0, 0, 0, 0);
            if (messageDate.getTime() === today.getTime()) return 'Today';
            else if (messageDate.getTime() === yesterday.getTime()) return 'Yesterday';
            else return messageDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
        function shouldShowDateSeparator(currentMsg, prevMsg) {
            if (!prevMsg) return true;
            return new Date(currentMsg.created_at).toDateString() !== new Date(prevMsg.created_at).toDateString();
        }

        // Function to load group messages
        function loadGroupMessages(scrollToBottom = false) {
            if (!currentGroupId) return;
            console.log('=== loadGroupMessages function called ==='); // Debug log
            console.log('Group ID:', currentGroupId, 'Scroll to bottom:', scrollToBottom); // Debug log

            fetch('/group-chat/messages/' + currentGroupId)
                .then(res => res.json())
                .then(messages => {
                    console.log('loadGroupMessages - messages received:', messages); // Debug log
                    let html = '';
                    if (messages.length === 0) {
                        html = `<div class=\"chat-welcome text-center mt-5\">
                            <i class=\"fas fa-comments fa-3x text-muted mb-3\"></i>
                            <h4>No messages yet</h4>
                            <p class=\"text-muted\">Start the conversation!</p>
                        </div>`;
                    } else {
                        messages.forEach((msg, index) => {
                            const prevMsg = index > 0 ? messages[index - 1] : null;
                            if (shouldShowDateSeparator(msg, prevMsg)) {
                                html += `<div style="text-align:center; margin:20px 0;"><span style="background:#e0e0e0; padding:6px 12px; border-radius:8px; font-size:12px; color:#666; font-weight:500;">${formatDateLabel(msg.created_at)}</span></div>`;
                            }
                            console.log('loadGroupMessages - processing message:', msg); // Debug log
                            let repliedHtml = '';
                            if (msg.replied_to) {
                                const rText = msg.replied_to.message || '[Attachment]';
                                const rSender = msg.replied_to.sender ? msg.replied_to.sender.f_name : 'Unknown';
                                repliedHtml = `<div class="replied-message" onclick="scrollToOriginalMessage(${msg.reply_to_id})"><div class="replied-message-sender">${rSender}</div><div class="replied-message-text">${rText}</div></div>`;
                            }
                            let messageContent = '';
                            if (msg.attachment) {
                                console.log('loadGroupMessages - message has attachment:', msg.attachment); // Debug log
                                // Use original filename if available, otherwise extract from path
                                const displayName = msg.original_filename || msg.attachment.split('/').pop();
                                const escapedDisplayName = displayName.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                                // No encoding needed as backend saves files with underscores instead of spaces
                                // Check if it's an image
                                const imgExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                const ext = msg.attachment.split('.').pop().toLowerCase();
                                if (imgExt.includes(ext)) {
                                    messageContent = `<img src="/storage/${msg.attachment}" class="img-fluid chat-message-image" style="max-width:200px;max-height:200px; border-radius:8px;" alt="Attachment" title="Click to zoom">`;
                                } else {
                                    messageContent = `<a href="/storage/${msg.attachment}" download="${displayName}" class="btn btn-sm btn-primary"><i class="fa fa-download"></i> Download ${escapedDisplayName}</a>`;
                                }
                            } else {
                                messageContent = msg.message;
                            }
                            const msgTextReply = msg.attachment ? '[Attachment]' : msg.message;
                            html += `<div class=\"message ${msg.sender_id == {{ Auth::id() }} ? 'sent' : 'received'}\" data-message-id=\"${msg.id}\">\n                                <div class=\"fw-bold\">${msg.sender.f_name}</div>\n                                <div class=\"message-content\">${repliedHtml}${messageContent}</div>\n                                <div class=\"message-time\">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>\n                                <button class=\"message-reply-btn\" data-message-id=\"${msg.id}\" data-sender-name=\"${msg.sender.f_name}\" data-message-text=\"${msgTextReply}\"><i class=\"fa fa-reply\"></i> Reply</button>\n                            </div>`;
                        });
                    }
                    $('#chatMessages').html(html);
                    if (scrollToBottom && !isSearchActive && !userHasScrolledManually) {
                        $('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight);
                    } else if (isSearchActive) {
                        // Restore search state after group messages are loaded
                        restoreSearchState();
                    }
                });
        }

        // MOBILE CHAT TOGGLE LOGIC
        function showChatViewMobile() {
            if (window.innerWidth < 768) {
                document.querySelector('.chat-card').classList.add('show-chat');
                document.getElementById('chatBackBtn').style.display = 'inline-block';
            }
        }
        function showUserListMobile() {
            if (window.innerWidth < 768) {
                document.querySelector('.chat-card').classList.remove('show-chat');
                document.getElementById('chatBackBtn').style.display = 'none';
            }
        }
        // On user click, show chat view on mobile
        document.getElementById('usersList').addEventListener('click', function(e) {
            let target = e.target;
            while (target && !target.classList.contains('chat-user-item')) {
                target = target.parentElement;
            }
            if (!target) return;
            if (target.classList.contains('group-item')) return;
            showChatViewMobile();
        });
        // Back button logic
        document.getElementById('chatBackBtn').addEventListener('click', function() {
            showUserListMobile();
        });
        // On page load, always show user list on mobile
        if (window.innerWidth < 768) {
            showUserListMobile();
        }
        // On window resize, reset view if needed
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                document.querySelector('.chat-card').classList.remove('show-chat');
                document.getElementById('chatBackBtn').style.display = 'none';
            }
        });

        // CHAT SEARCH FUNCTIONALITY
        let searchResults = [];
        let currentSearchIndex = -1;
        let originalMessages = '';
        let isSearchActive = false;
        let currentSearchQuery = '';

        // Show/hide search button based on chat state
        function toggleSearchButton() {
            const searchBtn = document.getElementById('chatSearchBtn');
            if (currentUserId || currentGroupId) {
                searchBtn.style.display = 'inline-block';
            } else {
                searchBtn.style.display = 'none';
                hideSearchBar();
            }
        }

        // Show search bar
        function showSearchBar() {
            document.getElementById('chatSearchBar').style.display = 'block';
            document.getElementById('chatSearchInput').focus();
            isSearchActive = true;
            // Store original messages for restoration
            originalMessages = document.getElementById('chatMessages').innerHTML;
        }

        // Hide search bar
        function hideSearchBar() {
            document.getElementById('chatSearchBar').style.display = 'none';
            document.getElementById('chatSearchInput').value = '';
            document.getElementById('chatSearchResults').style.display = 'none';
            isSearchActive = false;
            currentSearchQuery = '';
            
            // Restore original content for each message
            const messages = document.querySelectorAll('.message .message-content');
            messages.forEach(messageContent => {
                if (messageContent.dataset.originalContent) {
                    messageContent.innerHTML = messageContent.dataset.originalContent;
                    delete messageContent.dataset.originalContent;
                }
            });
            
            // Remove all search highlights and active states
            document.querySelectorAll('.search-highlight').forEach(el => {
                el.outerHTML = el.textContent;
            });
            document.querySelectorAll('.search-result-active').forEach(el => {
                el.classList.remove('search-result-active');
            });
            
            // Clear search state
            searchResults = [];
            currentSearchIndex = -1;
            originalMessages = '';
        }

        // Search through messages
        function searchMessages(query) {
            if (!query.trim()) {
                hideSearchBar();
                return;
            }

            currentSearchQuery = query;
            const chatMessages = document.getElementById('chatMessages');
            const messages = chatMessages.querySelectorAll('.message');
            searchResults = [];
            
            messages.forEach((message, index) => {
                const messageContent = message.querySelector('.message-content');
                if (messageContent) {
                    const text = messageContent.textContent.toLowerCase();
                    if (text.includes(query.toLowerCase())) {
                        searchResults.push({
                            element: message,
                            index: index,
                            content: messageContent
                        });
                    }
                }
            });

            // Update results display
            const resultsDiv = document.getElementById('chatSearchResults');
            if (searchResults.length > 0) {
                resultsDiv.textContent = `${searchResults.length} result(s) found`;
                resultsDiv.style.display = 'block';
                currentSearchIndex = 0;
                highlightSearchResults(query);
                scrollToSearchResult(0);
            } else {
                resultsDiv.textContent = 'No results found';
                resultsDiv.style.display = 'block';
                currentSearchIndex = -1;
            }
        }

        // Search through messages without resetting index (for restoration)
        function searchMessagesWithoutIndexReset(query) {
            if (!query.trim()) {
                return;
            }

            currentSearchQuery = query;
            const chatMessages = document.getElementById('chatMessages');
            const messages = chatMessages.querySelectorAll('.message');
            searchResults = [];
            
            messages.forEach((message, index) => {
                const messageContent = message.querySelector('.message-content');
                if (messageContent) {
                    const text = messageContent.textContent.toLowerCase();
                    if (text.includes(query.toLowerCase())) {
                        searchResults.push({
                            element: message,
                            index: index,
                            content: messageContent
                        });
                    }
                }
            });

            // Update results display but don't change currentSearchIndex
            const resultsDiv = document.getElementById('chatSearchResults');
            if (searchResults.length > 0) {
                resultsDiv.textContent = `${searchResults.length} result(s) found`;
                resultsDiv.style.display = 'block';
                highlightSearchResults(query);
                // Don't scroll here - let the caller handle it
            } else {
                resultsDiv.textContent = 'No results found';
                resultsDiv.style.display = 'block';
            }
        }

        // Highlight search results
        function highlightSearchResults(query) {
            const chatMessages = document.getElementById('chatMessages');
            const messages = chatMessages.querySelectorAll('.message');
            
            messages.forEach(message => {
                const messageContent = message.querySelector('.message-content');
                if (messageContent) {
                    // Store original content if not already stored
                    if (!messageContent.dataset.originalContent) {
                        messageContent.dataset.originalContent = messageContent.innerHTML;
                    }
                    
                    // Get the text content for searching
                    const text = messageContent.textContent;
                    const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                    
                    // Replace with highlighted version
                    messageContent.innerHTML = text.replace(regex, '<span class="search-highlight">$1</span>');
                }
            });
        }

        // Scroll to search result
        function scrollToSearchResult(index) {
            if (searchResults.length === 0 || index < 0 || index >= searchResults.length) return;
            
            const result = searchResults[index];
            const chatMessages = document.getElementById('chatMessages');
            
            // Remove previous active highlight
            document.querySelectorAll('.search-result-active').forEach(el => {
                el.classList.remove('search-result-active');
            });
            
            // Add active highlight to current result
            result.element.classList.add('search-result-active');
            
            // Calculate the position to scroll to
            const chatMessagesRect = chatMessages.getBoundingClientRect();
            const resultRect = result.element.getBoundingClientRect();
            const scrollTop = chatMessages.scrollTop;
            
            // Calculate the target scroll position to center the result
            const targetScrollTop = scrollTop + (resultRect.top - chatMessagesRect.top) - (chatMessagesRect.height / 2) + (resultRect.height / 2);
            
            // Smooth scroll to the calculated position
            chatMessages.scrollTo({
                top: targetScrollTop,
                behavior: 'smooth'
            });
            
            // Update results display
            const resultsDiv = document.getElementById('chatSearchResults');
            resultsDiv.textContent = `${index + 1} of ${searchResults.length} result(s)`;
        }

        // Navigate to next search result
        function nextSearchResult() {
            if (searchResults.length === 0) return;
            currentSearchIndex = (currentSearchIndex + 1) % searchResults.length;
            scrollToSearchResult(currentSearchIndex);
        }

        // Navigate to previous search result
        function prevSearchResult() {
            if (searchResults.length === 0) return;
            currentSearchIndex = currentSearchIndex <= 0 ? searchResults.length - 1 : currentSearchIndex - 1;
            scrollToSearchResult(currentSearchIndex);
        }

        // Restore search state after messages are loaded
        function restoreSearchState() {
            if (isSearchActive && currentSearchQuery) {
                // Store the current search index before re-running search
                const savedSearchIndex = currentSearchIndex;
                
                // Re-run search with the current query
                setTimeout(() => {
                    // Re-run search but preserve the index
                    searchMessagesWithoutIndexReset(currentSearchQuery);
                    
                    // Restore the saved search index if it's still valid
                    if (savedSearchIndex >= 0 && savedSearchIndex < searchResults.length) {
                        currentSearchIndex = savedSearchIndex;
                        scrollToSearchResult(currentSearchIndex);
                    }
                }, 100);
            }
        }

        // Event listeners for search functionality
        document.getElementById('chatSearchBtn').addEventListener('click', showSearchBar);
        document.getElementById('chatSearchClose').addEventListener('click', hideSearchBar);
        document.getElementById('chatSearchInput').addEventListener('input', function(e) {
            searchMessages(e.target.value);
        });
        document.getElementById('chatSearchNext').addEventListener('click', nextSearchResult);
        document.getElementById('chatSearchPrev').addEventListener('click', prevSearchResult);

        // Handle Enter key in search input
        document.getElementById('chatSearchInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (e.shiftKey) {
                    prevSearchResult();
                } else {
                    nextSearchResult();
                }
            } else if (e.key === 'Escape') {
                hideSearchBar();
            }
        });

        // Update search button visibility when chat state changes
        const originalLoadMessages = loadMessages;
        loadMessages = function(userId) {
            originalLoadMessages(userId);
            toggleSearchButton();
        };

        const originalLoadGroupMessages = loadGroupMessages;
        loadGroupMessages = function(scrollToBottom = false) {
            originalLoadGroupMessages(scrollToBottom);
            toggleSearchButton();
        };

        // IMAGE ZOOM FUNCTIONALITY
        function openImageZoom(imageSrc) {
            const modal = document.getElementById('imageZoomModal');
            const zoomedImg = document.getElementById('zoomedImage');
            modal.style.display = 'block';
            zoomedImg.src = imageSrc;
            document.body.style.overflow = 'hidden';
        }

        function closeImageZoom() {
            const modal = document.getElementById('imageZoomModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const closeBtn = document.querySelector('.image-zoom-close');
            if (closeBtn) {
                closeBtn.onclick = closeImageZoom;
            }

            const modal = document.getElementById('imageZoomModal');
            if (modal) {
                modal.onclick = function(event) {
                    if (event.target === modal) {
                        closeImageZoom();
                    }
                };
            }

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeImageZoom();
                }
            });
        });

        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('chat-message-image')) {
                openImageZoom(event.target.src);
            }
        });

        // REPLY FUNCTIONALITY
        function setReplyTo(messageId, senderName, messageText) {
            replyToMessage = { id: messageId, sender: senderName, text: messageText };
            document.getElementById('replyToMessageId').value = messageId;
            document.getElementById('replyToUser').textContent = senderName;
            document.getElementById('replyToMessage').textContent = messageText || '[Attachment]';
            document.getElementById('replyPreview').style.display = 'block';
            document.getElementById('messageInput').focus();
        }
        function cancelReply() {
            replyToMessage = null;
            document.getElementById('replyToMessageId').value = '';
            document.getElementById('replyPreview').style.display = 'none';
        }
        function scrollToOriginalMessage(oMId){const tM=document.querySelector(`.message[data-message-id="${oMId}"]`);if(tM){const cM=document.getElementById('chatMessages');userHasScrolledManually=true;document.querySelectorAll('.message-highlight').forEach(el=>el.classList.remove('message-highlight'));const cR=cM.getBoundingClientRect();const tR=tM.getBoundingClientRect();const sT=cM.scrollTop;const tST=sT+(tR.top-cR.top)-(cR.height/2)+(tR.height/2);cM.scrollTo({top:tST,behavior:'smooth'});setTimeout(()=>{tM.classList.add('message-highlight');setTimeout(()=>tM.classList.remove('message-highlight'),2000);},300);setTimeout(()=>{userHasScrolledManually=false;},10000);}}
        document.addEventListener('DOMContentLoaded', function() {
            const cancelReplyBtn = document.getElementById('cancelReply');
            if (cancelReplyBtn) { cancelReplyBtn.addEventListener('click', cancelReply); }
        });
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('message-reply-btn') || event.target.parentElement.classList.contains('message-reply-btn')) {
                const button = event.target.classList.contains('message-reply-btn') ? event.target : event.target.parentElement;
                setReplyTo(button.getAttribute('data-message-id'), button.getAttribute('data-sender-name'), button.getAttribute('data-message-text'));
            }
        });
    </script>

<script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
        let currentUserId = null;
        let currentUserMobile = null;
        let messagePollingInterval = null;
        let activeRealtimeUserChannel = null;
        let currentGroupId = null; // Track current group for group chat
        let lastGroupUsers = [];
        let translatedMessages = {};

function escapeHtml(text) {
            if (!text) return "";
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

function appendPendingOutgoingMessage(messageText) {
            const chatMessages = document.getElementById('chatMessages');
            if (!chatMessages) return null;
            const now = new Date();
            const wrapper = document.createElement('div');
            wrapper.className = 'message sent pending-message';
            wrapper.innerHTML = `
                <div class="message-content sent-bg">${escapeHtml(messageText || '')}</div>
                <div class="message-time">${now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
            `;
            chatMessages.appendChild(wrapper);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return wrapper;
        }

function appendSingleMessage(message) {
            const chatMessages = document.getElementById('chatMessages');
            if (!chatMessages) return;
            // Remove "No messages yet" placeholder if present
            const welcomeDiv = chatMessages.querySelector('.chat-welcome');
            if (welcomeDiv) welcomeDiv.remove();

            const isSent = message.sender_id == {{ Auth::id() }};
            const messageDate = new Date(message.created_at);
            const messageDateStr = messageDate.toLocaleDateString();

            // Add date separator if needed
            const existingDates = chatMessages.querySelectorAll('.badge.bg-light');
            let lastDate = existingDates.length ? existingDates[existingDates.length - 1].textContent.trim() : null;
            if (lastDate !== messageDateStr) {
                chatMessages.insertAdjacentHTML('beforeend', `
                    <div class="text-center my-3">
                        <span class="badge bg-light text-dark">${messageDateStr}</span>
                    </div>
                `);
            }

            let seenHtml = '';
            if (isSent && message.is_read) {
                seenHtml = `<div class="message-seen text-end" style="font-size: 0.8em; color: #28c76f;">Seen</div>`;
            }

            // Reply preview
            let repliedHtml = '';
            if (message.reply_to_id && message.replied_to) {
                const rT = message.replied_to.message || (message.replied_to.attachment ? '[Attachment]' : '');
                let rS = 'Unknown';
                if (message.replied_to.sender) {
                    rS = message.replied_to.sender.f_name || message.replied_to.sender.name || 'Unknown';
                }
                const truncatedText = rT.length > 50 ? rT.substring(0, 50) + '...' : rT;
                const replyBgColor = isSent ? 'rgba(255,255,255,0.2)' : 'rgba(37,211,102,0.1)';
                const replyTextColor = isSent ? '#333333' : '#667781';
                repliedHtml = `<div class="replied-message" onclick="event.stopPropagation(); scrollToOriginalMessage(${message.reply_to_id});" title="Click to view original message" style="cursor: pointer; background: ${replyBgColor}; border-left: 3px solid #25d366; padding: 8px 12px; margin-bottom: 8px; border-radius: 4px; font-size: 13px;">
                    <div class="replied-message-sender" style="color: #25d366; font-weight: 600; font-size: 12px; margin-bottom: 4px;"><i class="fa fa-reply me-1"></i>${escapeHtml(rS)}</div>
                    <div class="replied-message-text" style="color: ${replyTextColor}; font-size: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${escapeHtml(truncatedText)}</div>
                </div>`;
            }

            // Build message content
            let contentHtml = '';
            if (message.attachment) {
                const imgExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                const ext = message.attachment.split('.').pop().toLowerCase();
                if ((message.attachment_type && message.attachment_type.startsWith('image/')) || imgExt.includes(ext)) {
                    contentHtml = `<div style="position:relative; display:inline-block;"><img src="/storage/${message.attachment}" class="img-fluid" style="max-width:200px;max-height:200px; border-radius:8px;" alt="Attachment"><a href="/storage/${message.attachment}" download class="btn btn-sm btn-primary" style="position:absolute;bottom:8px;right:8px;z-index:2; border-radius:50%; background:#F7941D; color:#fff; padding:8px;"><i class="fa fa-download"></i></a></div>`;
                } else if (message.attachment_type && message.attachment_type.startsWith('video/')) {
                    contentHtml = `<div style="position:relative; display:inline-block;"><video controls style="max-width:200px;max-height:200px; border-radius:8px;"><source src="/storage/${message.attachment}"></video><a href="/storage/${message.attachment}" download class="btn btn-sm btn-primary" style="position:absolute;bottom:8px;right:8px;z-index:2; border-radius:50%; background:#F7941D; color:#fff; padding:8px;"><i class="fa fa-download"></i></a></div>`;
                } else {
                    contentHtml = `<div style="background:#e6f0ff; border-radius:8px; padding:16px; display:flex;color:#000; align-items:center; gap:12px;"><i class="fa fa-file fa-2x text-primary"></i><span style="flex:1;">${message.attachment.split('/').pop()}</span><a href="/storage/${message.attachment}" download class="btn btn-sm btn-primary d-flex align-items-center" style="background:#F7941D; color:#fff;"><i class="fa fa-download me-1"></i> Download</a></div>`;
                }
            } else {
                contentHtml = escapeHtml(message.message || '');
            }

            const senderName = isSent ? 'You' : (message.sender ? (message.sender.f_name || message.sender.name || 'User') : document.getElementById('chatHeader').textContent);
            const messageText = message.attachment ? '📎 ' + message.attachment.split('/').pop() : (message.message || '');

            const html = `<div class="message ${isSent ? 'sent' : 'received'}" data-message-id="${message.id}">
                <div class="fw-bold">${escapeHtml(senderName)}</div>
                <div class="message-content">${repliedHtml}${contentHtml}</div>
                <div class="message-translated" style="display:none; margin-top:6px; padding:6px 10px; background:rgba(0,0,0,0.05); border-radius:8px; font-size:13px;"></div>
                <div class="message-time">${messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                ${seenHtml}
                <button class="message-reply-btn" data-message-id="${message.id}" data-sender-name="${escapeHtml(senderName)}" data-message-text="${escapeHtml(messageText)}" title="Reply" style="opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px;"><i class="fa fa-reply"></i></button>
                <button class="message-translate-btn" data-message-id="${message.id}" data-message-text="${escapeHtml(messageText)}" title="Translate" style="opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px; margin-left: 4px;"><i class="fa fa-language"></i></button>
            </div>`;

            chatMessages.insertAdjacentHTML('beforeend', html);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

function markVisibleSentMessagesSeen(readerId) {
            if (!currentUserId) return;
            if (readerId && String(readerId) !== String(currentUserId)) return;
            document.querySelectorAll('#chatMessages .message.sent').forEach(msgEl => {
                if (!msgEl.querySelector('.message-seen')) {
                    msgEl.insertAdjacentHTML('beforeend', '<div class="message-seen text-end" style="font-size: 0.8em; color: #28c76f;">Seen</div>');
                }
            });
        }

function setUserUnreadCount(userId, count) {
            const badge = document.querySelector(`.chat-user-item[data-user-id="${userId}"] .unread-count`);
            if (!badge) return;
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = '';
            } else {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        }



        document.addEventListener('DOMContentLoaded', function() {
            function bindRealtimeUserChannel(userId) {
                if (!window.ChatRealtime || !window.chatRealtimeConfig || !window.chatRealtimeConfig.enabled) {
                    return;
                }
                if (activeRealtimeUserChannel && typeof activeRealtimeUserChannel.stopListening === 'function') {
                    activeRealtimeUserChannel.stopListening('.direct.message.created');
                    activeRealtimeUserChannel.stopListening('.direct.messages.read');
                }
                activeRealtimeUserChannel = window.ChatRealtime.subscribeUserChannel({{ Auth::id() }}, function (payload, eventName) {
                    if (eventName === '.direct.messages.read') {
                        markVisibleSentMessagesSeen(payload && payload.reader_id ? payload.reader_id : null);
                        return;
                    }
                    if (eventName === '.direct.message.created' && payload && payload.message) {
                        var msg = payload.message;
                        // If this message is for the currently open chat, append it
                        if (currentUserId && (
                            String(msg.sender_id) === String(currentUserId) ||
                            String(msg.receiver_id) === String(currentUserId)
                        )) {
                            // Avoid duplicate: check if message already rendered
                            if (!document.querySelector('#chatMessages .message[data-message-id="' + msg.id + '"]')) {
                                appendSingleMessage(msg);
                            }
                            if (String(msg.sender_id) !== String({{ Auth::id() }})) {
                                markAsRead(currentUserId);
                            }
                        }
                        updateUnreadCounts();
                        return;
                    }
                    updateUnreadCounts();
                });
            }
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
                translatedMessages = {};

                // Set currentUserId BEFORE any labs
                currentUserId = String(userId);
                console.log('Current user ID set to:', currentUserId);

                // Remove active from all, add to selected
                document.querySelectorAll('.chat-user-item').forEach(function(item) {
                    item.classList.remove('active');
                        target.classList.add('active');
                    });
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

                // Bind WebSocket realtime channel for live updates
                bindRealtimeUserChannel(userId);

                // Mark messages as read
                setUserUnreadCount(userId, 0);
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
                    const replyToId = document.getElementById('replyToMessageId').value;
                    const bodyData = { message };
                    if (replyToId) {
                        bodyData.reply_to_id = replyToId;
                    }
                    fetch('/group-chat/send/' + currentGroupId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(bodyData)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        document.getElementById('messageInput').value = '';
                        cancelReply();
                        // Reload group messages without re-triggering click
                        if (currentGroupId) {
                            if (typeof loadGroupMessages === 'function') {
                                loadGroupMessages(true);
                            } else {
                                $(`.chat-user-item.group-item[data-group-id=\"${currentGroupId}\"]`).click();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error sending group message:', error);
                        toastr.error('Failed to send message. Please try again.');
                    });
                }
                // User-to-user chat
                else if (currentUserId) {
                    const receiverId = document.getElementById('receiverId').value;
                    const pendingBubble = appendPendingOutgoingMessage(message);
                    const replyToId = document.getElementById('replyToMessageId').value;
                    let bodyData = `receiver_id=${encodeURIComponent(receiverId)}&message=${encodeURIComponent(message)}`;
                    if (replyToId) {
                        bodyData += `&reply_to_id=${encodeURIComponent(replyToId)}`;
                    }
                    document.getElementById('messageInput').value = '';
                    cancelReply();
                    fetch('{{ route('lab.chat.send') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: bodyData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Remove pending bubble and append real message
                        if (pendingBubble && pendingBubble.parentNode) {
                            pendingBubble.parentNode.removeChild(pendingBubble);
                        }
                        if (data && data.id && String(currentUserId) === String(receiverId)) {
                            if (!document.querySelector('#chatMessages .message[data-message-id="' + data.id + '"]')) {
                                appendSingleMessage(data);
                            }
                            setUserUnreadCount(receiverId, 0);
                        }
                        updateUnreadCounts();
                    })
                    .catch(error => {
                        console.error('Error sending direct message:', error);
                        if (pendingBubble && pendingBubble.parentNode) {
                            pendingBubble.parentNode.removeChild(pendingBubble);
                        }
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Failed to send message. Please try again.');
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
                console.log('Favorite items:', $('.chat-user-item.is-favorite').map(function(){return $(this).data('user-id');}).get());
            }

            // Show only favorites in the user list
            function showOnlyFavorites() {
                var $all = $('.chat-user-item').not('.group-item');
                var $fav = $('.chat-user-item.is-favorite').not('.group-item');
                var $groups = $('.chat-user-item.group-item');
                
                $all.hide();
                $fav.show();
                $groups.hide();

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
                
                $all.hide();
                $groups.show();

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
                    fetch('/lab/chat/favorites')
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
                fetch('/lab/chat/favorite/' + userId, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
                })
                .then(res => res.json())
                .then(data => {
                    fetch('/lab/chat/favorites')
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
            fetch('/lab/chat/favorites')
                .then(res => res.json())
                .then(favorites => {
                    updateFavoriteStars(favorites);
                });
        
            updateUnreadCounts(); // Ensure unread counts show on page load

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
            const apiUrl = `{{ url('lab/chat/messages') }}/${userId}`;
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
                        
                        // Reply preview - WhatsApp style
                        let repliedHtml = '';
                        if (message.reply_to_id && message.replied_to) {
                            const rT = message.replied_to.message || (message.replied_to.attachment ? '[Attachment]' : '');
                            let rS = 'Unknown';
                            if (message.replied_to.sender) {
                                rS = message.replied_to.sender.f_name || message.replied_to.sender.name || 'Unknown';
                            }
                            const truncatedText = rT.length > 50 ? rT.substring(0, 50) + '...' : rT;
                            // Different styling for sent vs received messages - matching customer chat style
                            const replyBgColor = isSent ? 'rgba(255,255,255,0.2)' : 'rgba(37,211,102,0.1)';
                            const replyBorderColor = '#25d366'; // Always green border
                            const replySenderColor = '#25d366'; // Always green sender name
                            const replyTextColor = isSent ? '#333333' : '#667781'; // Dark text for sent, gray for received
                            repliedHtml = `<div class="replied-message" onclick="event.stopPropagation(); scrollToOriginalMessage(${message.reply_to_id});" title="Click to view original message" style="cursor: pointer; background: ${replyBgColor}; border-left: 3px solid ${replyBorderColor}; padding: 8px 12px; margin-bottom: 8px; border-radius: 4px; font-size: 13px;">
                                <div class="replied-message-sender" style="color: ${replySenderColor}; font-weight: 600; font-size: 12px; margin-bottom: 4px;"><i class="fa fa-reply me-1"></i>${escapeHtml(rS)}</div>
                                <div class="replied-message-text" style="color: ${replyTextColor}; font-size: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${escapeHtml(truncatedText)}</div>
                            </div>`;
                        }
                        
                        let messageHtml = '';
                        const senderName = isSent ? 'You' : document.getElementById('chatHeader').textContent;
                        const messageText = message.attachment ? '[Attachment]' : (message.message || '');
                        const tr = translatedMessages[message.id];
                        const transStyle = tr ? '' : 'display:none;';
                        const transHtml = tr ? '<small class="text-muted">' + escapeHtml(tr.lang) + ':</small> ' + escapeHtml(tr.text) : '';
                        
                        if (message.attachment) {
                            let attachmentHtml = '';
                            // Always show image if file extension is image, even if attachment_type is missing
                            const imgExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            const ext = message.attachment.split('.').pop().toLowerCase();
                            if ((message.attachment_type && message.attachment_type.startsWith('image/')) || imgExt.includes(ext)) {
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
                        <div class="message ${isSent ? 'sent' : 'received'}" data-message-id="${message.id}">
                            <div class="message-content">${repliedHtml}${attachmentHtml}</div>
                            <div class="message-translated" style="${transStyle} margin-top:6px; padding:6px 10px; background:rgba(0,0,0,0.05); border-radius:8px; font-size:13px;">${transHtml}</div>
                            <div class="message-time">${messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                            ${seenHtml}
                            <button class="message-reply-btn" data-message-id="${message.id}" data-sender-name="${escapeHtml(senderName)}" data-message-text="${escapeHtml(messageText)}" title="Reply" style="opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px;"><i class="fa fa-reply"></i></button>
                            <button class="message-translate-btn" data-message-id="${message.id}" data-message-text="${escapeHtml(messageText)}" title="Translate" style="opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px; margin-left: 4px;"><i class="fa fa-language"></i></button>
                        </div>
                    `;
                        } else {
                            messageHtml = `
                        <div class="message ${isSent ? 'sent' : 'received'}" data-message-id="${message.id}">
                            <div class="message-content ${isSent ? 'sent-bg' : 'received-bg'}">${repliedHtml}${escapeHtml(message.message || '')}</div>
                            <div class="message-translated" style="${transStyle} margin-top:6px; padding:6px 10px; background:rgba(0,0,0,0.05); border-radius:8px; font-size:13px;">${transHtml}</div>
                            <div class="message-time">${messageDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                            ${seenHtml}
                            <button class="message-reply-btn" data-message-id="${message.id}" data-sender-name="${escapeHtml(senderName)}" data-message-text="${escapeHtml(messageText)}" title="Reply" style="opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px;"><i class="fa fa-reply"></i></button>
                            <button class="message-translate-btn" data-message-id="${message.id}" data-message-text="${escapeHtml(messageText)}" title="Translate" style="opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px; margin-left: 4px;"><i class="fa fa-language"></i></button>
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
            fetch(`{{ url('lab/chat/mark-read') }}/${userId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
        }
        // Update unread message counts (users and groups)
                

        function setGroupUnreadCount(groupId, count) {
            const badge = document.querySelector(`.chat-user-item.group-item[data-group-id="${groupId}"] .unread-count`);
            if (!badge) return;
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = '';
            } else {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        }


        

        

        

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
            fetch('{{ route('lab.chat.unread-counts') }}')
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
                translatedMessages = {};
                currentGroupId = groupId;
                currentUserId = null;
                
                document.querySelectorAll('.chat-user-item').forEach(function(item) {
                    item.classList.remove('active');
                });
                this.classList.add('active');
            }
            // Mark all group messages as read
            fetch(`/group-chat/mark-read/${groupId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(() => {
                setGroupUnreadCount(groupId, 0);
                updateUnreadCounts();
                // Clear previous polling
            if (messagePollingInterval) {
                clearInterval(messagePollingInterval);
                messagePollingInterval = null;
            }
            // Function to load group messages
            function loadGroupMessages(scrollToBottom = false) {
                // Ensure escapeHtml is available
                const escapeHtmlFunc = typeof escapeHtml !== 'undefined' ? escapeHtml : function(text) {
                    if (!text) return '';
                    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                    return String(text).replace(/[&<>"']/g, m => map[m]);
                };
                
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
                                const isSent = msg.sender_id == {{ Auth::id() }};
                                
                                // Reply preview - WhatsApp style
                                let repliedHtml = '';
                                if (msg.reply_to_id && msg.replied_to) {
                                    const rT = msg.replied_to.message || (msg.replied_to.attachment ? '[Attachment]' : '');
                                    let rS = 'Unknown';
                                    if (msg.replied_to.sender) {
                                        rS = msg.replied_to.sender.f_name || msg.replied_to.sender.name || 'Unknown';
                                    }
                                    const truncatedText = rT.length > 50 ? rT.substring(0, 50) + '...' : rT;
                                    const replyBgColor = isSent ? 'rgba(255,255,255,0.2)' : 'rgba(37,211,102,0.1)';
                                    const replyBorderColor = '#25d366';
                                    const replySenderColor = '#25d366';
                                    const replyTextColor = isSent ? '#333333' : '#667781';
                                    repliedHtml = `<div class="replied-message" onclick="event.stopPropagation(); scrollToOriginalMessage(${msg.reply_to_id});" title="Click to view original message" style="cursor: pointer; background: ${replyBgColor}; border-left: 3px solid ${replyBorderColor}; padding: 8px 12px; margin-bottom: 8px; border-radius: 4px; font-size: 13px;">
                                        <div class="replied-message-sender" style="color: ${replySenderColor}; font-weight: 600; font-size: 12px; margin-bottom: 4px;"><i class="fa fa-reply me-1"></i>${escapeHtmlFunc(rS)}</div>
                                        <div class="replied-message-text" style="color: ${replyTextColor}; font-size: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${escapeHtmlFunc(truncatedText)}</div>
                                    </div>`;
                                }
                                
                                const senderName = msg.sender.f_name || 'Unknown';
                                const messageText = msg.attachment ? '[Attachment]' : (msg.message || '');
                                const tr = translatedMessages[msg.id];
                                const transStyle = tr ? '' : 'display:none;';
                                const transHtml = tr ? '<small class="text-muted">' + escapeHtmlFunc(tr.lang) + ':</small> ' + escapeHtmlFunc(tr.text) : '';
                                
                                let attachmentHtml = '';
                                if (msg.attachment) {
                                    const imgExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                    const ext = msg.attachment.split('.').pop().toLowerCase();
                                    if (imgExt.includes(ext)) {
                                        attachmentHtml = `<img src="/storage/${msg.attachment}" class="img-fluid" style="max-width:200px;max-height:200px; border-radius:8px;" alt="Attachment">`;
                                    } else {
                                        attachmentHtml = `<a href="/storage/${msg.attachment}" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-download"></i> Download</a>`;
                                    }
                                }
                                
                                html += `<div class="message ${isSent ? 'sent' : 'received'}" data-message-id="${msg.id}">
                                    <div class="fw-bold">${escapeHtmlFunc(senderName)}</div>
                                    <div class="message-content">${repliedHtml}${msg.attachment ? attachmentHtml : escapeHtmlFunc(msg.message || '')}</div>
                                    <div class="message-translated" style="${transStyle} margin-top:6px; padding:6px 10px; background:rgba(0,0,0,0.05); border-radius:8px; font-size:13px;">${transHtml}</div>
                                    <div class="message-time">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                                    <button class="message-reply-btn" data-message-id="${msg.id}" data-sender-name="${escapeHtmlFunc(senderName)}" data-message-text="${escapeHtmlFunc(messageText)}" title="Reply" style="opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px;"><i class="fa fa-reply"></i></button>
                                    <button class="message-translate-btn" data-message-id="${msg.id}" data-message-text="${escapeHtmlFunc(messageText)}" title="Translate" style="opacity: 0; transition: opacity 0.2s; background: rgba(0,0,0,0.05); border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #667781; margin-top: 4px; margin-left: 4px;"><i class="fa fa-language"></i></button>
                                </div>`;
                            });
                        }
                        $('#chatMessages').html(html);
                        if (scrollToBottom) {
                            $('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading group messages:', error);
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
                const replyToId = document.getElementById('replyToMessageId').value;
                if (replyToId) {
                    formData.append('reply_to_id', replyToId);
                }
                var url = '{{ route('lab.chat.send.attachment') }}';
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
                    cancelReply();
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
    
        // Escape HTML function
        

        // Reply functions - WhatsApp style
        function setReplyTo(messageId, senderName, messageText) {
            if (!messageId) return;
            document.getElementById('replyToMessageId').value = messageId;
            document.getElementById('replyToUser').textContent = senderName || 'Unknown';
            const displayText = messageText || '[Attachment]';
            document.getElementById('replyToMessage').textContent = displayText.length > 50 ? displayText.substring(0, 50) + '...' : displayText;
            document.getElementById('replyPreview').style.display = 'block';
            document.getElementById('messageInput').focus();
        }

        function cancelReply() {
            document.getElementById('replyToMessageId').value = '';
            document.getElementById('replyToUser').textContent = '';
            document.getElementById('replyToMessage').textContent = '';
            document.getElementById('replyPreview').style.display = 'none';
        }

        function scrollToOriginalMessage(messageId) {
            const chatMessages = document.getElementById('chatMessages');
            const targetMsg = chatMessages.querySelector(`.message[data-message-id="${messageId}"]`);
            
            if (targetMsg) {
                // Remove previous highlights
                document.querySelectorAll('.message-highlight').forEach(el => el.classList.remove('message-highlight'));
                
                // Scroll to message
                targetMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Highlight the message after scrolling
                setTimeout(() => {
                    targetMsg.classList.add('message-highlight');
                    setTimeout(() => {
                        targetMsg.classList.remove('message-highlight');
                    }, 3000);
                }, 500);
            } else {
                // If message not found, reload messages and try again
                if (currentUserId) {
                    loadMessages(currentUserId);
                    setTimeout(() => {
                        scrollToOriginalMessage(messageId);
                    }, 500);
                } else if (currentGroupId) {
                    // Reload group messages if in group chat
                    const groupId = currentGroupId;
                    fetch('/group-chat/messages/' + groupId)
                        .then(res => res.json())
                        .then(messages => {
                            // Re-render messages (this will be handled by the group click handler)
                            setTimeout(() => {
                                scrollToOriginalMessage(messageId);
                            }, 500);
                        });
                }
            }
        }

        // Cancel reply button
        document.getElementById('cancelReply').addEventListener('click', function() {
            cancelReply();
        });

        // Translate button click handler
        $(document).on('click', '.message-translate-btn', function(e) {
            e.stopPropagation();
            e.preventDefault();
            const $btn = $(this);
            const msgId = $btn.attr('data-message-id');
            const rawText = ($btn.attr('data-message-text') || '').replace(/^📎\s*/, '').trim();
            const targetLang = $('#translateTargetLang').val() || 'Hindi';
            const $msg = $btn.closest('.message');
            const $translatedDiv = $msg.find('.message-translated');
            if (translatedMessages[msgId]) {
                delete translatedMessages[msgId];
                $translatedDiv.hide().empty();
                return;
            }
            if (!rawText) {
                if (typeof toastr !== 'undefined') toastr.warning('No text to translate');
                else alert('No text to translate');
                return;
            }
            const origHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            $.post('{{ route("lab.chat.translate") }}', { text: rawText, target_language: targetLang, _token: '{{ csrf_token() }}' })
                .done(function(data) {
                    if (data.success && data.translated) {
                        translatedMessages[msgId] = { lang: targetLang, text: data.translated };
                        $translatedDiv.html('<small class="text-muted">' + (typeof escapeHtml !== 'undefined' ? escapeHtml(targetLang) : targetLang) + ':</small> ' + (typeof escapeHtml !== 'undefined' ? escapeHtml(data.translated) : data.translated)).show();
                    } else {
                        if (typeof toastr !== 'undefined') toastr.error(data.message || 'Translation failed');
                        else alert(data.message || 'Translation failed');
                    }
                })
                .fail(function() {
                    if (typeof toastr !== 'undefined') toastr.error('Translation request failed');
                    else alert('Translation request failed');
                })
                .always(function() {
                    $btn.prop('disabled', false).html(origHtml);
                });
        });
        
        // Reply button click handler - WhatsApp style
        $(document).on('click', '.message-reply-btn', function(e) {
            e.stopPropagation();
            e.preventDefault();
            const messageId = $(this).attr('data-message-id') || $(this).data('message-id');
            const senderName = $(this).attr('data-sender-name') || $(this).data('sender-name') || 'Unknown';
            const messageText = $(this).attr('data-message-text') || $(this).data('message-text') || '';
            
            if (!messageId) {
                console.error('Message ID not found');
                return;
            }
            
            setReplyTo(messageId, senderName, messageText);
        });

        // Show reply and translate buttons on hover
        $(document).on('mouseenter', '.message', function() {
            $(this).find('.message-reply-btn, .message-translate-btn').css('opacity', '1');
        }).on('mouseleave', '.message', function() {
            $(this).find('.message-reply-btn, .message-translate-btn').css('opacity', '0');
        });

        // Make scrollToOriginalMessage available globally
        window.scrollToOriginalMessage = scrollToOriginalMessage;

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

        const usersListElement = document.getElementById('usersList');
        if (usersListElement) {
            usersListElement.addEventListener('click', function() {
                showChatMobile();
            });
        }

        window.addEventListener('resize', function() {
            if (window.innerWidth > 480) {
                document.querySelector('.mobile-chat-wrapper').classList.remove('show-list', 'show-chat');
            }
        });
    
        // Open specific user chat when URL has ?open_user=ID
        (function() {
            const params = new URLSearchParams(window.location.search);
            const openUserId = params.get('open_user');
            if (openUserId) {
                setTimeout(function() {
                    const userItem = document.querySelector('.chat-user-item[data-user-id="' + openUserId + '"]:not(.group-item)');
                    if (userItem) {
                        userItem.scrollIntoView({ block: 'nearest', behavior: 'auto' });
                        userItem.click();
                        if (typeof showChatMobile === 'function') {
                            showChatMobile();
                        }
                        if (window.history && window.history.replaceState) {
                            window.history.replaceState({}, document.title, window.location.pathname);
                        }
                    }
                }, 450);
            }
        })();
    
    });</script>

@endsection

