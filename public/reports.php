<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$error_message = '';

// جلب السنوات المتوفرة في المبيعات
$years = [];
$years_stmt = $conn->prepare("SELECT DISTINCT YEAR(sale_date) as year FROM sales ORDER BY year DESC");
if ($years_stmt) {
    $years_stmt->execute();
    $years_result = $years_stmt->get_result();
    if ($years_result) {
        while($row = $years_result->fetch_assoc()) {
            $years[] = $row['year'];
        }
    }
    $years_stmt->close();
} else {
    error_log("خطأ في إعداد استعلام جلب السنوات: " . $conn->error);
    $error_message = "حدث خطأ أثناء جلب السنوات المتاحة.";
}

// متغيرات الفلترة
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : (isset($years[0]) ? $years[0] : date('Y'));
// Default to current month if no month is selected, to show daily report for current month
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
// Default to 0 for day to show all days of the selected month if not explicitly selected
$selected_day = isset($_GET['day']) ? intval($_GET['day']) : 0;

// أسماء الشهور العربية
$months_names = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];

// جلب الأشهر المتوفرة في السنة المختارة
$months = [];
if ($selected_year) {
    $months_stmt = $conn->prepare("SELECT DISTINCT MONTH(sale_date) as month FROM sales WHERE YEAR(sale_date) = ? ORDER BY month");
    if ($months_stmt) {
        $months_stmt->bind_param("i", $selected_year);
        $months_stmt->execute();
        $months_result = $months_stmt->get_result();
        while($row = $months_result->fetch_assoc()) {
            $months[] = $row['month'];
        }
        $months_stmt->close();
    } else {
        error_log("خطأ في إعداد استعلام جلب الشهور: " . $conn->error);
        $error_message = "حدث خطأ أثناء جلب الشهور المتاحة.";
    }
}

// جلب الأيام المتوفرة في الشهر المختار
$days = [];
if ($selected_month && $selected_year) {
    $days_stmt = $conn->prepare("SELECT DISTINCT DAY(sale_date) as day FROM sales WHERE YEAR(sale_date) = ? AND MONTH(sale_date) = ? ORDER BY day");
    if ($days_stmt) {
        $days_stmt->bind_param("ii", $selected_year, $selected_month);
        $days_stmt->execute();
        $days_result = $days_stmt->get_result();
        while($row = $days_result->fetch_assoc()) {
            $days[] = $row['day'];
        }
        $days_stmt->close();
    } else {
        error_log("خطأ في إعداد استعلام جلب الأيام: " . $conn->error);
        $error_message = "حدث خطأ أثناء جلب الأيام المتاحة.";
    }
}

// بناء شرط الفلترة وجملة GROUP BY
$where_clause_parts = ["YEAR(sale_date) = ?"];
$params = [$selected_year];
$types = "i";

$group_by_clause = "";
$select_period_column = "";
$order_by_clause = "";

if ($selected_day) {
    // تقرير ليوم محدد، لا تجميع
    $where_clause_parts[] = "MONTH(sale_date) = ?";
    $where_clause_parts[] = "DAY(sale_date) = ?";
    $params[] = $selected_month;
    $params[] = $selected_day;
    $types .= "ii";

    $select_period_column = "DATE(sale_date) as period_label";
    $order_by_clause = "ORDER BY sale_date ASC";
} elseif ($selected_month) {
    // تقرير لشهر محدد، تجميع حسب اليوم
    $where_clause_parts[] = "MONTH(sale_date) = ?";
    $params[] = $selected_month;
    $types .= "i";

    $select_period_column = "DAY(sale_date) as period_label";
    $group_by_clause = "GROUP BY DAY(sale_date)";
    $order_by_clause = "ORDER BY DAY(sale_date) ASC";
} else {
    // تقرير لسنة محددة، تجميع حسب الشهر
    $select_period_column = "MONTH(sale_date) as period_label";
    $group_by_clause = "GROUP BY MONTH(sale_date)";
    $order_by_clause = "ORDER BY MONTH(sale_date) ASC";
}

$where_clause = "WHERE " . implode(" AND ", $where_clause_parts);

// بناء الاستعلام الرئيسي لجلب البيانات المجمعة
$report_query_sql = "SELECT
                        $select_period_column,
                        SUM(si.price) as total_sales,
                        SUM(s.paid_amount) as total_paid
                     FROM sales s
                     JOIN sales_items si ON s.id = si.sale_id
                     $where_clause
                     $group_by_clause
                     $order_by_clause";

$report_data = [];
$report_stmt = $conn->prepare($report_query_sql);

