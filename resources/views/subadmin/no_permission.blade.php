<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission nahi hai</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f6f7f9; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #333; }
        .box { background: #fff; border-radius: 12px; padding: 32px 36px; max-width: 460px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        .code { font-size: 44px; font-weight: 700; color: #F07F28; margin: 0 0 8px; }
        p { margin: 0 0 22px; line-height: 1.5; }
        .btn { display: inline-block; padding: 9px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 0 4px; }
        .btn-primary { background: #F07F28; color: #fff; }
        .btn-light { background: #eee; color: #333; }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">403</div>
        <p>{{ $message }}</p>
        @if($homeUrl)
            <a href="{{ $homeUrl }}" class="btn btn-primary">Mere pages par jayein</a>
        @endif
        <a href="{{ route('logout') }}" class="btn btn-light">Logout</a>
    </div>
</body>
</html>
