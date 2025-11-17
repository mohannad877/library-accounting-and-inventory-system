<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
include 'header.php';

$error_message = '';
$transactions = [];
$flash_message = null;
$flash_type = null;

if (isset($_SESSION['message'])) {
    $flash_message = $_SESSION['message'];
    $flash_type = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message'], $_SESSION['message_type']);
}

$sql = "SELECT t.*, b.title FROM transactions t JOIN books b ON t.book_id = b.id ORDER BY t.transaction_date DESC";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("خطأ في إعداد استعلام عرض العمليات: " . $conn->error);
    $error_message = "حدث خطأ أثناء إعداد جلب بيانات العمليات. يرجى المحاولة لاحقاً.";
} else {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>عرض عمليات البيع والصرف</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; margin: 0; padding-top: 44px; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0; padding: 32px 18px; }
        h2 { text-align: center; color: #333; margin-bottom: 24px; font-size: 1.18em; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; background: #fff; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center; color: #333; }
        th { background: #ececec; color: #444; font-weight: bold; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f0f0f0; }
        .action-btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
            transition: background-color 0.2s;
            margin: 0 2px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .action-btn.edit { background-color: #e0e0e0; color: #333; border: 1px solid #ccc; }
        .action-btn.edit:hover { background-color: #d0d0d0; }
        .action-btn.delete { background-color: #dc3545; color: white; border: 1px solid #bb2d3b; }
        .action-btn.delete:hover { background-color: #c82333; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 6px; text-align: center; font-weight: 600; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #b7dfc5; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        @media (max-width: 700px) {
            .container {
                max-width: 99vw;
                margin: 3vw auto 0 auto;
                padding: 10px 3vw 18px 3vw;
                border-radius: 9px;
            }
            h2 {
                font-size: 1.25em;
                margin-top: 14px;
                margin-bottom: 16px;
            }
            table, th, td {
                font-size: 18px !important;
                padding: 13px 7px !important;
            }
            th, td {
                min-width: 60px;
            }
            .action-btn {
                font-size: 1.18em;
                padding: 15px 0;
                border-radius: 9px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container container-large">
        <h2>عرض عمليات البيع والصرف</h2>
        <div style="display: flex; justify-content: center; margin-bottom: 18px;">
            <form method="get" action="export_transactions.php" style="margin: 0;">
                <input type="hidden" name="year" value="<?= isset($_GET['year']) ? htmlspecialchars($_GET['year']) : '' ?>">
                <input type="hidden" name="month" value="<?= isset($_GET['month']) ? htmlspecialchars($_GET['month']) : '' ?>">
                <input type="hidden" name="day" value="<?= isset($_GET['day']) ? htmlspecialchars($_GET['day']) : '' ?>">
                <button type="submit" style="background: #218838; color: #fff; padding: 7px 18px; font-size: 1em; border-radius: 7px; border: none; margin-bottom: 0; cursor: pointer;">تصدير إلى CSV</button>
            </form>
        </div>
        <?php if ($flash_message): ?>
            <div class="alert <?= $flash_type === 'error' ? 'error' : 'success' ?>"><?= e($flash_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert error"><?= $error_message ?></div>
        <?php endif; ?>
        <table>
            <tr>
                <th>اسم الكتاب</th>
                <th>نوع العملية</th>
                <th>الكمية</th>
                <th>التاريخ</th>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <th>تعديل</th>
                    <th>حذف</th>
                <?php endif; ?>
            </tr>
            <?php if (!empty($transactions)): ?>
                <?php foreach($transactions as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['type']) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td><?= $row['transaction_date'] ?></td>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <td><a href="edit_transaction.php?id=<?= $row['id'] ?>" class="action-btn edit">تعديل</a></td>
                        <td>
                            <?php
                                $deleteCsrfKey = 'delete_transaction_' . $row['id'];
                                $deleteCsrf = csrf_token($deleteCsrfKey);
                            ?>
                            <form method="post" action="delete_transaction.php" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه العملية؟');">
                                <input type="hidden" name="transaction_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="csrf_key" value="<?= e($deleteCsrfKey) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e($deleteCsrf) ?>">
                                <button type="submit" class="action-btn delete" style="border:none;">حذف</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center;">لا توجد عمليات لعرضها.</td></tr>
            <?php endif; ?>
        </table>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?> 