if ($report_stmt) {
    if (!empty($params)) {
        $report_stmt->bind_param($types, ...$params);
    }
    $report_stmt->execute();
    $report_result = $report_stmt->get_result();

    if ($report_result) {
        while ($row = $report_result->fetch_assoc()) {
            $report_data[] = $row;
        }
    }
    $report_stmt->close();
} else {
    error_log("خطأ في إعداد استعلام التقرير: " . $conn->error);
    $error_message = "حدث خطأ أثناء إعداد التقرير.";
}

?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقارير المبيعات</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; padding-top: 44px; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0; padding: 32px 18px; }
        h2 { text-align: center; color: #333; margin-bottom: 24px; font-size: 1.18em; margin-top: 0; }
        form { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin-bottom: 24px; }
        select { padding: 7px 8px; border: 1px solid #ccc; border-radius: 6px; min-width: 90px; background: #f8f8f8; color: #333; }
        label { color: #555; font-weight: 500; margin-left: 6px; }
        button { background: #666; color: #fff; border: none; border-radius: 7px; padding: 8px 22px; font-size: 1em; cursor: pointer; transition: background 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        button:hover { background: #444; color: #fff; }

        /* New table styles */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: center; border-bottom: 1px solid #eee; color: #333; }
        th { background-color: #444; color: #fff; font-weight: 600; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f0f0f0; }
        .no-records { text-align: center; color: #777; padding: 30px; font-size: 1.1em; }

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
            form label {
                font-size: 1.18em;
                margin-bottom: 8px;
            }
            select, button {
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
    <script>
        function submitOnChange() {
            document.getElementById('report-form').submit();
        }
    </script>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container container-large">
        <h2>تقارير المبيعات</h2>
        <div style="display: flex; justify-content: center; margin-bottom: 18px;">
            <form method="get" action="export_reports.php" style="margin: 0;">
                <input type="hidden" name="year" value="<?= htmlspecialchars($selected_year) ?>">
                <input type="hidden" name="month" value="<?= htmlspecialchars($selected_month) ?>">
                <input type="hidden" name="day" value="<?= htmlspecialchars($selected_day) ?>">
                <button type="submit" style="background: #218838; color: #fff; padding: 7px 18px; font-size: 1em; border-radius: 7px; border: none; margin-bottom: 0; cursor: pointer;">تصدير إلى CSV</button>
            </form>
        </div>
        <form method="get" id="report-form">
            <label>السنة:</label>
            <select name="year" onchange="submitOnChange()">
                <?php foreach($years as $year_option): ?>
                    <option value="<?= $year_option ?>" <?= ($year_option == $selected_year) ? 'selected' : '' ?>><?= $year_option ?></option>
                <?php endforeach; ?>
            </select>
            <label>الشهر:</label>
            <select name="month" onchange="submitOnChange()">
                <option value="">كل الشهور</option>
                <?php foreach($months_names as $num => $name): ?>
                    <option value="<?= $num ?>" <?= ($num == $selected_month) ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
            <label>اليوم:</label>
            <select name="day" onchange="submitOnChange()">
                <option value="">كل الأيام</option>
                <?php foreach($days as $day_option): ?>
                    <option value="<?= $day_option ?>" <?= ($day_option == $selected_day) ? 'selected' : '' ?>><?= $day_option ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" style="display:none;">تصفية</button> <!-- Hidden submit button -->
        </form>

        <?php if ($error_message): ?>
            <div class="alert error"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if (!empty($report_data)): ?>
        <table>
            <thead>
                <tr>
                    <th>الفترة</th>
                    <th>إجمالي المبيعات</th>
                    <th>إجمالي المدفوع</th>
                    <th>المتبقي</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_data as $data): ?>
                <tr>
                    <td>
                        <?php
                        if ($selected_day) {
                            // If specific day selected, period_label is full date (YYYY-MM-DD)
                            echo htmlspecialchars($data['period_label']);
                        } elseif ($selected_month) {
                            // If specific month selected, period_label is day number
                            echo htmlspecialchars($data['period_label']);
                        } else {
                            // If only year selected, period_label is month number
                            echo htmlspecialchars($months_names[$data['period_label']]);
                        }
                        ?>
                    </td>
                    <td><?= number_format($data['total_sales'] ?? 0) ?></td>
                    <td><?= number_format($data['total_paid'] ?? 0) ?></td>
                    <td><?= number_format(($data['total_sales'] ?? 0) - ($data['total_paid'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="no-records">لا توجد بيانات متاحة للفترة المحددة.</p>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
<?php
$conn->close();
?> 