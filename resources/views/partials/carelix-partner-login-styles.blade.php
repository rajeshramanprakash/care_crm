<style>
    body { margin: 0; font-family: 'Source Sans Pro', Arial, sans-serif; background: #fff; min-height: 100vh; }
    .contain { display: flex; min-height: 100vh; }
    .left {
        background: #ea8a2b;
        color: #fff;
        flex: 1.2;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 40px 30px;
    }
    .right { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px 30px; background: #fff; }
    .logo img { max-width: 200px; }
    .login-box { width: 100%; max-width: 380px; }
    .login-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 8px; color: #222; }
    .btn-login {
        background: #ea8a2b;
        border-color: #ea8a2b;
        font-weight: 600;
    }
    .btn-login:hover, .btn-login:focus {
        background: #cf6413;
        border-color: #cf6413;
    }
    .form-control:focus {
        border-color: #ea8a2b;
        box-shadow: 0 0 0 0.2rem rgba(234, 138, 43, 0.25);
    }
    @media (max-width: 900px) {
        .contain { flex-direction: column; }
        .left, .right { flex: unset; width: 100%; min-height: 280px; }
    }
</style>
