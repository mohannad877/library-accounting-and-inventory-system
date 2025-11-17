<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login('admin');
include 'header.php';

$invoice_number_filter = $_GET['invoice_number'] ?? '';
$day_filter = $_GET['day'] ?? '';
$month_filter = $_GET['month'] ?? '';
$year_filter = $_GET['year'] ?? '';

$sql = "SELECT t.invoice_number, t.quantity, t.purchase_price, t.transaction_date, b.title, t.stage
        FROM transactions t
        JOIN books b ON t.book_id = b.id
        WHERE t.type = 'وارد'"; // عرض فواتير الشراء فقط

$params = [];
$types = "";

if (!empty($invoice_number_filter)) {
    $sql .= " AND t.invoice_number LIKE ?";
    $params[] = "%" . $invoice_number_filter . "%";
    $types .= "s";
}

if (!empty($day_filter)) {
    $sql .= " AND DAY(t.transaction_date) = ?";
    $params[] = $day_filter;
    $types .= "i";
}

if (!empty($month_filter)) {
    $sql .= " AND MONTH(t.transaction_date) = ?";
    $params[] = $month_filter;
    $types .= "i";
}

if (!empty($year_filter)) {
    $sql .= " AND YEAR(t.transaction_date) = ?";
    $params[] = $year_filter;
    $types .= "i";
}

$sql .= " ORDER BY t.transaction_date DESC, t.invoice_number DESC";

error_log("SQL Query in view_invoices: " . $sql);
if (!empty($params)) {
    error_log("SQL Params: " . implode(", ", $params) . " | Types: " . $types);
}

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("Failed to prepare statement for view_invoices: " . $conn->error);
    $invoices = [];
    $error_message = "حدث خطأ في قاعدة البيانات. يرجى المحاولة لاحقاً.";
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    error_log("Number of rows fetched: " . $result->num_rows);
    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }
    error_log("Invoices array content: " . print_r($invoices, true));
    $stmt->close();
}
$conn->close();

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>عرض فواتير الشراء</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; margin: 0; padding-top: 44px;}
        .container { max-width: 90%; margin: 50px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0; padding: 32px 18px 24px 18px; }
        h2 { text-align: center; color: #333; margin-bottom: 24px; font-size: 1.13em; margin-top: 0; }
        .filters-form {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #fdfdfd;
            flex-wrap: wrap;
            justify-content: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .filters-form label { margin-bottom: 5px; font-weight: 600; color: #555; }
        .filters-form input[type="text"], .filters-form select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 0.9em;
            flex: 1;
            min-width: 120px;
            background: #f8f8f8;
            color: #333;
        }
        .filters-form button {
            padding: 10px 20px;
            background-color: #666;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        .filters-form button:hover { background-color: #444; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        th { background-color: #444; color: #fff; font-weight: 600; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f0f0f0; }
        .no-records { text-align: center; color: #777; padding: 30px; font-size: 1.1em; }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .alert.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
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
            .filters-form label {
                font-size: 1.18em;
                margin-bottom: 8px;
            }
            .filters-form input[type="text"], .filters-form select {
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
        }
    </style>
</head>
<body>
    <div class="container container-large">
        <h2>فواتير الشراء (الوارد)</h2>
        <div style="display: flex; justify-content: center; margin-bottom: 18px;">
            <form method="get" action="export_invoices.php" style="margin: 0;">
                <input type="hidden" name="invoice_number" value="<?= htmlspecialchars($invoice_number_filter) ?>">
                <input type="hidden" name="day" value="<?= htmlspecialchars($day_filter) ?>">
                <input type="hidden" name="month" value="<?= htmlspecialchars($month_filter) ?>">
                <input type="hidden" name="year" value="<?= htmlspecialchars($year_filter) ?>">
                <button type="submit" style="background: #218838; color: #fff; padding: 7px 18px; font-size: 1em; border-radius: 7px; border: none; margin-bottom: 0; cursor: pointer;">تصدير إلى CSV</button>
            </form>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert error"><?= $error_message ?></div>
        <?php endif; ?>

        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert error">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>

        <form method="GET" action="" class="filters-form">
            <input type="text" name="invoice_number" placeholder="رقم الفاتورة" value="<?= htmlspecialchars($invoice_number_filter) ?>">

            <select name="day">
                <option value="">يوم</option>
                <?php for ($d = 1; $d <= 31; $d++): ?>
                    <option value="<?= $d ?>" <?= ($day_filter == $d) ? 'selected' : '' ?>><?= $d ?></option>
                <?php endfor; ?>
            </select>

            <select name="month">
                <option value="">شهر</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($month_filter == $m) ? 'selected' : '' ?>><?= $m ?></option>
                <?php endfor; ?>
            </select>

            <select name="year">
                <option value="">سنة</option>
                <?php 
                    $current_year = date('Y');
                    for ($y = $current_year; $y >= $current_year - 5; $y--): // آخر 5 سنوات
                ?>
                    <option value="<?= $y ?>" <?= ($year_filter == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>

            <button type="submit">تصفية</button>
            <button type="button" onclick="window.location.href='view_invoices.php'">إعادة تعيين</button>
        </form>

        <?php if (!empty($invoices)): ?>
        <table>
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>عنوان الكتاب</th>
                    <th>المرحلة</th>
                    <th>الكمية</th>
                    <th>سعر الشراء الإفرادي</th>
                    <th>تاريخ العملية</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?= htmlspecialchars($invoice['invoice_number']) ?></td>
                    <td><?= htmlspecialchars($invoice['title']) ?></td>
                    <td><?= htmlspecialchars($invoice['stage'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($invoice['quantity']) ?></td>
                    <td><?= htmlspecialchars(number_format($invoice['purchase_price'], 2)) ?></td>
                    <td><?= htmlspecialchars($invoice['transaction_date']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="no-records">لا توجد فواتير شراء مطابقة للمعايير المحددة.</p>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 