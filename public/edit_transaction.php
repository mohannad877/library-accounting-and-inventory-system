<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login('admin');
include 'header.php';

$transaction = null;
$books = [];
$error_message = '';
$success_message = '';

$stages_list = ['أول أساسي', 'ثاني أساسي', 'ثالث أساسي', 'رابع أساسي', 'خامس أساسي', 'سادس أساسي', 'أول إعدادي', 'ثاني إعدادي', 'ثالث إعدادي'];

// جلب الكتب لملء القائمة المنسدلة (مع الكميات للتحقق من جانب الخادم)
$books_sql = "SELECT id, title, quantity FROM books ORDER BY title ASC";
$books_stmt = $conn->prepare($books_sql);
if ($books_stmt) {
    $books_stmt->execute();
    $books_result = $books_stmt->get_result();
    if ($books_result) {
        while ($row = $books_result->fetch_assoc()) {
            $books[$row['id']] = $row; // Store books by ID for easy lookup
        }
    }
    $books_stmt->close();
} else {
    error_log("خطأ في إعداد استعلام جلب الكتب: " . $conn->error);
    $error_message = "حدث خطأ أثناء جلب قائمة الكتب.";
}

// جلب بيانات العملية الحالية (قبل التعديل)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $transaction_id_get = intval($_GET['id']);
    
    $sql_fetch_old = "SELECT id, book_id, type, quantity, invoice_number, stage FROM transactions WHERE id = ?";
    $stmt_fetch_old = $conn->prepare($sql_fetch_old);
    if ($stmt_fetch_old === false) {
        $error_message = "حدث خطأ في قاعدة البيانات أثناء جلب بيانات العملية الأصلية.";
        error_log("Failed to prepare statement for fetching old transaction: " . $conn->error);
    } else {
        $stmt_fetch_old->bind_param("i", $transaction_id_get);
        $stmt_fetch_old->execute();
        $result_fetch_old = $stmt_fetch_old->get_result();
        if ($result_fetch_old->num_rows > 0) {
            $transaction = $result_fetch_old->fetch_assoc();
            $old_book_id = $transaction['book_id'];
            $old_type = $transaction['type'];
            $old_quantity = $transaction['quantity'];
            $old_stage = $transaction['stage'] ?? $stages_list[0];
        } else {
            $error_message = "العملية المطلوبة غير موجودة.";
        }
        $stmt_fetch_old->close();
    }
} else {
    $error_message = "معرف العملية غير محدد.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $transaction) {
    require_post();
    $csrf_key = 'edit_transaction_' . $transaction['id'];
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', $csrf_key)) {
        $error_message = "طلب غير صالح (CSRF)!";
    } else {
        $transaction_id_post = intval($_POST['transaction_id']);
        $new_book_id = intval($_POST['book_id']);
        $new_type = trim($_POST['type']);
        $new_quantity = intval($_POST['quantity']);
        $new_invoice_number = trim($_POST['invoice_number']);
        $new_stage = $_POST['stage'] ?? '';
        if (!in_array($new_stage, $stages_list)) {
            $error_message = "يجب اختيار المرحلة بشكل صحيح.";
        } elseif ($transaction_id_post !== $transaction['id']) {
            $error_message = "بيانات العملية غير متطابقة. يرجى إعادة المحاولة.";
        } elseif (!isset($books[$new_book_id])) {
            $error_message = "الكتاب المحدد غير صالح.";
        } elseif (!in_array($new_type, ['صرف', 'وارد'])) {
            $error_message = "نوع العملية غير صالح.";
        } elseif ($new_quantity <= 0) {
            $error_message = "الكمية يجب أن تكون أكبر من صفر.";
        } else {
            $conn->begin_transaction();
            try {
                $old_stage = $transaction['stage'] ?? $stages_list[0];
                if ($old_book_id == $new_book_id && $old_stage == $new_stage) {
                    $diff = $new_quantity - $old_quantity;
                    if ($diff !== 0) {
                        $sql_update_stage = ($new_type === 'وارد') ?
                            "UPDATE book_stages SET quantity = quantity + ? WHERE book_id = ? AND stage = ?" :
                            "UPDATE book_stages SET quantity = quantity - ? WHERE book_id = ? AND stage = ?";
                        $stmt_update_stage = $conn->prepare($sql_update_stage);
                        $stmt_update_stage->bind_param("iis", abs($diff), $new_book_id, $new_stage);
                        if (!$stmt_update_stage->execute()) throw new Exception("خطأ في تحديث كمية المرحلة: " . $stmt_update_stage->error);
                        $stmt_update_stage->close();
                    }
                } else {
                    $sql_revert = ($old_type === 'وارد') ?
                        "UPDATE book_stages SET quantity = quantity - ? WHERE book_id = ? AND stage = ?" :
                        "UPDATE book_stages SET quantity = quantity + ? WHERE book_id = ? AND stage = ?";
                    $stmt_revert = $conn->prepare($sql_revert);
                    $stmt_revert->bind_param("iis", $old_quantity, $old_book_id, $old_stage);
                    if (!$stmt_revert->execute()) throw new Exception("خطأ في عكس تأثير العملية القديمة: " . $stmt_revert->error);
                    $stmt_revert->close();

                    $sql_apply = ($new_type === 'وارد') ?
                        "UPDATE book_stages SET quantity = quantity + ? WHERE book_id = ? AND stage = ?" :
                        "UPDATE book_stages SET quantity = quantity - ? WHERE book_id = ? AND stage = ?";
                    $stmt_apply = $conn->prepare($sql_apply);
                    $stmt_apply->bind_param("iis", $new_quantity, $new_book_id, $new_stage);
                    if (!$stmt_apply->execute()) throw new Exception("خطأ في تطبيق تأثير العملية الجديدة: " . $stmt_apply->error);
                    $stmt_apply->close();
                }

                $update_sql = "UPDATE transactions SET book_id = ?, type = ?, quantity = ?, invoice_number = ?, stage = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                if ($update_stmt === false) {
                    throw new Exception("حدث خطأ في قاعدة البيانات أثناء التحضير لتحديث العملية.");
                }
                $update_stmt->bind_param("isissi", $new_book_id, $new_type, $new_quantity, $new_invoice_number, $new_stage, $transaction_id_post);
                if (!$update_stmt->execute()) {
                    throw new Exception("حدث خطأ أثناء تحديث العملية: " . $update_stmt->error);
                }
                $update_stmt->close();
                $conn->commit();
                $success_message = "تم تحديث العملية بنجاح!";
                $transaction['book_id'] = $new_book_id;
                $transaction['type'] = $new_type;
                $transaction['quantity'] = $new_quantity;
                $transaction['invoice_number'] = $new_invoice_number;
                $transaction['stage'] = $new_stage;
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "خطأ: " . $e->getMessage();
            }
        }
    }
}

