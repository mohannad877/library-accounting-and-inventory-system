<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login('admin');
require_post();

$sale_id = intval($_POST['sale_id'] ?? 0);
$csrf_key = $_POST['csrf_key'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';

if ($sale_id <= 0 || !verify_csrf_token($csrf_token, $csrf_key)) {
    $_SESSION['error_message'] = "طلب حذف الفاتورة غير صالح.";
    header("Location: view_sales.php");
    exit();
}

$conn->begin_transaction();

try {
    $items_stmt = $conn->prepare("SELECT book_id, stage, quantity FROM sales_items WHERE sale_id = ?");
    $items_stmt->bind_param("i", $sale_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $sale_items = $items_result->fetch_all(MYSQLI_ASSOC);
    $items_stmt->close();

    if (empty($sale_items)) {
        throw new Exception("لا توجد عناصر مرتبطة بهذه الفاتورة.");
    }

    foreach ($sale_items as $item) {
        $book_id = $item['book_id'];
        $stage = $item['stage'];
        $quantity = $item['quantity'];

        $update_stage_stmt = $conn->prepare("UPDATE book_stages SET quantity = quantity + ? WHERE book_id = ? AND stage = ?");
        $update_stage_stmt->bind_param("iis", $quantity, $book_id, $stage);
        if (!$update_stage_stmt->execute() || $update_stage_stmt->affected_rows === 0) {
            $update_stage_stmt->close();
            $insert_stage_stmt = $conn->prepare("INSERT INTO book_stages (book_id, stage, quantity) VALUES (?, ?, ?)");
            $insert_stage_stmt->bind_param("isi", $book_id, $stage, $quantity);
            if (!$insert_stage_stmt->execute()) {
                throw new Exception("خطأ في إعادة الكمية للمرحلة: " . $insert_stage_stmt->error);
            }
            $insert_stage_stmt->close();
        } else {
            $update_stage_stmt->close();
        }
    }

    $stmt_items = $conn->prepare("DELETE FROM sales_items WHERE sale_id = ?");
    $stmt_items->bind_param("i", $sale_id);
    if (!$stmt_items->execute()) {
        throw new Exception("خطأ في حذف تفاصيل الفاتورة: " . $stmt_items->error);
    }
    $stmt_items->close();

    $stmt_sale = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $stmt_sale->bind_param("i", $sale_id);
    if (!$stmt_sale->execute()) {
        throw new Exception("خطأ في حذف الفاتورة: " . $stmt_sale->error);
    }
    $stmt_sale->close();

    $conn->commit();
    $_SESSION['success_message'] = "تم حذف الفاتورة بنجاح وإعادة المخزون.";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = $e->getMessage();
}

$conn->close();
header("Location: view_sales.php");
exit();