<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login('admin');
include "header.php";

$sale_id = $_GET['sale_id'] ?? null;
$sale = null;
$sale_items = [];
$error_message = '';
$success_message = '';

$stages_list = ['أول أساسي', 'ثاني أساسي', 'ثالث أساسي', 'رابع أساسي', 'خامس أساسي', 'سادس أساسي', 'أول إعدادي', 'ثاني إعدادي', 'ثالث إعدادي'];

if ($sale_id) {
    // Fetch sale details
    $stmt_sale = $conn->prepare("SELECT id, school_name, sale_date, paid_amount FROM sales WHERE id = ?");
    if ($stmt_sale) {
        $stmt_sale->bind_param("i", $sale_id);
        $stmt_sale->execute();
        $result_sale = $stmt_sale->get_result();
        if ($result_sale && $result_sale->num_rows > 0) {
            $sale = $result_sale->fetch_assoc();
        } else {
            $error_message = "لم يتم العثور على الفاتورة.";
        }
        $stmt_sale->close();
    } else {
        $error_message = "خطأ في إعداد استعلام جلب الفاتورة: " . $conn->error;
    }

    // Fetch sale items
    $stmt_items = $conn->prepare("SELECT si.id as item_id, si.book_id, si.stage, si.quantity, si.price, b.title FROM sales_items si JOIN books b ON si.book_id = b.id WHERE si.sale_id = ?");
    if ($stmt_items) {
        $stmt_items->bind_param("i", $sale_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        if ($result_items) {
            while ($row = $result_items->fetch_assoc()) {
                $sale_items[] = $row;
            }
        }
        $stmt_items->close();
    } else {
        $error_message = "خطأ في إعداد استعلام جلب تفاصيل الفاتورة: " . $conn->error;
    }
} else {
    $error_message = "معرف الفاتورة غير محدد.";
}

$csrf_form_key = $sale_id ? 'edit_sale_' . $sale_id : 'edit_sale_form';
$form_csrf_token = csrf_token($csrf_form_key);
// Fetch all books for dropdowns
$books_query = $conn->query("SELECT id, title, price FROM books ORDER BY title");
$books_options = [];
if ($books_query) {
    while($row = $books_query->fetch_assoc()) {
        $books_options[] = $row;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sale_id) {
    require_post();
    $csrf_key = 'edit_sale_' . $sale_id;
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', $csrf_key)) {
        $error_message = "طلب غير صالح (CSRF)!";
    } else {
        $new_school_name = sanitize_string($_POST['school_name'] ?? '');
        $new_sale_date = $_POST['sale_date'] ?? '';
        $new_paid_amount = filter_var($_POST['paid_amount'], FILTER_VALIDATE_FLOAT);
        $book_ids = $_POST['book_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $prices = $_POST['price'] ?? [];
        $stages_selected = $_POST['stage'] ?? [];

        if (empty($new_school_name) || empty($new_sale_date) || empty($book_ids)) {
            $error_message = "الرجاء ملء جميع الحقول المطلوبة وإضافة كتاب واحد على الأقل.";
        } elseif ($new_paid_amount === false || $new_paid_amount < 0) {
            $error_message = "المبلغ المدفوع غير صالح.";
        } else {
            $conn->begin_transaction();
            try {
                // إعادة الكميات السابقة إلى المخزون
                if (!empty($sale_items)) {
                    $restore_stmt = $conn->prepare("UPDATE book_stages SET quantity = quantity + ? WHERE book_id = ? AND stage = ?");
                    foreach ($sale_items as $old_item) {
                        $restore_stmt->bind_param("iis", $old_item['quantity'], $old_item['book_id'], $old_item['stage']);
                        if (!$restore_stmt->execute()) {
                            throw new Exception("خطأ في استرجاع مخزون الكتاب: " . $restore_stmt->error);
                        }
                    }
                    $restore_stmt->close();
                }

                // حذف العناصر القديمة
                $stmt_delete_items = $conn->prepare("DELETE FROM sales_items WHERE sale_id = ?");
                $stmt_delete_items->bind_param("i", $sale_id);
                if (!$stmt_delete_items->execute()) {
                    throw new Exception("خطأ في حذف تفاصيل الفاتورة القديمة: " . $stmt_delete_items->error);
                }
                $stmt_delete_items->close();

                // إدراج العناصر الجديدة مع التحقق من الكميات
                $insert_stmt = $conn->prepare("INSERT INTO sales_items (sale_id, book_id, stage, quantity, price) VALUES (?, ?, ?, ?, ?)");
                $deduct_stmt = $conn->prepare("UPDATE book_stages SET quantity = quantity - ? WHERE book_id = ? AND stage = ?");

                for ($i = 0; $i < count($book_ids); $i++) {
                    $book_id = intval($book_ids[$i]);
                    $quantity = intval($quantities[$i] ?? 0);
                    $price = floatval($prices[$i] ?? 0);
                    $stage = $stages_selected[$i] ?? '';

                    if (!in_array($stage, $stages_list)) {
                        throw new Exception("يجب اختيار المرحلة بشكل صحيح لكل كتاب.");
                    }
                    if ($book_id <= 0 || $quantity <= 0 || $price < 0) {
                        throw new Exception("بيانات غير صالحة للعنصر رقم " . ($i + 1));
                    }

                    $available_stmt = $conn->prepare("SELECT quantity FROM book_stages WHERE book_id = ? AND stage = ?");
                    $available_stmt->bind_param("is", $book_id, $stage);
                    $available_stmt->execute();
                    $available_stmt->bind_result($available_qty);
                    if (!$available_stmt->fetch()) {
                        $available_stmt->close();
                        throw new Exception("لا توجد كمية متاحة للكتاب المختار في المرحلة {$stage}.");
                    }
                    $available_stmt->close();

                    if ($quantity > $available_qty) {
                        throw new Exception("الكمية المطلوبة للمرحلة {$stage} تتجاوز المتاح ({$available_qty}).");
                    }

                    $insert_stmt->bind_param("iisid", $sale_id, $book_id, $stage, $quantity, $price);
                    if (!$insert_stmt->execute()) {
                        throw new Exception("خطأ في إضافة تفاصيل الفاتورة: " . $insert_stmt->error);
                    }

                    $deduct_stmt->bind_param("iis", $quantity, $book_id, $stage);
                    if (!$deduct_stmt->execute()) {
                        throw new Exception("خطأ في تحديث مخزون المرحلة: " . $deduct_stmt->error);
                    }
                }

                $insert_stmt->close();
                $deduct_stmt->close();

                // تحديث بيانات الفاتورة الرئيسية
                $stmt_update_sale = $conn->prepare("UPDATE sales SET school_name = ?, sale_date = ?, paid_amount = ? WHERE id = ?");
                if (!$stmt_update_sale) {
                    throw new Exception("خطأ في إعداد استعلام تحديث الفاتورة: " . $conn->error);
                }
                $stmt_update_sale->bind_param("ssdi", $new_school_name, $new_sale_date, $new_paid_amount, $sale_id);
                if (!$stmt_update_sale->execute()) {
                    throw new Exception("خطأ في تحديث الفاتورة: " . $stmt_update_sale->error);
                }
                $stmt_update_sale->close();

                $conn->commit();
                header("Location: view_sales.php");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "خطأ: " . $e->getMessage();
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل الفاتورة</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; padding-top: 80px; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0; padding: 32px 18px; }
        h2 { text-align: center; color: #333; margin-bottom: 24px; font-size: 1.18em; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: 500; }
        input[type="text"], input[type="date"], input[type="number"], select { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; background: #f8f8f8; color: #333; }
        .item-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-end; }
        .item-row select, .item-row input { flex: 1; }
        .item-row button { flex-shrink: 0; padding: 8px 12px; background-color: #ccc; color: #333; border: 1px solid #bbb; border-radius: 4px; cursor: pointer; transition: background-color 0.3s ease; }
        .item-row button:hover { background-color: #aaa; }
        #add-item-btn { background-color: #e0e0e0; color: #333; padding: 10px 20px; border: 1px solid #ccc; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 15px; transition: background-color 0.3s ease; }
        #add-item-btn:hover { background-color: #d0d0d0; }
        button[type="submit"] { background-color: #666; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; width: 100%; margin-top: 20px; transition: background-color 0.3s ease; }
        button[type="submit"]:hover { background-color: #444; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>تعديل الفاتورة</h2>

        <?php if ($error_message): ?>
            <div class="message error"><?= $error_message ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="message success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if ($sale): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($form_csrf_token) ?>">
                <div class="form-group">
                    <label for="school_name">اسم المدرسة:</label>
                    <input type="text" id="school_name" name="school_name" value="<?= htmlspecialchars($sale['school_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="sale_date">تاريخ البيع:</label>
                    <input type="date" id="sale_date" name="sale_date" value="<?= htmlspecialchars($sale['sale_date']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="paid_amount">المبلغ المدفوع:</label>
                    <input type="number" id="paid_amount" name="paid_amount" step="0.01" value="<?= htmlspecialchars($sale['paid_amount']) ?>" required>
                </div>

                <h3>الكتب</h3>
                <div id="items-container">
                    <?php foreach ($sale_items as $index => $item): ?>
                        <div class="item-row">
                            <input type="hidden" name="item_id[]" value="<?= $item['item_id'] ?>">
                            <select name="book_id[]" class="book-select" data-index="<?= $index ?>" required>
                                <option value="">اختر كتاب</option>
                                <?php foreach ($books_options as $book): ?>
                                    <option value="<?= $book['id'] ?>" data-price="<?= $book['price'] ?>" <?= ($book['id'] == $item['book_id']) ? 'selected' : '' ?>><?= htmlspecialchars($book['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="stage[]" required>
                                <option value="">اختر المرحلة</option>
                                <?php foreach ($stages_list as $stage): ?>
                                    <option value="<?= $stage ?>" <?= ($item['stage'] === $stage) ? 'selected' : '' ?>><?= $stage ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="quantity[]" class="item-quantity" placeholder="الكمية" value="<?= $item['quantity'] ?>" min="1" required>
                            <input type="number" name="price[]" class="item-price" placeholder="السعر" value="<?= $item['price'] ?>" step="0.01" readonly required>
                            <button type="button" class="remove-item-btn">حذف</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-item-btn">إضافة كتاب</button>

                <button type="submit">تحديث الفاتورة</button>
            </form>
        <?php else: ?>
            <div class="message error">الرجاء تحديد فاتورة لتعديلها.</div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const itemsContainer = document.getElementById('items-container');
            const addItemBtn = document.getElementById('add-item-btn');
            let itemIndex = <?= count($sale_items) ?>;

            function updatePrice(selectElement) {
                const row = selectElement.closest('.item-row');
                const quantityInput = row.querySelector('.item-quantity');
                const priceInput = row.querySelector('.item-price');
                const selectedBookOption = selectElement.options[selectElement.selectedIndex];
                const bookPrice = parseFloat(selectedBookOption.dataset.price);

                const quantity = parseFloat(quantityInput.value);

                if (!isNaN(bookPrice) && !isNaN(quantity) && quantity > 0) {
                    priceInput.value = (bookPrice * quantity).toFixed(2);
                } else {
                    priceInput.value = '0.00';
                }
                updateOverallTotal();
            }

            function updateOverallTotal() {
                let total = 0;
                document.querySelectorAll('.item-price').forEach(input => {
                    const price = parseFloat(input.value);
                    if (!isNaN(price)) {
                        total += price;
                    }
                });
                // You can display this total somewhere, e.g., in a footer or summary section
                // For now, it just calculates it.
                // console.log('Total: ', total.toFixed(2));
            }

            addItemBtn.addEventListener('click', function() {
                const newRow = document.createElement('div');
                newRow.classList.add('item-row');
                newRow.innerHTML = `
                    <input type="hidden" name="item_id[]" value="">
                    <select name="book_id[]" class="book-select" data-index="${itemIndex}" required>
                        <option value="">اختر كتاب</option>
                        <?php foreach ($books_options as $book): ?>
                            <option value="<?= $book['id'] ?>" data-price="<?= $book['price'] ?>"><?= htmlspecialchars($book['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="stage[]" required>
                        <option value="">اختر المرحلة</option>
                        <?php foreach ($stages_list as $stage): ?>
                            <option value="<?= $stage ?>"><?= $stage ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="quantity[]" class="item-quantity" placeholder="الكمية" value="1" min="1" required>
                    <input type="number" name="price[]" class="item-price" placeholder="السعر" value="0.00" step="0.01" readonly required>
                    <button type="button" class="remove-item-btn">حذف</button>
                `;
                itemsContainer.appendChild(newRow);
                itemIndex++;
                // Add event listeners for the new row
                newRow.querySelector('.book-select').addEventListener('change', function() { updatePrice(this); });
                newRow.querySelector('.item-quantity').addEventListener('input', function() { updatePrice(this.closest('.item-row').querySelector('.book-select')); });
                newRow.querySelector('.remove-item-btn').addEventListener('click', function() { removeItemRow(this); });
                updateOverallTotal();
            });

            function removeItemRow(button) {
                button.closest('.item-row').remove();
                updateOverallTotal();
            }

            // Initial setup for existing items
            document.querySelectorAll('.book-select').forEach(select => {
                select.addEventListener('change', function() { updatePrice(this); });
            });
            document.querySelectorAll('.item-quantity').forEach(input => {
                input.addEventListener('input', function() { updatePrice(this.closest('.item-row').querySelector('.book-select')); });
            });
            document.querySelectorAll('.remove-item-btn').forEach(button => {
                button.addEventListener('click', function() { removeItemRow(this); });
            });

            updateOverallTotal(); // Calculate initial total on page load
        });
    </script>
</body>
</html> 