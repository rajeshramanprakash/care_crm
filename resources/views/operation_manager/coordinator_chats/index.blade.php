@extends('operation_manager.layouts.app')

@section('title', 'Operation Staff Chats')

@section('header-css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css">
    <style>
        .content-wrapper {
            background-color: #f4f6f9;
            min-height: 100vh;
            margin-left: 250px;
            padding-top: 1rem;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 20px;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-body {
            padding: 20px;
        }

        @media (max-width: 991.98px) {
            .content-wrapper {
                margin-left: 0;
            }
        }

        :root {
            --chat-primary: #0084ff;
            --chat-secondary: #e9ecef;
        }

        .chat-messages {
            height: calc(100vh - 100px);
            overflow-y: auto;
            padding: 1rem;
        }

        .chat-user-item {
            transition: all 0.3s ease;
        }

        .chat-user-item:hover {
            background-color: var(--chat-secondary);
        }

        .chat-participant-link {
            padding: 0.75rem 1rem;
            border: none;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .chat-participant-link:hover {
            background-color: var(--chat-secondary);
            text-decoration: none;
        }

        .chat-participant-link.active {
            background-color: var(--chat-primary);
            color: white;
        }

        .chat-participant-link.active small {
            color: rgba(255, 255, 255, 0.8);
        }

        .chat-message {
            display: flex;
            margin-bottom: 1rem;
        }

        .chat-message.outgoing {
            flex-direction: row-reverse;
        }

        .chat-message .message-content {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            position: relative;
            margin: 0 1rem;
            font-size: 14px;
        }

        .chat-message.incoming .message-content {
            background-color: var(--chat-secondary);
        }

        .chat-message.outgoing .message-content {
            background-color: var(--chat-primary);
            color: white;
        }

        .message-time {
            font-size: 0.75rem;
            color: #000000;
        }

        .chat-message.outgoing .message-time {
            text-align: right;
        }

        .last-message {
            font-size: 0.85rem;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .unread-badge {
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            font-size: 12px;
            line-height: 20px;
            border-radius: 10px;
            text-align: center;
            background-color: #dc3545;
            color: white;
        }
    </style>
@endsection

@section('main')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Operation Staff Chats</h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- Chat Sidebar -->
                    <div class="col-md-4 col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="card-title m-0">Operation Staff List</h3>
                                    <div class="dropdown">
                                        <button class="btn btn-link text-dark" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <button class="dropdown-item sort-btn" data-sort="newest" type="button">Newest</button>
                                            <button class="dropdown-item sort-btn" data-sort="oldest" type="button">Oldest</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="input-group mt-3">
                                    <input type="text" class="form-control" placeholder="Search coordinators..." id="searchInput">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0" style="height: calc(100vh - 100px); overflow-y: auto;">
                                <div class="list-group list-group-flush">
                                    @foreach($coordinators as $coordinator)
                                    <div class="list-group-item p-0">
                                        <div class="chat-user-item" data-user-id="{{ $coordinator->id }}">
                                            <div class="d-flex align-items-center p-3" style="cursor: pointer;"
                                                 data-bs-toggle="collapse"
                                                 data-bs-target="#chatList{{ $coordinator->id }}">
                                                <div class="position-relative">
                                                    <img src="{{ $coordinator->profile_image ? asset('storage/'.$coordinator->profile_image) : asset('images/default-user.png') }}"
                                                         class="rounded-circle"
                                                         alt="User Image"
                                                         style="width: 40px; height: 40px; object-fit: cover; margin-right:10px;">
                                                    {{-- <span class="position-absolute bottom-0 end-0 bg-success rounded-circle"
                                                          style="width: 10px; height: 10px;"></span> --}}
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="mb-0" style="font-size: 14px;">{{ $coordinator->f_name }} {{ $coordinator->l_name }}</h6>
                                                        <small class="text-muted">{{ $coordinator->role->name }}</small>
                                                    </div>
                                                    <small class="text-muted">{{ $coordinator->email }}</small>
                                                </div>
                                            </div>
                                            <div class="collapse" id="chatList{{ $coordinator->id }}">
                                                <div class="list-group list-group-flush">
                                                    @foreach($coordinator->chatParticipants as $participant)
                                                    <a href="#" class="list-group-item list-group-item-action chat-participant-link"
                                                       data-coordinator-id="{{ $coordinator->id }}"
                                                       data-participant-id="{{ $participant->id }}"
                                                       data-participant-name="{{ $participant->f_name }} {{ $participant->l_name }}">
                                                        <div class="d-flex align-items-center">
                                                            <div class="position-relative">
                                                                <img src="{{ $participant->profile_image ? asset('storage/'.$participant->profile_image) : asset('images/default-user.png') }}"
                                                                     class="rounded-circle"
                                                                     alt="User Image"
                                                                     style="width: 32px; height: 32px; object-fit: cover; margin-right:10px;">
                                                            </div>
                                                            <div class="ms-3 flex-grow-1">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <h6 class="mb-0" style="font-size: 12px">{{ $participant->f_name }} {{ $participant->l_name }}</h6>
                                                                    @if($participant->lastMessage)
                                                                        <small class="message-time">
                                                                            {{ \Carbon\Carbon::parse($participant->lastMessage->created_at)->diffForHumans() }}
                                                                        </small>
                                                                    @endif
                                                                </div>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div class="last-message">
                                                                        @if($participant->lastMessage)
                                                                            {{ $participant->lastMessage->message }}
                                                                        @else
                                                                            No messages yet
                                                                        @endif
                                                                    </div>
                                                                    @if($participant->unreadCount > 0)
                                                                        <span class="unread-badge">{{ $participant->unreadCount }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Content -->
                    <div class="col-md-8 col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <div id="selectedUserInfo" style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <div class="position-relative">
                                                <img src="" id="selectedUserImage" class="rounded-circle"
                                                     alt="User Image" style="width: 40px; height: 40px; object-fit: cover; margin-right:10px;">
                                                {{-- <span class="position-absolute bottom-0 end-0 bg-success rounded-circle"
                                                      style="width: 10px; height: 10px;"></span> --}}
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="mb-0" id="selected-user-name"></h6>
                                                <small class="text-success">Active now</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="welcomeMessage" class="text-center w-100">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="mb-3">
                                                <i class="fas fa-comments fa-3x text-muted"></i>
                                            </div>
                                            <h4>Welcome to Operation Chat</h4>
                                            <p class="text-muted">Select a conversation to start viewing</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0" id="chatContainer" style="display: none; height: calc(100vh - 100px); overflow-y: auto;">
                                <div class="chat-messages" id="chat-messages">
                                    <!-- Messages will be loaded here -->
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
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.0/js/dataTables.responsive.min.js"></script>
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script>
        let selectedCoordinatorId = null;
        let selectedParticipantId = null;

        function loadMessages(coordinatorId, participantId) {
            $.get(`{{ route('operation-manager.coordinator.chats.messages', ['coordinatorId' => ':coordinatorId', 'participantId' => ':participantId']) }}`.replace(':coordinatorId', coordinatorId).replace(':participantId', participantId))
                .done(function(messages) {
                    const messagesContainer = $('#chat-messages');
                    messagesContainer.empty();

                    messages.forEach(message => {
                        const isFromCoordinator = message.sender_id == coordinatorId;
                        const messageHtml = `
                            <div class="chat-message ${isFromCoordinator ? 'outgoing' : 'incoming'}">
                                <div class="message-content">
                                    ${message.message}
                                    <div class="message-time">
                                        ${moment(message.created_at).format('LLL')}
                                    </div>
                                </div>
                            </div>
                        `;
                        messagesContainer.append(messageHtml);
                    });

                    messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
                })
                .fail(function(error) {
                    console.error('Error loading messages:', error);
                    toastr.error('Failed to load messages. Please try again.');
                });
        }

        function updateUnreadCounts() {
            $.get('{{ route('operation-manager.coordinator.chats.unread-counts') }}')
                .done(function(counts) {
                    $('.unread-badge').hide();
                    Object.entries(counts).forEach(([userId, count]) => {
                        if (count > 0) {
                            $(`.chat-participant-link[data-participant-id="${userId}"] .unread-badge`)
                                .text(count)
                                .show();
                        }
                    });
                })
                .fail(function(error) {
                    console.error('Error updating unread counts:', error);
                });
        }

        $(document).ready(function() {
            // Search functionality
            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('.chat-user-item').each(function() {
                    const userName = $(this).find('h6').text().toLowerCase();
                    const userEmail = $(this).find('small').text().toLowerCase();
                    if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
                        $(this).closest('.list-group-item').show();
                    } else {
                        $(this).closest('.list-group-item').hide();
                    }
                });
            });

            // Sort functionality
            $('.sort-btn').click(function() {
                const sortType = $(this).data('sort');
                const $coordinatorList = $('.list-group');
                const $coordinators = $coordinatorList.children('.list-group-item').get();

                $coordinators.sort(function(a, b) {
                    const timeA = $(a).find('.message-time').first().text() || '0';
                    const timeB = $(b).find('.message-time').first().text() || '0';

                    if (sortType === 'newest') {
                        return timeA < timeB ? 1 : -1;
                    } else {
                        return timeA > timeB ? 1 : -1;
                    }
                });

                $coordinatorList.empty();
                $coordinators.forEach(function(item) {
                    $coordinatorList.append(item);
                });
            });

            // Handle chat participant selection
            $('.chat-participant-link').click(function(e) {
                e.preventDefault();
                $('.chat-participant-link').removeClass('active');
                $(this).addClass('active');

                selectedCoordinatorId = $(this).data('coordinator-id');
                selectedParticipantId = $(this).data('participant-id');
                const participantName = $(this).data('participant-name');
                const participantImage = $(this).find('img').attr('src');

                $('#welcomeMessage').hide();
                $('#selectedUserInfo').show();
                $('#chatContainer').show();
                $('#selected-user-name').text(participantName);
                $('#selectedUserImage').attr('src', participantImage);

                loadMessages(selectedCoordinatorId, selectedParticipantId);

                // Mark messages as read
                $.ajax({
                    url: `{{ route('operation-manager.coordinator.chats.mark-read', ['userId' => ':userId']) }}`.replace(':userId', selectedParticipantId),
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
            });

            // Initial unread counts
            updateUnreadCounts();

            // Update unread counts periodically
            if (window.ChatRealtime && window.chatRealtimeConfig && window.chatRealtimeConfig.enabled) {
                window.ChatRealtime.subscribeUserChannel({{ Auth::id() }}, function(payload, eventName) {
                    updateUnreadCounts();
                });
            } else if (window.chatRealtimeConfig && !window.chatRealtimeConfig.enabled) {
                setInterval(updateUnreadCounts, 30000);
            }
        });
    </script>
@endsection
