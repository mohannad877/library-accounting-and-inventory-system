<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login('admin');

$invoice_number_filter = $_GET['invoice_number'] ?? '';
$day_filter = $_GET['day'] ?? '';
$month_filter = $_GET['month'] ?? '';
$year_filter = $_GET['year'] ?? '';

$sql = "SELECT t.invoice_number, t.quantity, t.purchase_price, t.transaction_date, b.title, t.stage
        FROM transactions t
        JOIN books b ON t.book_id = b.id
        WHERE t.type = 'وارد'";

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

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// تجهيز اسم الملف
$filename = "invoices";
if (!empty($year_filter)) {
    $filename .= "_" . $year_filter;
}
if (!empty($month_filter)) {
    $filename .= "_" . str_pad($month_filter, 2, "0", STR_PAD_LEFT);
}
if (!empty($day_filter)) {
    $filename .= "_" . str_pad($day_filter, 2, "0", STR_PAD_LEFT);
}
if (!empty($invoice_number_filter)) {
    $filename .= "_" . preg_replace('/[^a-zA-Z0-9]/', '', $invoice_number_filter);
}
$filename .= ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

// رؤوس الأعمدة
fputcsv($output, ['رقم الفاتورة', 'عنوان الكتاب', 'المرحلة', 'الكمية', 'سعر الشراء الإفرادي', 'تاريخ العملية']);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['invoice_number'],
        $row['title'],
        $row['stage'],
        $row['quantity'],
        number_format($row['purchase_price'], 2),
        $row['transaction_date']
    ]);
}
fclose($output);
$conn->close();
exit; 