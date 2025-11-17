<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login('admin');
require_post();

$transaction_id = intval($_POST['transaction_id'] ?? 0);
$csrf_key = $_POST['csrf_key'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';

if ($transaction_id <= 0 || !verify_csrf_token($csrf_token, $csrf_key)) {
    $_SESSION['message'] = "طلب حذف غير صالح.";
    $_SESSION['message_type'] = "error";
    header("Location: view_transactions.php");
    exit();
}

$conn->begin_transaction();

try {
    $stmt_fetch_transaction = $conn->prepare("SELECT book_id, type, quantity, stage FROM transactions WHERE id = ?");
    if (!$stmt_fetch_transaction) {
        throw new Exception("خطأ في إعداد استعلام جلب العملية: " . $conn->error);
    }
    $stmt_fetch_transaction->bind_param("i", $transaction_id);
    $stmt_fetch_transaction->execute();
    $result_fetch_transaction = $stmt_fetch_transaction->get_result();

    if ($result_fetch_transaction->num_rows === 0) {
        throw new Exception("العملية غير موجودة.");
    }
    $transaction_data = $result_fetch_transaction->fetch_assoc();
    $stmt_fetch_transaction->close();

    $book_id = $transaction_data['book_id'];
    $type = $transaction_data['type'];
    $quantity = $transaction_data['quantity'];
    $stage = $transaction_data['stage'];

    $stage_stmt = $conn->prepare("SELECT quantity FROM book_stages WHERE book_id = ? AND stage = ? FOR UPDATE");
    $stage_stmt->bind_param("is", $book_id, $stage);
    $stage_stmt->execute();
    $stage_stmt->bind_result($available_qty);
    $rowExists = $stage_stmt->fetch();
    $stage_stmt->close();

    if (!$rowExists) {
        if ($type === 'صرف') {
            $insert_stage_stmt = $conn->prepare("INSERT INTO book_stages (book_id, stage, quantity) VALUES (?, ?, ?)");
            $insert_stage_stmt->bind_param("isi", $book_id, $stage, $quantity);
            $insert_stage_stmt->execute();
            $insert_stage_stmt->close();
        } else {
            throw new Exception("لا يمكن تحديث مخزون المرحلة لعدم وجود بيانات سابقة.");
        }
    } else {
        if ($type === 'وارد' && $quantity > $available_qty) {
            throw new Exception("لا يمكن حذف العملية لأن الكمية المتاحة أقل من المدخلة.");
        }
        $update_stmt = $conn->prepare(
            $type === 'وارد'
                ? "UPDATE book_stages SET quantity = quantity - ? WHERE book_id = ? AND stage = ?"
                : "UPDATE book_stages SET quantity = quantity + ? WHERE book_id = ? AND stage = ?"
        );
        $update_stmt->bind_param("iis", $quantity, $book_id, $stage);
        if (!$update_stmt->execute()) {
            throw new Exception("خطأ في تحديث المخزون: " . $update_stmt->error);
        }
        $update_stmt->close();
    }

    $stmt_delete_transaction = $conn->prepare("DELETE FROM transactions WHERE id = ?");
    if (!$stmt_delete_transaction) {
        throw new Exception("خطأ في إعداد استعلام حذف العملية: " . $conn->error);
    }
    $stmt_delete_transaction->bind_param("i", $transaction_id);
    if (!$stmt_delete_transaction->execute()) {
        throw new Exception("خطأ في حذف العملية: " . $stmt_delete_transaction->error);
    }
    $stmt_delete_transaction->close();

    $conn->commit();
    $_SESSION['message'] = "تم حذف العملية بنجاح وتحديث المخزون.";
    $_SESSION['message_type'] = "success";
} catch (Exception $e) {
    $conn->rollback();
    error_log("خطأ في حذف العملية: " . $e->getMessage());
    $_SESSION['message'] = "حدث خطأ أثناء حذف العملية: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

$conn->close();
header("Location: view_transactions.php");
exit();