<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Role - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .role-select-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        .role-btn {
            width: 100%;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            border: 2px solid #f1f1f1;
            background: white;
            transition: all 0.3s ease;
            text-align: left;
            position: relative;
        }
        .role-btn:hover {
            border-color: #F7941D;
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        .role-btn i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #F7941D;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .role-btn:hover i {
            opacity: 1;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo-container img {
            max-width: 150px;
            height: auto;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="role-select-card">
                    <div class="logo-container">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" onerror="this.src='https://via.placeholder.com/150x50?text=CRM'">
                    </div>
                    <h4 class="text-center mb-4">Select Your Role</h4>

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('login.role.select') }}" method="POST">
                        @csrf
                        @foreach($roles as $role)
                            <button type="submit" name="role" value="{{ $role }}" class="role-btn">
                                {{ $role }}
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        @endforeach
                    </form>

                    <div class="text-center mt-4">
                        <a href="{{ route('logout') }}" class="text-muted text-decoration-none">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
