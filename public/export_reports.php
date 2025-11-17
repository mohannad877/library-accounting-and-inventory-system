<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

// استقبال الفلاتر
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$selected_day = isset($_GET['day']) ? intval($_GET['day']) : 0;

$months_names = [1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر'];

$where_clause_parts = ["YEAR(sale_date) = ?"];
$params = [$selected_year];
$types = "i";
$group_by_clause = "";
$select_period_column = "";
$order_by_clause = "";

if ($selected_day) {
    $where_clause_parts[] = "MONTH(sale_date) = ?";
    $where_clause_parts[] = "DAY(sale_date) = ?";
    $params[] = $selected_month;
    $params[] = $selected_day;
    $types .= "ii";
    $select_period_column = "DATE(sale_date) as period_label";
    $order_by_clause = "ORDER BY sale_date ASC";
} elseif ($selected_month) {
    $where_clause_parts[] = "MONTH(sale_date) = ?";
    $params[] = $selected_month;
    $types .= "i";
    $select_period_column = "DAY(sale_date) as period_label";
    $group_by_clause = "GROUP BY DAY(sale_date)";
    $order_by_clause = "ORDER BY DAY(sale_date) ASC";
} else {
    $select_period_column = "MONTH(sale_date) as period_label";
    $group_by_clause = "GROUP BY MONTH(sale_date)";
    $order_by_clause = "ORDER BY MONTH(sale_date) ASC";
}

$where_clause = "WHERE " . implode(" AND ", $where_clause_parts);

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
}

// تجهيز اسم الملف
$filename = "reports_" . $selected_year;
if ($selected_month) {
    $filename .= "_" . str_pad($selected_month, 2, "0", STR_PAD_LEFT);
}
if ($selected_day) {
    $filename .= "_" . str_pad($selected_day, 2, "0", STR_PAD_LEFT);
}
$filename .= ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

// رؤوس الأعمدة
fputcsv($output, ['الفترة', 'إجمالي المبيعات', 'إجمالي المدفوع', 'المتبقي']);
foreach ($report_data as $data) {
    if ($selected_day) {
        $period = $data['period_label'];
    } elseif ($selected_month) {
        $period = $data['period_label'];
    } else {
        $period = $months_names[$data['period_label']] ?? $data['period_label'];
    }
    $total_sales = $data['total_sales'] ?? 0;
    $total_paid = $data['total_paid'] ?? 0;
    $remaining = $total_sales - $total_paid;
    fputcsv($output, [$period, $total_sales, $total_paid, $remaining]);
}
fclose($output);
$conn->close();
exit; 