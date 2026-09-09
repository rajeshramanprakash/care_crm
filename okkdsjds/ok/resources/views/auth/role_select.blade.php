<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Role</title>
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #ffffff;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 20px;
            width: 100%;
            max-width: 600px;
            text-align: center;
        }

        h3 {
            margin-bottom: 20px;
            color: #343a40;
            font-size: 24px;
        }

        .role-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }

        .role-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            background-color: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .role-card:hover {
            border-color: #F7941D;
            background-color: #e7f1ff;
            transform: translateY(-5px);
        }

        .role-card.selected {
            border-color: #28a745;
            background-color: #eafaea;
            color: #28a745;
        }

        button {
            background-color: #F7941D;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>Select Your Role</h3>

        <!-- Role Selection Form -->
        <form method="POST" action="{{ route('login.role.select') }}" id="role-form">
            @csrf
            <input type="hidden" name="role" id="selected-role">
            <input type="hidden" name="user_id" value="{{ $user->id }}">

            <div class="role-cards">
                @foreach ($roles as $role)
                    <div class="role-card" data-role="{{ $role->id }}">
                        {{ $role->name }}
                    </div>
                @endforeach
            </div>

            <button type="submit">Proceed</button>
        </form>
    </div>

    <script>
        const roleCards = document.querySelectorAll('.role-card');
        const hiddenInput = document.getElementById('selected-role');

        roleCards.forEach(card => {
            card.addEventListener('click', () => {
                roleCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                hiddenInput.value = card.getAttribute('data-role');
            });
        });
        document.getElementById('role-form').addEventListener('submit', function (e) {
            if (!hiddenInput.value) {
                alert('Please select a role before proceeding.');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
