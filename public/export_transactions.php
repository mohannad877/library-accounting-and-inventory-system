<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$day_filter = $_GET['day'] ?? '';
$month_filter = $_GET['month'] ?? '';
$year_filter = $_GET['year'] ?? '';

$sql = "SELECT t.*, b.title FROM transactions t JOIN books b ON t.book_id = b.id WHERE 1=1";
$params = [];
$types = "";
if (!empty($year_filter)) {
    $sql .= " AND YEAR(t.transaction_date) = ?";
    $params[] = $year_filter;
    $types .= "i";
}
if (!empty($month_filter)) {
    $sql .= " AND MONTH(t.transaction_date) = ?";
    $params[] = $month_filter;
    $types .= "i";
}
if (!empty($day_filter)) {
    $sql .= " AND DAY(t.transaction_date) = ?";
    $params[] = $day_filter;
    $types .= "i";
}
$sql .= " ORDER BY t.transaction_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$filename = "transactions";
if (!empty($year_filter)) {
    $filename .= "_" . $year_filter;
}
if (!empty($month_filter)) {
    $filename .= "_" . str_pad($month_filter, 2, "0", STR_PAD_LEFT);
}
if (!empty($day_filter)) {
    $filename .= "_" . str_pad($day_filter, 2, "0", STR_PAD_LEFT);
}
$filename .= ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

// رؤوس الأعمدة
fputcsv($output, ['اسم الكتاب', 'نوع العملية', 'الكمية', 'التاريخ']);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['title'],
        $row['type'],
        $row['quantity'],
        $row['transaction_date']
    ]);
}
fclose($output);
$conn->close();
exit; 