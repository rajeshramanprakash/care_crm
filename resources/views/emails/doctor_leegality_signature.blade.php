<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            color: #fff;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <p>Dear {{ $doctor->name }},</p>
        
        <p>Please review and sign your Service Agreement with {{ config('app.name') }}.</p>
        
        <p>You can securely review and sign the document by clicking the button below:</p>
        
        <p>
            <a href="{{ $signUrl }}" class="btn" style="color: white;">Review & Sign Agreement</a>
        </p>

        <p>If the button above does not work, copy and paste the following link into your browser:</p>
        <p><a href="{{ $signUrl }}">{{ $signUrl }}</a></p>
        
        <p>Thank you,<br>
        {{ config('app.name') }} Team</p>
    </div>
</body>
</html>
