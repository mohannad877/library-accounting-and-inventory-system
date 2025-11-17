<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$books = [];
$sql = "SELECT * FROM books ORDER BY category, series, title";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    $stmt->close();
}

// جلب الكميات لكل كتاب دفعة واحدة
$book_ids = array_column($books, 'id');
$stage_quantities = [];
if (!empty($book_ids)) {
    $ids_str = implode(',', array_map('intval', $book_ids));
    $q = $conn->query("SELECT book_id, stage, quantity FROM book_stages WHERE book_id IN ($ids_str)");
    while ($r = $q->fetch_assoc()) {
        $stage_quantities[$r['book_id']][$r['stage']] = $r['quantity'];
    }
}
$stages = ['أول أساسي', 'ثاني أساسي', 'ثالث أساسي', 'رابع أساسي', 'خامس أساسي', 'سادس أساسي', 'أول إعدادي', 'ثاني إعدادي', 'ثالث إعدادي'];

$filename = "books_stock_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

// رؤوس الأعمدة
$header = ['اسم الكتاب', 'الفئة', 'السلسلة', 'الترم/السنة', 'السعر'];
foreach ($stages as $stage) {
    $header[] = 'كمية ' . $stage;
}
fputcsv($output, $header);

foreach ($books as $row) {
    $line = [
        $row['title'],
        $row['category'],
        $row['series'],
        $row['term'],
        $row['price']
    ];
    foreach ($stages as $stage) {
        $line[] = isset($stage_quantities[$row['id']][$stage]) ? $stage_quantities[$row['id']][$stage] : 0;
    }
    fputcsv($output, $line);
}
fclose($output);
$conn->close();
exit; 