<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
include 'header.php';

$error_message = '';
$success_message = '';

// معالجة حفظ الفاتورة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'group_sale')) {
        $error_message = 'طلب غير صالح (CSRF)!';
    } else {
        $school_name = sanitize_string($_POST['school_name'] ?? '');
        $book_ids = $_POST['book_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $prices = $_POST['price'] ?? [];
        $stages_selected = $_POST['stage'] ?? [];
        $is_sample = isset($_POST['is_sample']) && $_POST['is_sample'] == '1';
        $type = $is_sample ? "عينة مجانية" : "صرف";
        $paid_amount = $is_sample ? 0 : filter_var($_POST['paid_amount'], FILTER_VALIDATE_FLOAT); // جلب المبلغ المدفوع
        // إذا كانت عينة مجانية، جميع الأسعار = 0
        if ($is_sample) {
            foreach ($prices as &$p) { $p = 0; }
            unset($p);
        }

        // تحقق من المدخلات الأساسية
        if (empty($school_name)) {
            $error_message = 'يرجى إدخال اسم المدرسة.';
        } elseif (empty($book_ids)) {
            $error_message = 'يجب إضافة كتاب واحد على الأقل للفاتورة.';
        } elseif ($paid_amount === false || $paid_amount < 0) {
            $error_message = 'المبلغ المدفوع غير صالح.';
        } else {
            $conn->begin_transaction();
            try {
                // إضافة الفاتورة الرئيسية
                $stmt = $conn->prepare("INSERT INTO sales (school_name, type, paid_amount) VALUES (?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("خطأ في إعداد استعلام الفاتورة الرئيسية: " . $conn->error);
                }
                $stmt->bind_param("ssd", $school_name, $type, $paid_amount);
                if (!$stmt->execute()) {
                    throw new Exception("خطأ في حفظ الفاتورة الرئيسية: " . $stmt->error);
                }
                $sale_id = $stmt->insert_id;
                $stmt->close();

                // جلب أسماء الكتب للرسائل
                $books_titles = [];
                $title_stmt = $conn->prepare("SELECT id, title, quantity FROM books WHERE id IN (" . implode(', ', array_fill(0, count(array_unique($book_ids)), '?')) . ")");
                if ($title_stmt) {
                    $id_types = str_repeat('i', count(array_unique($book_ids)));
                    $title_stmt->bind_param($id_types, ...array_values(array_unique($book_ids)));
                    $title_stmt->execute();
                    $title_result = $title_stmt->get_result();
                    while ($row = $title_result->fetch_assoc()) {
                        $books_titles[$row['id']] = ['title' => $row['title'], 'quantity' => $row['quantity']];
                    }
                    $title_stmt->close();
                }

                // إضافة تفاصيل الفاتورة وتحديث الكمية
                for ($i = 0; $i < count($book_ids); $i++) {
                    $book_id = intval($book_ids[$i]);
                    $quantity = intval($quantities[$i]);
                    $price = floatval($prices[$i]);
                    $stage = $stages_selected[$i] ?? '';
                    if (!in_array($stage, ['أول أساسي', 'ثاني أساسي', 'ثالث أساسي', 'رابع أساسي', 'خامس أساسي', 'سادس أساسي', 'أول إعدادي', 'ثاني إعدادي', 'ثالث إعدادي'])) {
                        throw new Exception('يجب اختيار المرحلة بشكل صحيح لكل كتاب.');
                    }
                    // تحقق من المدخلات لكل صنف
                    if ($book_id <= 0 || $quantity <= 0 || $price < 0 || ($is_sample && $price != 0)) {
                        throw new Exception('بيانات غير صالحة للصنف رقم ' . ($i + 1) . '.');
                    }
                    // التحقق من الكمية المتاحة في المرحلة
                    $q_stage = $conn->prepare("SELECT quantity FROM book_stages WHERE book_id=? AND stage=?");
                    $q_stage->bind_param("is", $book_id, $stage);
                    $q_stage->execute();
                    $q_stage->bind_result($available_stage_qty);
                    if ($q_stage->fetch() === null) {
                        $available_stage_qty = 0;
                    }
                    $q_stage->close();
                    if ($quantity > $available_stage_qty) {
                        throw new Exception('الكمية المطلوبة للكتاب في المرحلة ' . $stage . ' تتجاوز الكمية المتوفرة: ' . $available_stage_qty);
                    }
                    // إضافة تفاصيل الفاتورة مع المرحلة
                    $stmt_item = $conn->prepare("INSERT INTO sales_items (sale_id, book_id, stage, quantity, price) VALUES (?, ?, ?, ?, ?)");
                    if (!$stmt_item) {
                        throw new Exception("خطأ في إعداد استعلام إضافة تفاصيل الفاتورة: " . $conn->error);
                    }
                    $stmt_item->bind_param("iisid", $sale_id, $book_id, $stage, $quantity, $price);
                    if (!$stmt_item->execute()) {
                        throw new Exception("خطأ في إضافة تفاصيل الفاتورة: " . $stmt_item->error);
                    }
                    $stmt_item->close();
                    // خصم الكمية من جدول book_stages للمرحلة فقط
                    $update_stmt = $conn->prepare("UPDATE book_stages SET quantity = quantity - ? WHERE book_id = ? AND stage = ?");
                    if (!$update_stmt) {
                        throw new Exception("خطأ في إعداد استعلام تحديث كمية الكتاب للمرحلة: " . $conn->error);
                    }
                    $update_stmt->bind_param("iis", $quantity, $book_id, $stage);
                    if (!$update_stmt->execute()) {
                        throw new Exception("خطأ في تحديث كمية الكتاب للمرحلة: " . $update_stmt->error);
                    }
                    $update_stmt->close();
                }

                $conn->commit(); // تأكيد المعاملة
                $success_message = 'تم حفظ الفاتورة بنجاح!';

            } catch (Exception $e) {
                $conn->rollback(); // التراجع عن المعاملة عند وجود خطأ
                $error_message = $e->getMessage();
                error_log("خطأ في عملية البيع الجماعي: " . $e->getMessage());
            }
        }
        
        // إعادة التوجيه لمنع إعادة إرسال النموذج عند التحديث
        $_SESSION['success_message'] = $success_message; // نقل الرسائل عبر الجلسة
        $_SESSION['error_message'] = $error_message;
        header("Location: group_sale.php");
        exit();
    }
}

