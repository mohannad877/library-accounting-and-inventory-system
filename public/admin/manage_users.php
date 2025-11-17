<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_login('admin');

$message = '';
$message_type = ''; // إضافة متغير لنوع الرسالة

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_post();
    $action = $_POST['action'] ?? 'save_user';
    $csrf_key = $_POST['csrf_key'] ?? 'manage_users_save';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', $csrf_key)) {
        $message = "طلب غير صالح (CSRF).";
        $message_type = "error";
    } else {
        if ($action === 'delete_user') {
            $delete_id = intval($_POST['user_id'] ?? 0);
            if ($delete_id <= 0) {
                $message = "معرف المستخدم غير صالح.";
                $message_type = "error";
            } elseif ($delete_id == $_SESSION['user_id']) {
                $message = "لا يمكنك حذف حسابك الحالي.";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $delete_id);
                if ($stmt->execute()) {
                    $message = "تم حذف المستخدم بنجاح.";
                    $message_type = "success";
                } else {
                    $message = "خطأ في حذف المستخدم: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            }
        } else {
            $username = sanitize_string($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $user_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;

            if (strlen($username) < 3) {
                $message = "اسم المستخدم يجب أن يكون 3 أحرف على الأقل.";
                $message_type = "error";
            } elseif (!$user_id && empty($password)) {
                $message = "كلمة المرور مطلوبة عند إضافة مستخدم جديد.";
                $message_type = "error";
            } elseif (!in_array($role, ['user', 'admin'])) {
                $message = "الدور المحدد غير صالح.";
                $message_type = "error";
            } else {
                if ($user_id) {
                    $dup_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id <> ?");
                    $dup_stmt->bind_param("si", $username, $user_id);
                    $dup_stmt->execute();
                    $dup_result = $dup_stmt->get_result();
                    if ($dup_result && $dup_result->num_rows > 0) {
                        $message = "اسم المستخدم مستخدم من قبل حساب آخر.";
                        $message_type = "error";
                        $dup_stmt->close();
                    } else {
                        if (!empty($password)) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
                            $stmt->bind_param("sssi", $username, $hashed_password, $role, $user_id);
                        } else {
                            $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                            $stmt->bind_param("ssi", $username, $role, $user_id);
                        }
                        if ($stmt->execute()) {
                            $message = "تم تعديل المستخدم بنجاح.";
                            $message_type = "success";
                        } else {
                            $message = "خطأ في تعديل المستخدم: " . $stmt->error;
                            $message_type = "error";
                        }
                        $stmt->close();
                        $dup_stmt->close();
                    }
                } else {
                    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    $check_stmt->bind_param("s", $username);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();

                    if ($check_result->num_rows > 0) {
                        $message = "اسم المستخدم هذا موجود بالفعل.";
                        $message_type = "error";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                        $stmt->bind_param("sss", $username, $hashed_password, $role);
                        if ($stmt->execute()) {
                            $message = "تم إضافة المستخدم بنجاح.";
                            $message_type = "success";
                        } else {
                            $message = "خطأ في إضافة المستخدم: " . $stmt->error;
                            $message_type = "error";
                        }
                        $stmt->close();
                    }
                    $check_stmt->close();
                }
            }
        }
    }
}

// جلب المستخدمين لعرضهم في الجدول
$users_data = [];
$users_stmt = $conn->prepare("SELECT id, username, role FROM users ORDER BY username");
if ($users_stmt) {
    $users_stmt->execute();
    $result = $users_stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $users_data[] = $row;
    }
    $users_stmt->close();
} else {
    $message = "خطأ في جلب قائمة المستخدمين: " . $conn->error;
    $message_type = "error";
}

include "../header.php"; // تضمين الهيدر

