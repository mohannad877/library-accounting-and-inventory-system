<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
include 'header.php';

// جلب الكتب للاختيار في الفورم
$books = [];
// التأكد من جلب السعر أيضًا لتستخدمه JavaScript
$sql = "SELECT id, title, price FROM books ORDER BY title";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}
// جلب المراحل المتوفرة لكل كتاب
$book_stages_map = [];
$q = $conn->query("SELECT book_id, stage FROM book_stages WHERE quantity > 0");
while ($r = $q->fetch_assoc()) {
    $book_stages_map[$r['book_id']][] = $r['stage'];
}

// جلب المراحل الممكنة
$all_stages = ['تمهيدي', 'أول أساسي', 'ثاني أساسي', 'ثالث أساسي', 'رابع أساسي', 'خامس أساسي', 'سادس أساسي', 'أول إعدادي', 'ثاني إعدادي', 'ثالث إعدادي'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_post();
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'add_transaction')) {
        $_SESSION['error_message'] = 'طلب غير صالح (CSRF)!';
    } else {
        $invoice_number = trim($_POST['invoice_number'] ?? '');
        $book_ids = $_POST['book_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $purchase_prices = $_POST['purchase_price'] ?? [];
        $stages_selected = $_POST['stage'] ?? [];
        $type = 'وارد'; // هذه الصفحة مخصصة لعمليات الشراء (الوارد)

        if (empty($invoice_number)) {
            $_SESSION['error_message'] = 'يرجى إدخال رقم الفاتورة.';
        } elseif (empty($book_ids)) {
            $_SESSION['error_message'] = 'يجب إضافة صنف واحد على الأقل للفاتورة.';
        } else {
            $all_success = true;
            $conn->begin_transaction(); // بدء المعاملة

            for ($i = 0; $i < count($book_ids); $i++) {
                $book_id = intval($book_ids[$i]);
                $quantity = intval($quantities[$i]);
                $purchase_price = floatval($purchase_prices[$i]);
                $stage = $stages_selected[$i] ?? '';
                if (!in_array($stage, $all_stages)) {
                    $_SESSION['error_message'] = 'يجب اختيار المرحلة بشكل صحيح لكل كتاب.';
                    $all_success = false;
                    break;
                }
                // تحقق من البيانات الأساسية
                if ($book_id <= 0 || $quantity <= 0 || $purchase_price <= 0) {
                    $_SESSION['error_message'] = 'بيانات غير صالحة للصنف رقم ' . ($i + 1) . '.';
                    $all_success = false;
                    break;
                }
                // إدخال العملية في جدول transactions
                $stmt = $conn->prepare("INSERT INTO transactions (book_id, stage, type, quantity, purchase_price, invoice_number) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    error_log("خطأ في إعداد استعلام إدخال العملية: " . $conn->error);
                    $_SESSION['error_message'] = 'حدث خطأ داخلي أثناء إعداد العملية.';
                    $all_success = false;
                    break;
                }
                $stmt->bind_param("issids", $book_id, $stage, $type, $quantity, $purchase_price, $invoice_number);
                if (!$stmt->execute()) {
                    error_log("خطأ في تسجيل العملية: " . $stmt->error);
                    $_SESSION['error_message'] = 'حدث خطأ أثناء تسجيل الصنف رقم ' . ($i + 1) . '.';
                    $all_success = false;
                    break;
                }
                $stmt->close();
                // تحديث أو إضافة الكمية في جدول book_stages للمرحلة فقط
                $check_stmt = $conn->prepare("SELECT id FROM book_stages WHERE book_id = ? AND stage = ?");
                $check_stmt->bind_param("is", $book_id, $stage);
                $check_stmt->execute();
                $check_stmt->store_result();
                if ($check_stmt->num_rows > 0) {
                    // موجودة: تحديث الكمية
                    $update_stmt = $conn->prepare("UPDATE book_stages SET quantity = quantity + ? WHERE book_id = ? AND stage = ?");
                    $update_stmt->bind_param("iis", $quantity, $book_id, $stage);
                    $update_stmt->execute();
                    $update_stmt->close();
                } else {
                    // غير موجودة: إضافة صف جديد
                    $insert_stmt = $conn->prepare("INSERT INTO book_stages (book_id, stage, quantity) VALUES (?, ?, ?)");
                    $insert_stmt->bind_param("isi", $book_id, $stage, $quantity);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }

            if ($all_success) {
                $conn->commit(); // تأكيد المعاملة
                $_SESSION['success_message'] = 'تم تسجيل الفاتورة بنجاح وتحديث المخزون.';
            } else {
                $conn->rollback(); // التراجع عن المعاملة عند وجود خطأ
                // رسالة الخطأ تم تعيينها بالفعل داخل الحلقة
            }
        }
        header("Location: view_invoices.php");
        exit;
    }
}

$csrf_token = csrf_token('add_transaction');

// عرض رسائل النجاح أو الخطأ
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert error">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تسجيل عملية شراء (فاتورة)</title>
    <style>
        body { background: #f4f4f6; font-family: Tahoma, Arial, 'Segoe UI', sans-serif; margin: 0; padding-top: 44px; }
        .container { max-width: 800px; margin: 50px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0; padding: 32px 18px 24px 18px; }
        h2 { text-align: center; color: #333; margin-bottom: 24px; font-size: 1.13em; margin-top: 0; }
        label { display: block; margin-bottom: 6px; color: #555; font-weight: 600; }
        input[type="number"], input[type="text"], select {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: #f8f8f8;
            color: #333;
            transition: border 0.2s;
            box-sizing: border-box;
        }
        input[type="number"]:focus, input[type="text"]:focus, select:focus {
            border-color: #999;
            outline: none;
        }
        button[type="submit"] {
            width: 100%;
            background: #666;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px 25px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        button[type="submit"]:hover {
            background: #444;
        }
        .add-item-btn {
            background: #666;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 7px 18px;
            font-size: 0.97em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        .add-item-btn:hover {
            background: #444;
        }
        .remove-item-btn {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 8px 14px;
            font-size: 0.9em;
            margin-top: 0;
            width: auto;
            white-space: nowrap;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .remove-item-btn:hover {
            background: #c82333;
        }
        .item-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            align-items: center;
            border: 1px solid #e0e0e0;
            padding: 10px;
            border-radius: 8px;
            background: #fdfdfd;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }
        .item-row > div { flex: 1; }
        .total-price-section {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px dashed #bbb;
            text-align: right;
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        .total-price-section span {
            color: #000;
            margin-left: 10px;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .alert.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
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
            input[type="number"], input[type="text"], select {
                font-size: 18px;
                padding: 15px 13px;
                margin-bottom: 16px;
            }
            .item-row > div { padding: 0 2px; }
            .add-item-btn, .remove-item-btn, button[type="submit"] {
                font-size: 1.18em;
                padding: 17px 0;
                border-radius: 9px;
            }
        }
    </style>
    <script>
        let itemIndex = 0;
        const books = <?= json_encode($books) ?>;
        const allStages = <?= json_encode($all_stages) ?>;

        function calculateItemPrice(index) {
            const bookId = document.getElementById(`book_id_${index}`).value;
            const quantity = parseFloat(document.getElementById(`quantity_${index}`).value);
            const priceInput = document.getElementById(`purchase_price_${index}`);

            if (bookId && !isNaN(quantity) && quantity > 0) {
                // Find the selected book from the PHP-provided books array
                const selectedBook = books.find(book => book.id == bookId);

                if (selectedBook && selectedBook.price) {
                    // Calculate total price for this item (price per unit * quantity)
                    const itemTotalPrice = (parseFloat(selectedBook.price) * quantity).toFixed(2);
                    priceInput.value = itemTotalPrice;
                } else {
                    priceInput.value = '';
                }
            } else {
                priceInput.value = '';
            }
            updateTotalPrice();
        }

        function updateTotalPrice() {
            let total = 0;
            document.querySelectorAll('input[name^="purchase_price["]').forEach(input => {
                const price = parseFloat(input.value);
                if (!isNaN(price)) {
                    total += price;
                }
            });
            document.getElementById('total_invoice_price').textContent = total.toFixed(2);
        }

        function addItemRow() {
            itemIndex++;
            const itemsContainer = document.getElementById('items_container');
            const newRow = document.createElement('div');
            newRow.classList.add('item-row');
            newRow.innerHTML = `
                <div style="min-width:160px;">
                    <select id="book_id_${itemIndex}" name="book_id[]" onchange="calculateItemPrice(${itemIndex});updateStages(this,${itemIndex})" required>
                        <option value="">اختر كتاب...</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?= $book['id'] ?>" data-price="<?= $book['price'] ?>"><?= htmlspecialchars($book['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="min-width:120px;">
                    <select id="stage_${itemIndex}" name="stage[]" required>
                        <option value="">اختر المرحلة</option>
                    </select>
                </div>
                <div style="min-width:90px;">
                    <input type="number" id="quantity_${itemIndex}" name="quantity[]" min="1" value="1" oninput="calculateItemPrice(${itemIndex})" required placeholder="الكمية">
                </div>
                <div style="min-width:120px;">
                    <input type="number" id="purchase_price_${itemIndex}" name="purchase_price[]" step="0.01" readonly required placeholder="السعر الإجمالي">
                </div>
                <div style="min-width:70px;">
                    <button type="button" class="remove-item-btn" onclick="removeItemRow(this)">إزالة</button>
                </div>
            `;
            itemsContainer.appendChild(newRow);
            updateTotalPrice();
        }

        function removeItemRow(button) {
            button.closest('.item-row').remove();
            updateTotalPrice();
        }

        function updateStages(select, index) {
            var stageSelect = document.getElementById('stage_' + index);
            stageSelect.innerHTML = '<option value="">اختر المرحلة</option>';
            allStages.forEach(function(stage) {
                stageSelect.innerHTML += '<option value="'+stage+'">'+stage+'</option>';
            });
        }

        // إضافة صف واحد عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            addItemRow();
            // إضافة مستمعي الأحداث للعناصر الموجودة مبدئياً إذا كانت هناك
            document.querySelectorAll('select[name^="book_id["]').forEach(select => {
                select.addEventListener('change', (event) => {
                    const index = event.target.id.split('_')[2];
                    calculateItemPrice(index);
                });
            });
            document.querySelectorAll('input[name^="quantity["]').forEach(input => {
                input.addEventListener('input', (event) => {
                    const index = event.target.id.split('_')[1];
                    calculateItemPrice(index);
                });
            });
        });
    </script>
</head>
<body>
    <div class="container container-medium">
        <h2>تسجيل عملية شراء (فاتورة)</h2>
        <form method="post" action="" id="transaction-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <label for="invoice_number">رقم الفاتورة:</label>
            <input type="text" id="invoice_number" name="invoice_number" required>

            <div id="items_container">
                <!-- Item rows will be added here by JavaScript -->
            </div>

            <button type="button" class="add-item-btn" onclick="addItemRow()">+ إضافة صنف</button>

            <div class="total-price-section">
                <span>الإجمالي الكلي:</span> <span id="total_invoice_price">0.00</span>
            </div>

            <button type="submit">تسجيل الفاتورة</button>
        </form>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
<?php
$conn->close();
?>
