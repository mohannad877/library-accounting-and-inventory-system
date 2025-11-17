<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_login('admin');
include "../header.php";
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم المدير</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; margin: 0; padding: 0; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px #0001; border: 1px solid #e0e0e0; padding: 32px 18px; }
        h2 { text-align: center; color: #222; margin-bottom: 24px; font-size: 1.18em; margin-top: 32px; }
        .admin-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 25px;
        }
        .admin-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: #444;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 15px 0;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 1px 4px #0001;
            transition: background 0.18s, transform 0.13s;
        }
        .admin-btn:hover {
            background: #222;
            transform: translateY(-1px) scale(1.01);
        }
        .admin-btn svg {
            width: 20px;
            height: 20px;
            fill: #fff;
        }
        @media (max-width: 600px) {
            .admin-links { grid-template-columns: 1fr; gap: 10px; }
            .admin-btn { padding: 12px 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>لوحة تحكم المدير</h2>
        <p style="text-align:center;color:#444;">مرحباً بك يا مدير! من هنا يمكنك إدارة النظام.</p>
        <div class="admin-links">
            <a href="manage_users.php" class="admin-btn">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
                إدارة المستخدمين
            </a>
            <a href="../logout.php" class="admin-btn" style="background:#dc3545;">
                <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM2 5h10V3H2c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h10v-2H2V5z"></path></svg>
                تسجيل الخروج
            </a>
        </div>
    </div>
    <?php include '../footer.php'; ?>
</body>
</html> 