// جلب قائمة الكتب مع الأسعار والكميات المتاحة
$books_arr = [];
$books_stmt = $conn->prepare("SELECT b.id, b.title, b.price, SUM(bs.quantity) as total_stage_qty FROM books b JOIN book_stages bs ON b.id = bs.book_id WHERE bs.quantity > 0 GROUP BY b.id, b.title, b.price ORDER BY b.title");
if ($books_stmt) {
    $books_stmt->execute();
    $books_result = $books_stmt->get_result();
    while($row = $books_result->fetch_assoc()) {
        $books_arr[] = $row;
    }
    $books_stmt->close();
} else {
    error_log("خطأ في إعداد استعلام جلب الكتب: " . $conn->error);
}

// جلب المراحل المتوفرة لكل كتاب
$book_stages_map = [];
$q = $conn->query("SELECT book_id, stage, quantity FROM book_stages WHERE quantity > 0");
while ($r = $q->fetch_assoc()) {
    $book_stages_map[$r['book_id']][] = $r['stage'];
}

// عرض رسائل النجاح أو الخطأ
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$csrf_token = csrf_token('group_sale');
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>بيع جماعي لمدرسة</title>
    <style>
        body { background: #f4f4f6; font-family: Tahoma, Arial, 'Segoe UI', sans-serif; padding-top: 44px; }
        .container { max-width: 700px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0; padding: 32px 18px; }
        h2 { text-align: center; color: #333; margin-bottom: 24px; font-size: 1.18em; margin-top: 0; }
        label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
        input, select { padding: 7px 8px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 14px; width: 100%; background: #f8f8f8; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; background: #fff; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #eee; text-align: center; color: #333; }
        th { background: #ececec; color: #444; font-weight: bold; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f0f0f0; }
        .add-row { background: #666; color: #fff; border: none; padding: 7px 18px; border-radius: 6px; cursor: pointer; margin-top: 10px; font-size: 0.97em; transition: background 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .add-row:hover { background: #444; }
        .remove-row { background: #dc3545; color: #fff; border: none; padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 0.97em; transition: background 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
        .remove-row:hover { background: #c82333; }
        .submit-btn { background: #666; color: #fff; border: none; padding: 10px 30px; border-radius: 8px; font-size: 1em; cursor: pointer; margin-top: 18px; display: block; width: 100%; transition: background 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .submit-btn:hover { background: #444; }
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
            label {
                font-size: 1.18em;
                margin-bottom: 8px;
            }
            input, select {
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
            .add-row, .remove-row, .submit-btn {
                font-size: 1.18em;
                padding: 17px 0;
                border-radius: 9px;
            }
        }
        .success-msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 18px; text-align: center; border: 1px solid #b7dfc5; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 18px; text-align: center; border: 1px solid #f5c6cb; }
    </style>
    <script>
        var booksData = <?php echo json_encode($books_arr, JSON_UNESCAPED_UNICODE); ?>;
        var bookStagesMap = <?php echo json_encode($book_stages_map, JSON_UNESCAPED_UNICODE); ?>;
        function addRow() {
            var table = document.getElementById('books-table').getElementsByTagName('tbody')[0];
            var newRow = table.rows[0].cloneNode(true);
            newRow.querySelectorAll('input, select').forEach(function(el) { el.value = ''; });
            table.appendChild(newRow);
        }
        function removeRow(btn) {
            var row = btn.parentNode.parentNode;
            var table = row.parentNode;
            if (table.rows.length > 1) {
                table.removeChild(row);
            }
            calculateTotalBillAmount(); // Recalculate total after removing a row
            calculateRemaining(); // Recalculate remaining after total changes
        }
        function calculateTotalBillAmount() {
            let total = 0;
            document.querySelectorAll('input[name="price[]"]').forEach(function(input) {
                total += parseFloat(input.value) || 0;
            });
            document.getElementById('total_bill_amount').value = total.toFixed(2);
            calculateRemaining(); // Update remaining after total is calculated
        }
        function calculateRemaining() {
            const totalBill = parseFloat(document.getElementById('total_bill_amount').value) || 0;
            const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
            const remaining = totalBill - paidAmount;
            document.getElementById('remaining_balance').value = remaining.toFixed(2);
        }
        function updatePrice(row) {
            var select = row.querySelector('select[name="book_id[]"]');
            var quantityInput = row.querySelector('input[name="quantity[]"]');
            var priceInput = row.querySelector('input[name="price[]"]');
            var bookId = select.value;
            var quantity = parseInt(quantityInput.value) || 0;
            var found = booksData.find(function(book) { return book.id == bookId; });
            if (found && quantity > 0) {
                // إضافة التحقق من الكمية المتوفرة في جانب العميل
                if (quantity > found.total_stage_qty) {
                    alert('الكمية المطلوبة تتجاوز الكمية المتوفرة: ' + found.total_stage_qty);
                    quantityInput.value = found.total_stage_qty; // تعيين الكمية للحد الأقصى المتاح
                    quantity = found.total_stage_qty;
                }
                priceInput.value = found.price * quantity;
            } else {
                priceInput.value = '';
            }
            calculateTotalBillAmount(); // Update total after individual item price changes
        }
        function setPrice(select) {
            var row = select.parentNode.parentNode;
            updatePrice(row);
        }
        function setPriceByQuantity(input) {
            var row = input.parentNode.parentNode;
            updatePrice(row);
        }
        function updateStages(select) {
            var row = select.parentNode.parentNode;
            var bookId = select.value;
            var stageSelect = row.querySelector('select[name="stage[]"]');
            stageSelect.innerHTML = '<option value="">اختر المرحلة</option>';
            if (bookStagesMap[bookId]) {
                bookStagesMap[bookId].forEach(function(stage) {
                    stageSelect.innerHTML += '<option value="'+stage+'">'+stage+'</option>';
                });
            }
        }
        function toggleSampleMode() {
            var isSample = document.getElementById('is_sample').checked;
            // أسعار الكتب
            document.querySelectorAll('input[name="price[]"]').forEach(function(input) {
                input.value = isSample ? 0 : '';
                input.readOnly = isSample;
            });
            // إجمالي الفاتورة
            document.getElementById('total_bill_amount').value = isSample ? 0 : '';
            document.getElementById('total_bill_amount').readOnly = true;
            // المبلغ المدفوع
            document.getElementById('paid_amount').value = isSample ? 0 : '';
            document.getElementById('paid_amount').readOnly = isSample;
            // المتبقي
            document.getElementById('remaining_balance').value = 0;
            document.getElementById('remaining_balance').readOnly = true;
        }

        // Initial calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotalBillAmount();
            calculateRemaining();
        });
    </script>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container container-medium">
        <h2>بيع جماعي لمدرسة</h2>
        <?php if (!empty($success_message)): ?>
            <div class="success-msg"><?= $success_message ?></div>
        <?php elseif (!empty($error_message)): ?>
            <div class="error-msg"><?= $error_message ?></div>
        <?php endif; ?>
        <form method="post" action="" id="sale-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <label>اسم المدرسة:</label>
            <input type="text" name="school_name" value="<?= htmlspecialchars($school_name ?? '') ?>" required>

            <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 7px;">
                <input type="checkbox" id="is_sample" name="is_sample" value="1" onclick="toggleSampleMode()" style="margin:0; width:18px; height:18px;">
                <label for="is_sample" style="display:inline; font-weight:bold; color:#007bff; margin:0; cursor:pointer;">عينة مجانية (كل الأسعار = 0)</label>
            </div>

            <table id="books-table">
                <thead>
                    <tr>
                        <th>الكتاب</th>
                        <th>المرحلة</th>
                        <th>الكمية (متوفر: <span class="available-quantity"></span>)</th>
                        <th>السعر</th>
                        <th>حذف</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="book_id[]" required onchange="setPrice(this);updateStages(this)">
                                <option value="">اختر كتاب</option>
                                <?php foreach($books_arr as $book): ?>
                                    <option value="<?= $book['id'] ?>" data-price="<?= $book['price'] ?>" data-quantity="<?= $book['total_stage_qty'] ?>"><?= htmlspecialchars($book['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="stage[]" required>
                                <option value="">اختر المرحلة</option>
                            </select>
                        </td>
                        <td><input type="number" name="quantity[]" min="1" required onchange="setPriceByQuantity(this)" onkeyup="setPriceByQuantity(this)"></td>
                        <td><input type="number" name="price[]" min="0" required readonly class="price-input"></td>
                        <td><button type="button" class="remove-row" onclick="removeRow(this)">حذف</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="add-row" onclick="addRow()">إضافة كتاب آخر</button>

            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #ddd;">
                <label for="total_bill_amount">إجمالي الفاتورة (المبلغ المفروض):</label>
                <input type="number" id="total_bill_amount" name="total_bill_amount" step="0.01" readonly value="0">

                <label for="paid_amount">المبلغ المدفوع من المدرسة:</label>
                <input type="number" id="paid_amount" name="paid_amount" step="0.01" value="0" oninput="calculateRemaining()">

                <label for="remaining_balance">المبلغ المتبقي على المدرسة:</label>
                <input type="number" id="remaining_balance" name="remaining_balance" step="0.01" readonly value="0">
            </div>

            <button type="submit" class="submit-btn">حفظ الفاتورة</button>
        </form>
    </div>
<?php include 'footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?> 