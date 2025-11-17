<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

// جلب المدارس المميزة
$flash_message = null;
$flash_type = null;

if (isset($_SESSION['success_message'])) {
    $flash_message = $_SESSION['success_message'];
    $flash_type = 'success';
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $flash_message = $_SESSION['error_message'];
    $flash_type = 'error';
    unset($_SESSION['error_message']);
}

$schools_query = "SELECT DISTINCT school_name FROM sales ORDER BY school_name";
$schools_result = $conn->query($schools_query);
if (!$schools_result) {
    error_log("خطأ في جلب أسماء المدارس: " . $conn->error);
    die("حدث خطأ أثناء جلب أسماء المدارس.");
}

// تصفية حسب المدرسة
$where_clause_parts = [];
$params = [];
$types = '';

// فرض تصفية النوع ليكون "صرف" دائمًا لهذه الصفحة
$where_clause_parts[] = "s.type = ?";
$params[] = "صرف";
$types .= "s";

if (isset($_GET['school']) && $_GET['school'] != '') {
    $school = $_GET['school'];
    $where_clause_parts[] = "s.school_name = ?";
    $params[] = $school;
    $types .= "s";
}

$where_clause = '';
if (!empty($where_clause_parts)) {
    $where_clause = "WHERE " . implode(" AND ", $where_clause_parts);
}

// جلب الفواتير مع إجمالي المبلغ والمبلغ المدفوع
$sql_sales = "SELECT s.id, s.school_name, s.sale_date, s.paid_amount, SUM(si.price) as total_bill_amount
              FROM sales s
              JOIN sales_items si ON s.id = si.sale_id
              $where_clause
              GROUP BY s.id, s.school_name, s.sale_date, s.paid_amount
              ORDER BY s.sale_date DESC";

$stmt_sales = $conn->prepare($sql_sales);
if (!$stmt_sales) {
    error_log("خطأ في إعداد استعلام جلب الفواتير: " . $conn->error);
    die("حدث خطأ أثناء جلب الفواتير.");
}

if (!empty($params)) {
    $stmt_sales->bind_param($types, ...$params);
}

$stmt_sales->execute();
$sales = $stmt_sales->get_result();
if (!$sales) {
    error_log("خطأ في تنفيذ استعلام جلب الفواتير: " . $stmt_sales->error);
    die("حدث خطأ أثناء جلب الفواتير.");
}

