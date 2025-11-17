<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
include 'header.php';

$stages = [
    'تمهيدي', 'أول أساسي', 'ثاني أساسي', 'ثالث أساسي', 'رابع أساسي', 'خامس أساسي', 'سادس أساسي',
    'أول إعدادي', 'ثاني إعدادي', 'ثالث إعدادي'
];

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($book_id <= 0) {
    echo "<div class='error-msg'>معرف الكتاب غير صحيح.</div>";
    exit();
}

// جلب بيانات الكتاب
$sql = "SELECT * FROM books WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book) {
    echo "<div class='error-msg'>الكتاب غير موجود.</div>";
    exit();
}

// جلب الكميات لكل مرحلة
$stage_quantities = array_fill_keys($stages, 0);
$sql2 = "SELECT stage, quantity FROM book_stages WHERE book_id = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $book_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) {
    $stage_quantities[$row['stage']] = $row['quantity'];
}
$stmt2->close();

// عند الحفظ
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'edit_book')) {
        echo "<div class='error-msg'>طلب غير صالح (CSRF)!</div>";
    } else {
        $title = sanitize_string($_POST['title'] ?? '');
        $category = sanitize_string($_POST['category'] ?? '');
        $price = intval($_POST['price'] ?? 0);
        $selected_stages = $_POST['stage_selected'] ?? [];
        $stage_qty = $_POST['stage_qty'] ?? [];
        $valid_stage = false;
        foreach ($selected_stages as $stage) {
            if (isset($stage_qty[$stage]) && intval($stage_qty[$stage]) > 0) {
                $valid_stage = true;
                break;
            }
        }
        if (!$valid_stage) {
            echo "<div class='error-msg'>يجب اختيار مرحلة واحدة على الأقل مع كمية أكبر من صفر.</div>";
        } else {
            // تحديث بيانات الكتاب
            $sql = "UPDATE books SET title=?, category=?, price=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $title, $category, $price, $book_id);
            $stmt->execute();
            $stmt->close();
            // تحديث الكميات لكل مرحلة
            foreach ($stages as $stage) {
                $qty = (in_array($stage, $selected_stages) && isset($stage_qty[$stage])) ? intval($stage_qty[$stage]) : 0;
                // تحقق إذا كان هناك صف موجود
                $sql_check = "SELECT id FROM book_stages WHERE book_id=? AND stage=?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("is", $book_id, $stage);
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    // تحديث
                    $sql_up = "UPDATE book_stages SET quantity=? WHERE book_id=? AND stage=?";
                    $stmt_up = $conn->prepare($sql_up);
                    $stmt_up->bind_param("iis", $qty, $book_id, $stage);
                    $stmt_up->execute();
                    $stmt_up->close();
                } else {
                    // إضافة جديد
                    if ($qty > 0) {
                        $sql_in = "INSERT INTO book_stages (book_id, stage, quantity) VALUES (?, ?, ?)";
                        $stmt_in = $conn->prepare($sql_in);
                        $stmt_in->bind_param("isi", $book_id, $stage, $qty);
                        $stmt_in->execute();
                        $stmt_in->close();
                    }
                }
                $stmt_check->close();
            }
            echo "<div class='success-msg'>تم تحديث بيانات الكتاب والكميات بنجاح!</div>";
            // تحديث الكميات المعروضة
            foreach ($stages as $stage) {
                $stage_quantities[$stage] = (in_array($stage, $selected_stages) && isset($stage_qty[$stage])) ? intval($stage_qty[$stage]) : 0;
            }
            // تحديث بيانات الكتاب المعروضة
            $book['title'] = $title;
            $book['category'] = $category;
            $book['price'] = $price;
        }
    }
}

// توليد رمز CSRF
$csrf_token = csrf_token('edit_book');
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تعديل كتاب</title>
    <style>
        body { background: #f4f4f6; font-family: Tahoma, Arial, 'Segoe UI', sans-serif; margin: 0; padding-top: 44px; }
        .container { max-width: 400px; margin: 50px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0; padding: 32px 18px 24px 18px; }
        h2 { text-align: center; color: #333; margin-bottom: 24px; font-size: 1.13em; margin-top: 0; }
        label { display: block; margin-bottom: 6px; color: #555; font-weight: 600; }
        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: #f8f8f8;
            color: #333;
            transition: border 0.2s;
        }
        input[type="text"]:focus, input[type="number"]:focus, select:focus {
            border-color: #999;
            outline: none;
        }
        button[type="submit"], .stock-btn {
            width: 100%;
            background: #666;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px 0;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            display: block;
            text-align: center;
            text-decoration: none;
        }
        button[type="submit"]:hover, .stock-btn:hover {
            background: #444;
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 18px;
            text-align: center;
            border: 1px solid #b7dfc5;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 18px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
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
            input[type="text"], input[type="number"], select {
                font-size: 18px;
                padding: 15px 13px;
                margin-bottom: 18px;
            }
            button[type="submit"], .stock-btn {
                font-size: 1.18em;
                padding: 17px 0;
                border-radius: 9px;
            }
        }
    </style>
</head>
<body>
    <div class="container container-small">
        <h2>تعديل كتاب</h2>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            <label>الفئة:</label>
            <select name="category" required>
                <option value="انجليزي" <?= $book['category'] == 'انجليزي' ? 'selected' : '' ?>>انجليزي</option>
                <option value="حاسوب" <?= $book['category'] == 'حاسوب' ? 'selected' : '' ?>>حاسوب</option>
                <option value="تمهيدي" <?= $book['category'] == 'تمهيدي' ? 'selected' : '' ?>>تمهيدي</option>
                <option value="تربية فنية" <?= $book['category'] == 'تربية فنية' ? 'selected' : '' ?>>تربية فنية</option>
            </select>

            <label>اسم الكتاب:</label>
            <input type="text" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>

            <label>السعر:</label>
            <input type="number" name="price" value="<?= $book['price'] ?>" required>

            <label>المراحل والكميات:</label>
            <div id="stages-container">
                <?php
                foreach ($stages as $stage) {
                    $checked = ($stage_quantities[$stage] > 0) ? 'checked' : '';
                    $qty_val = ($stage_quantities[$stage] > 0) ? $stage_quantities[$stage] : '';
                    echo "<div style='margin-bottom:7px;'>";
                    echo "<label style='display:inline-block;'><input type='checkbox' name='stage_selected[]' value='$stage' onchange='toggleQty(this)' $checked> $stage</label> ";
                    $display = ($stage_quantities[$stage] > 0) ? 'inline-block' : 'none';
                    echo "<input type='number' name='stage_qty[$stage]' min='0' value='$qty_val' placeholder='الكمية' style='width:70px;display:$display;margin-right:8px;'>";
                    echo "</div>";
                }
                ?>
            </div>

            <button type="submit">حفظ التعديلات</button>
            <a href="view_books.php" class="stock-btn">عرض التعديلات</a>
        </form>
        <script>
        function toggleQty(checkbox) {
            var qtyInput = checkbox.parentNode.parentNode.querySelector('input[type="number"]');
            if (checkbox.checked) {
                qtyInput.style.display = 'inline-block';
                qtyInput.required = true;
            } else {
                qtyInput.style.display = 'none';
                qtyInput.value = '';
                qtyInput.required = false;
            }
        }
        </script>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?> 