$form_csrf_key = $transaction ? 'edit_transaction_' . $transaction['id'] : 'edit_transaction_form';
$form_csrf_token = csrf_token($form_csrf_key);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل عملية</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; margin: 0; padding: 0; }
        .container { max-width: 500px; margin: 50px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px #0001; border: 1px solid #e0e0e0; padding: 32px 18px 24px 18px; }
        h2 { text-align: center; color: #222; margin-bottom: 24px; font-size: 1.13em; margin-top: 32px; }
        label { display: block; margin-bottom: 6px; color: #444; font-weight: 600; }
        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: #f8f9fa;
            color: #222;
            transition: border 0.2s;
        }
        input[type="text"]:focus, input[type="number"]:focus, select:focus {
            border-color: #444;
            outline: none;
        }
        button[type="submit"] {
            width: 100%;
            background: #444;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px 0;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        button[type="submit"]:hover {
            background: #222;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .message.success { background-color: #d4edda; color: #155724; border-color: #b7dfc5; }
        .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <h2>تعديل عملية</h2>

        <?php if ($error_message): ?>
            <div class="message error"><?= $error_message ?></div>
        <?php elseif ($success_message): ?>
            <div class="message success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if ($transaction): ?>
        <form method="post" action="">
            <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($transaction['id']) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($form_csrf_token) ?>">
            
            <label for="book_id">الكتاب:</label>
            <select name="book_id" id="book_id" required>
                <?php foreach ($books as $book_option): ?>
                    <option value="<?= $book_option['id'] ?>" <?= ($book_option['id'] == $transaction['book_id']) ? 'selected' : '' ?>><?= htmlspecialchars($book_option['title']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="type">نوع العملية:</label>
            <select name="type" id="type" required>
                <option value="صرف" <?= ($transaction['type'] == 'صرف') ? 'selected' : '' ?>>صرف</option>
                <option value="وارد" <?= ($transaction['type'] == 'وارد') ? 'selected' : '' ?>>وارد</option>
            </select>

            <label for="quantity">الكمية:</label>
            <input type="number" name="quantity" id="quantity" value="<?= htmlspecialchars($transaction['quantity']) ?>" min="1" required>

            <label for="invoice_number">رقم الفاتورة:</label>
            <input type="text" name="invoice_number" id="invoice_number" value="<?= htmlspecialchars($transaction['invoice_number'] ?? '') ?>">

            <label for="stage">المرحلة:</label>
            <select name="stage" id="stage" required>
                <option value="">اختر المرحلة</option>
                <?php foreach ($stages_list as $stage): ?>
                    <option value="<?= $stage ?>" <?= (isset($transaction['stage']) && $transaction['stage'] == $stage) ? 'selected' : '' ?>><?= $stage ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">حفظ التعديلات</button>
        </form>
        <?php else: ?>
            <p class="message error">العملية المطلوبة غير متوفرة للتعديل أو حدث خطأ في جلبها.</p>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?> 