?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>متابعة عمليات البيع للمدارس</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; padding-top: 44px; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0; padding: 32px 18px; }
        h2 { text-align: center; color: #333; margin-bottom: 24px; font-size: 1.18em; margin-top: 0; }
        .filter { margin-bottom: 18px; text-align: center; }
        select { padding: 7px 8px; border: 1px solid #ccc; border-radius: 6px; min-width: 90px; background: #f8f8f8; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; background: #fff; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center; color: #333; }
        th { background: #ececec; color: #444; font-weight: bold; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f0f0f0; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 6px; text-align: center; font-weight: 600; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #b7dfc5; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .details { background: #f9f9ff; border-radius: 8px; margin: 10px 0 20px 0; padding: 10px 18px; border: 1px solid #e0e0e0; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
        .details table { background: #f9f9ff; }
        .action-btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
            transition: background-color 0.2s;
            margin: 0 2px 5px 2px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .action-btn.primary { background-color: #e0e0e0; color: #333; border: 1px solid #ccc; }
        .action-btn.primary:hover { background-color: #d0d0d0; }
        .action-btn.success { background-color: #e0e0e0; color: #333; border: 1px solid #ccc; }
        .action-btn.success:hover { background-color: #d0d0d0; }
        .action-btn.danger { background-color: #dc3545; color: white; border: 1px solid #bb2d3b; }
        .action-btn.danger:hover { background-color: #c82333; }
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
            .filter label {
                font-size: 1.18em;
                margin-bottom: 8px;
            }
            select {
                font-size: 18px;
                padding: 15px 13px;
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
        <h2>متابعة عمليات البيع للمدارس</h2>
        <?php if ($flash_message): ?>
            <div class="alert <?= $flash_type ?>"><?= e($flash_message) ?></div>
        <?php endif; ?>
        <form method="get" class="filter">
            <label style="color:#555;font-weight:500;">تصفية حسب المدرسة:</label>
            <select name="school" onchange="this.form.submit()">
                <option value="">كل المدارس</option>
                <?php while($row = $schools_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($row['school_name']) ?>" <?= (isset($_GET['school']) && $_GET['school'] == $row['school_name']) ? 'selected' : '' ?>><?= htmlspecialchars($row['school_name']) ?></option>
                <?php endwhile; ?>
            </select>

            <!-- Removed type filter options as sales are typically 'صرف' -->
        </form>
        <table>
            <tr>
                <th>رقم الفاتورة</th>
                <th>اسم المدرسة</th>
                <th>تاريخ البيع</th>
                <th>تفاصيل</th>
                <th>الإجراءات</th>
            </tr>
            <?php while($sale = $sales->fetch_assoc()): ?>
            <?php
                $current_total_bill_amount = $sale['total_bill_amount'] ?? 0;
                $current_paid_amount = $sale['paid_amount'] ?? 0;
                $current_remaining_balance = $current_total_bill_amount - $current_paid_amount;
            ?>
            <tr>
                <td><?= $sale['id'] ?></td>
                <td><?= htmlspecialchars($sale['school_name']) ?></td>
                <td><?= $sale['sale_date'] ?></td>
                <td>
                    <?php
                    // جلب تفاصيل الفاتورة باستخدام الاستعلامات المُعدة
                    $sale_id = $sale['id'];
                    $sql_items = "SELECT si.*, b.title FROM sales_items si JOIN books b ON si.book_id = b.id WHERE si.sale_id = ?";
                    $stmt_items = $conn->prepare($sql_items);
                    if (!$stmt_items) {
                        error_log("خطأ في إعداد استعلام جلب تفاصيل الفاتورة: " . $conn->error);
                        die("حدث خطأ أثناء جلب تفاصيل الفاتورة.");
                    }
                    $stmt_items->bind_param("i", $sale_id);
                    $stmt_items->execute();
                    $items = $stmt_items->get_result();
                    if (!$items) {
                        error_log("خطأ في تنفيذ استعلام جلب تفاصيل الفاتورة: " . $stmt_items->error);
                        die("حدث خطأ أثناء جلب تفاصيل الفاتورة.");
                    }
                    ?>
                    <div class="details">
                        <table style="width:100%;background:#f9f9ff;">
                            <tr>
                                <th>الكتاب</th>
                                <th>المرحلة</th>
                                <th>الكمية</th>
                                <th>السعر</th>
                            </tr>
                            <?php while($item = $items->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['title']) ?></td>
                                <td><?= htmlspecialchars($item['stage'] ?? '-') ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= $item['price'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                        <table style="width:100%; background:#f9f9ff; margin-top: 10px; border-top: 1px solid #e0e0e0;">
                            <tr>
                                <th>الإجمالي (المفروض)</th>
                                <th>المبلغ المدفوع</th>
                                <th>المتبقي</th>
                            </tr>
                            <tr>
                                <td><b><?= number_format($current_total_bill_amount, 2) ?></b></td>
                                <td><b><?= number_format($current_paid_amount, 2) ?></b></td>
                                <td><b><?= number_format($current_remaining_balance, 2) ?></b></td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="text-align: center;">
                    <a href="edit_paid_amount.php?sale_id=<?= $sale['id'] ?>" class="action-btn primary">تعديل المبلغ المدفوع</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <a href="edit_sale.php?sale_id=<?= $sale['id'] ?>" class="action-btn success">تعديل الفاتورة</a>
                        <?php
                            $saleDeleteKey = 'delete_sale_' . $sale['id'];
                            $saleDeleteToken = csrf_token($saleDeleteKey);
                        ?>
                        <form method="post" action="delete_sale.php" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه الفاتورة؟');">
                            <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
                            <input type="hidden" name="csrf_key" value="<?= e($saleDeleteKey) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e($saleDeleteToken) ?>">
                            <button type="submit" class="action-btn danger" style="border:none;">حذف الفاتورة</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
<?php
$conn->close(); 