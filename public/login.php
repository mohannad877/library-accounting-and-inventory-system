<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (!empty($_SESSION['user_id'])) {
    redirect('../index.php');
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token, 'login_form')) {
        $error_message = 'رمز الحماية غير صالح، يرجى إعادة المحاولة.';
    } else {
        $username = sanitize_string($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $isHashed = str_starts_with($user['password'], '$2y$') || str_starts_with($user['password'], '$argon2');
            $isValid = $isHashed ? password_verify($password, $user['password']) : hash_equals($user['password'], $password);

            if ($isValid) {
                if (!$isHashed) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $newHash, $user['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                }

                regenerate_session_id_once();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                redirect('../index.php');
            } else {
                $error_message = "بيانات الدخول غير صحيحة.";
            }
        } else {
            $error_message = "بيانات الدخول غير صحيحة.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; margin: 0; padding-top: 44px; min-height: 100vh; display: flex; flex-direction: column; }
        .container {
            max-width: 400px;
            width: 90%; /* تجعلها متجاوبة */
            margin: 40px auto; /* تحتفظ بالتوسيط الأفقي وتضبط الهامش العلوي */
            margin-top: 130px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); /* ظل أنعم */
            border: 1px solid #e0e0e0;
            padding: 32px 18px 24px 18px; /* padding متناسق */
            box-sizing: border-box; /* للتأكد من أن padding لا يزيد العرض الكلي */
            display: flex; /* لتمكين flexbox داخل الـ container */
            flex-direction: column;
            justify-content: center;
            align-items: center; /* توسيط المحتوى داخل الـ container */
        }
        h2 { text-align: center; color: #333; margin-bottom: 24px; font-size: 1.13em; margin-top: 0; }
        label { display: block; margin-bottom: 6px; color: #555; font-weight: 600; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: #f8f8f8;
            color: #333;
            transition: border 0.2s;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: #999;
            outline: none;
        }
        button[type="submit"] {
            width: 100%;
            background: #666;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px 0;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        button[type="submit"]:hover {
            background: #444;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 18px;
            text-align: center;
            direction: rtl;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h2>تسجيل الدخول</h2>
        <?php if (!empty($error_message)): ?>
            <div class="error-msg"><?= e($error_message) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token('login_form')) ?>">
            <label for="username">اسم المستخدم:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">كلمة المرور:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">تسجيل الدخول</button>
        </form>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 
<?php $conn->close(); ?>