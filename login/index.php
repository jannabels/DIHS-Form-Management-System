@'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIHS - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #16a34a; --primary-light: #dcfce7; --text: #1f2937; --text-light: #6b7280; --white: #ffffff; --shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Inter", sans-serif; background: #f9fafb; color: var(--text); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .login-container { background: var(--white); border-radius: 1rem; box-shadow: var(--shadow); width: 100%; max-width: 400px; padding: 2.5rem; position: relative; overflow: hidden; }
        .login-container::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--primary); }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo img { height: 80px; width: auto; margin-bottom: 1rem; }
        .logo h1 { font-size: 1.5rem; font-weight: 600; margin-bottom: 0.25rem; }
        .logo p { color: var(--text-light); font-size: 0.875rem; }
        .feedback { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1.25rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem; }
        .feedback.error { background: #fee2e2; color: #b91c1c; }
        .feedback.info  { background: #eff6ff; color: #1d4ed8; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.9375rem; font-family: inherit; background: #f9fafb; transition: all 0.2s; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(22,163,74,0.1); background: var(--white); }
        .password-field { position: relative; }
        .password-toggle { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-light); cursor: pointer; padding: 0.5rem; font-family: inherit; border-radius: 0.25rem; }
        .password-toggle:hover { color: var(--primary); background: var(--primary-light); }
        .forgot-password { display: block; text-align: right; margin-top: 0.5rem; font-size: 0.8125rem; color: var(--text-light); text-decoration: none; }
        .forgot-password:hover { color: var(--primary); text-decoration: underline; }
        .btn { display: block; width: 100%; padding: 0.75rem 1.5rem; background: var(--primary); color: white; border: none; border-radius: 0.5rem; font-size: 0.9375rem; font-weight: 500; font-family: inherit; cursor: pointer; transition: all 0.2s; }
        .btn:hover:not(:disabled) { background: #15803d; transform: translateY(-1px); }
        .btn:disabled { opacity: 0.7; cursor: not-allowed; }
        .server-notice { margin-top: 1.5rem; padding: 0.75rem 1rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 0.5rem; font-size: 0.8125rem; color: #92400e; text-align: center; line-height: 1.6; }
        .server-notice strong { display: block; margin-bottom: 0.25rem; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="../images/dihslogo.png" alt="DIHS Logo" onerror="this.style.display='none'">
            <h1>School Forms Management System</h1>
            <p>Dasmariñas Integrated High School</p>
        </div>
        <div id="messageContainer"></div>
        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" class="password-toggle" id="passwordToggle" title="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
            </div>
            <button id="loginBtn" type="submit" class="btn">Log In</button>
        </form>
        <div class="server-notice">
            <strong>⚠️ PHP backend required</strong>
            This app needs a PHP + MySQL server to log in.<br>
            Deploy on <a href="https://railway.app" target="_blank" style="color:#92400e">Railway</a> or a cPanel host instead of Vercel.
        </div>
    </div>
    <script>
        const toggle = document.getElementById("passwordToggle");
        const pwInput = document.getElementById("password");
        toggle.addEventListener("click", function() {
            const show = pwInput.type === "password";
            pwInput.type = show ? "text" : "password";
            toggle.querySelector("i").className = show ? "fas fa-eye-slash" : "fas fa-eye";
            toggle.title = show ? "Hide password" : "Show password";
        });
        document.getElementById("loginForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const u = document.getElementById("username").value.trim();
            const p = document.getElementById("password").value.trim();
            const btn = document.getElementById("loginBtn");
            const box = document.getElementById("messageContainer");
            if (!u || !p) { box.innerHTML = "<div class=\"feedback error\"><i class=\"fas fa-exclamation-circle\"></i> Please enter both username and password.</div>"; return; }
            btn.disabled = true;
            btn.innerHTML = "<i class=\"fas fa-spinner fa-spin\"></i> Logging in...";
            setTimeout(function() {
                box.innerHTML = "<div class=\"feedback info\"><i class=\"fas fa-info-circle\"></i> Login requires a PHP server. Vercel does not run PHP. Please contact your administrator.</div>";
                btn.disabled = false;
                btn.innerHTML = "Log In";
            }, 800);
        });
    </script>
</body>
</html>
'@ | Set-Content login\index.php