$save_csrf_token = csrf_token('manage_users_save');
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إدارة المستخدمين</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; margin: 0; padding: 0; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px #0001; border: 1px solid #e0e0e0; padding: 32px 18px; }
        h2 { text-align: center; color: #222; margin-bottom: 24px; font-size: 1.18em; margin-top: 32px; }
        form { margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0; }
        label { display: block; margin-bottom: 6px; color: #444; font-weight: 600; }
        input[type="text"], input[type="password"], select {
            width: calc(100% - 22px); /* Adjust for padding and border */
            padding: 8px 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: #fff;
            color: #222;
            transition: border 0.2s;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="password"]:focus, select:focus {
            border-color: #444;
            outline: none;
        }
        button[type="submit"] {
            background: #444;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        button[type="submit"]:hover {
            background: #222;
        }
        .message-success {
            background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 18px; text-align: center; border: 1px solid #b7dfc5;
        }
        .message-error {
            background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 18px; text-align: center; border: 1px solid #f5c6cb;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center; }
        th { background: #ececec; color: #444; font-weight: bold; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f7f7fa; }
        .action-btns a {
            text-decoration: none;
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 0 3px;
            display: inline-block;
        }
        .action-btns form {
            display: inline;
            margin: 0;
        }
        .edit-btn { background: #007bff; }
        .delete-btn { background: #dc3545; }
        .edit-btn:hover { background: #0056b3; }
        .delete-btn:hover { background: #c82333; }
        @media (max-width: 768px) {
            .container { padding: 8px; }
            form { padding: 15px; }
            input[type="text"], input[type="password"], select { width: 100%; }
            th, td { font-size: 13px; padding: 8px 4px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>إدارة المستخدمين</h2>
        <?php if (!empty($message)): ?>
            <div class="message-<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <h3>إضافة / تعديل مستخدم</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="save_user">
            <input type="hidden" name="csrf_key" value="manage_users_save">
            <input type="hidden" name="csrf_token" value="<?= e($save_csrf_token) ?>">
            <input type="hidden" name="user_id" id="user_id">
            <label for="username">اسم المستخدم:</label>
            <input type="text" id="username" name="username" value="" required>

            <label for="password">كلمة المرور (اترك فارغاً لعدم التغيير عند التعديل):</label>
            <input type="password" id="password" name="password">

            <label for="role">الدور:</label>
            <select id="role" name="role" required>
                <option value="user">مستخدم عادي</option>
                <option value="admin">مدير</option>
            </select>

            <button type="submit" id="submit_btn">إضافة مستخدم</button>
        </form>

        <h3>قائمة المستخدمين</h3>
        <table>
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الدور</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users_data)): ?>
                    <?php foreach($users_data as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['role'] == 'admin' ? 'مدير' : 'مستخدم عادي') ?></td>
                        <td class="action-btns">
                            <a href="#" class="edit-btn" onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['role'], ENT_QUOTES) ?>')">تعديل</a>
                            <?php
                                $deleteKey = 'manage_users_delete_' . $user['id'];
                                $deleteToken = csrf_token($deleteKey);
                            ?>
                            <form method="post" action="" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="csrf_key" value="<?= e($deleteKey) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e($deleteToken) ?>">
                                <button type="submit" class="delete-btn" style="border:none; padding:5px 10px;">حذف</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">لا توجد مستخدمين لعرضهم.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function editUser(id, username, role) {
            document.getElementById('user_id').value = id;
            document.getElementById('username').value = username;
            document.getElementById('password').value = ''; // مسح حقل كلمة المرور عند التعديل
            document.getElementById('role').value = role;
            document.getElementById('submit_btn').textContent = 'تعديل المستخدم';
        }

        // إعادة تعيين النموذج عند تحميل الصفحة أو بعد الإرسال
        window.onload = function() {
            document.getElementById('user_id').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('role').value = 'user';
            document.getElementById('submit_btn').textContent = 'إضافة مستخدم';
        };
    </script>
    <?php include '../footer.php'; // تضمين الفوتر ?>
</body>
</html>
<?php $conn->close(); ?> 