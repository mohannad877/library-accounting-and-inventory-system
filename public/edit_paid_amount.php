<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
include "header.php";

$sale_id = $_GET['sale_id'] ?? null;
$sale = null;
$error_message = '';
$success_message = '';

if ($sale_id) {
    // Fetch sale details
    $stmt = $conn->prepare("SELECT school_name, sale_date, paid_amount, (SELECT SUM(price) FROM sales_items WHERE sale_id = sales.id) as total_bill_amount FROM sales WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $sale_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $sale = $result->fetch_assoc();
        } else {
            $error_message = "لم يتم العثور على الفاتورة.";
        }
        $stmt->close();
    } else {
        $error_message = "خطأ في إعداد الاستعلام: " . $conn->error;
    }
} else {
    $error_message = "معرف الفاتورة غير محدد.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_paid_amount'])) {
    require_post();
    $csrf_key = 'edit_paid_amount_' . $sale_id;
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', $csrf_key)) {
        $error_message = "طلب غير صالح (CSRF)!";
    } else {
        $new_paid_amount = filter_var($_POST['new_paid_amount'], FILTER_VALIDATE_FLOAT);

        if ($new_paid_amount === false || $new_paid_amount < 0) {
            $error_message = "الرجاء إدخال مبلغ دفع صحيح.";
        } else {
            $stmt_update = $conn->prepare("UPDATE sales SET paid_amount = ? WHERE id = ?");
            if ($stmt_update) {
                $stmt_update->bind_param("di", $new_paid_amount, $sale_id);
                if ($stmt_update->execute()) {
                    $success_message = "تم تحديث المبلغ المدفوع بنجاح.";
                    $sale['paid_amount'] = $new_paid_amount;
                } else {
                    $error_message = "خطأ في تحديث المبلغ المدفوع: " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $error_message = "خطأ في إعداد استعلام التحديث: " . $conn->error;
            }
        }
    }
}

$form_csrf_token = csrf_token('edit_paid_amount_' . $sale_id);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل المبلغ المدفوع</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; padding-top: 80px; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px #0001; border: 1px solid #e0e0e0; padding: 32px 18px; }
        h2 { text-align: center; color: #222; margin-bottom: 24px; font-size: 1.18em; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #444; font-weight: 500; }
        input[type="number"], input[type="text"] { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; background: #f8f9fa; color: #222; }
        button { background-color: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; width: 100%; }
        button:hover { background-color: #45a049; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .info-box { background: #f0f0f0; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
        .info-box p { margin: 5px 0; color: #333; }
        .info-box p strong { color: #000; }
    </style>
</head>
<body>
    <div class="container">
        <h2>تعديل المبلغ المدفوع للفاتورة</h2>

        <?php if ($error_message): ?>
            <div class="message error"><?= $error_message ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="message success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if ($sale): ?>
            <div class="info-box">
                <p><strong>اسم المدرسة:</strong> <?= htmlspecialchars($sale['school_name']) ?></p>
                <p><strong>تاريخ البيع:</strong> <?= $sale['sale_date'] ?></p>
                <p><strong>إجمالي مبلغ الفاتورة:</strong> <?= number_format($sale['total_bill_amount'], 2) ?></p>
                <p><strong>المبلغ المدفوع حالياً:</strong> <?= number_format($sale['paid_amount'], 2) ?></p>
                <p><strong>المبلغ المتبقي:</strong> <?= number_format($sale['total_bill_amount'] - $sale['paid_amount'], 2) ?></p>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($form_csrf_token) ?>">
                <div class="form-group">
                    <label for="new_paid_amount">أدخل المبلغ المدفوع الجديد:</label>
                    <input type="number" id="new_paid_amount" name="new_paid_amount" step="0.01" value="<?= htmlspecialchars($sale['paid_amount']) ?>" required>
                </div>
                <button type="submit">تحديث المبلغ المدفوع</button>
            </form>
        <?php else: ?>
            <div class="message error">الرجاء تحديد فاتورة لتعديلها.</div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?> 