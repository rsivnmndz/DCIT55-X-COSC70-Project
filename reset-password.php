<?php

session_start();

require_once __DIR__ . '/database/database.php';
require_once __DIR__ . '/auth/helpers.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($token === '') {
        redirect_with('forgot-password.html', 'Invalid or missing reset token.');
    }

    if (strlen($password) < 6) {
        redirect_with('reset-password.php?token=' . urlencode($token), 'Password must be at least 6 characters.');
    }

    if ($password !== $confirmPassword) {
        redirect_with('reset-password.php?token=' . urlencode($token), 'Passwords do not match.');
    }

    $stmt = $conn->prepare(
        'SELECT email FROM password_resets WHERE reset_token = ? AND expires_at > NOW() LIMIT 1'
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reset) {
        redirect_with('forgot-password.html', 'This reset link is invalid or has expired.');
    }

    $passwordHash = hash_password($password);
    $email = $reset['email'];

    $update = $conn->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $update->bind_param('ss', $passwordHash, $email);
    $update->execute();
    $update->close();

    $delete = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
    $delete->bind_param('s', $email);
    $delete->execute();
    $delete->close();

    redirect_with('login.html', null, 'Your password has been reset. You can sign in now.');
}

$tokenValid = false;
$errorMessage = $_GET['error'] ?? null;

if ($token !== '') {
    $stmt = $conn->prepare(
        'SELECT email FROM password_resets WHERE reset_token = ? AND expires_at > NOW() LIMIT 1'
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $tokenValid = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($token === '' || !$tokenValid) {
    redirect_with('forgot-password.html', 'This reset link is invalid or has expired.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HanapDormIndang Reset Password</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body {
            height: 100vh;
            background: url('./images/bg.png') no-repeat center center/cover;
            overflow: hidden;
            position: relative;
        }
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 0;
        }
        .page {
            position: relative;
            z-index: 1;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .title {
            text-align: center;
            color: white;
            font-size: 42px;
            font-weight: bold;
            line-height: 1.1;
            margin-bottom: 18px;
        }
        .card {
            width: 340px;
            padding: 30px 26px;
            border-radius: 22px;
            background: rgba(10, 15, 35, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .alert {
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 14px;
            font-size: 13px;
            line-height: 1.4;
        }
        .alert-error {
            background: rgba(255, 80, 80, 0.15);
            border: 1px solid rgba(255, 100, 100, 0.5);
            color: #ffb3b3;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #b8c3db;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.06);
            color: white;
            outline: none;
            margin-bottom: 14px;
        }
        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 30px;
            background: linear-gradient(to right, #00d9ff, #ffffff);
            color: black;
            font-weight: bold;
            cursor: pointer;
        }
        .back {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
        }
        .back a { color: #4fdcff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="page">
        <h1 class="title">Set New<br>Password</h1>
        <div class="card">
            <?php if ($errorMessage): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>
            <form method="POST" action="reset-password.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <label>New Password</label>
                <input type="password" name="password" placeholder="Enter new password" required minlength="6">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat new password" required minlength="6">
                <button type="submit">Update Password</button>
            </form>
            <div class="back">
                <a href="login.html">← Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
