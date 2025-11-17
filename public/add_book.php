<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
include 'header.php';

$feedback = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_post();
    if (!verify_csrf_token($_POST['csrf_token'] ?? '', 'add_book')) {
        $feedback = "<div class='error-msg'>طلب غير صالح (CSRF)!</div>";
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
            $feedback = "<div class='error-msg'>يجب اختيار مرحلة واحدة على الأقل مع كمية أكبر من صفر.</div>";
        } else {
            $sql = "INSERT INTO books (title, category, price) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $title, $category, $price);
            
            if ($stmt->execute()) {
                $book_id = $stmt->insert_id;
                $success = true;
                $stage_stmt = $conn->prepare("INSERT INTO book_stages (book_id, stage, quantity) VALUES (?, ?, ?)");
                foreach ($selected_stages as $stage) {
                    $qty = isset($stage_qty[$stage]) ? intval($stage_qty[$stage]) : 0;
                    if ($qty > 0) {
                        $stage_stmt->bind_param("isi", $book_id, $stage, $qty);
                        if (!$stage_stmt->execute()) {
                            $success = false;
                        }
                    }
                }
                $stage_stmt->close();
                $feedback = $success
                    ? "<div class='success-msg'>تم إضافة الكتاب والمراحل بنجاح!</div>"
                    : "<div class='error-msg'>حدث خطأ أثناء إضافة المراحل. يرجى مراجعة البيانات.</div>";
            } else {
                error_log("خطأ في إضافة كتاب: " . $stmt->error);
                $feedback = "<div class='error-msg'>حدث خطأ أثناء إضافة الكتاب. يرجى المحاولة مرة أخرى.</div>";
            }
            $stmt->close();
        }
    }
}

$csrf_token = csrf_token('add_book');
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إضافة كتاب جديد</title>
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
        button[type="submit"] {
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
        }
        button[type="submit"]:hover {
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
            button[type="submit"] {
                font-size: 1.18em;
                padding: 17px 0;
                border-radius: 9px;
            }
        }
    </style>
</head>
<body>
    <div class="container container-small">
        <h2>إضافة كتاب جديد</h2>
        <?= $feedback ?>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            <label>الفئة:</label>
            <select name="category" required>
                <option value="انجليزي">انجليزي</option>
                <option value="حاسوب">حاسوب</option>
                <option value="تمهيدي">تمهيدي</option>
                <option value="تربية فنية">تربية فنية</option>
            </select>

            <label>اسم الكتاب:</label>
            <input type="text" name="title" required>

            <label>السعر:</label>
            <input type="number" name="price" required>

            <label>المراحل والكميات:</label>
            <div id="stages-container">
                <?php
                $stages = ['تمهيدي', 'أول أساسي', 'ثاني أساسي', 'ثالث أساسي', 'رابع أساسي', 'خامس أساسي', 'سادس أساسي',
                    'أول إعدادي', 'ثاني إعدادي', 'ثالث إعدادي'];
                foreach ($stages as $stage) {
                    echo "<div style='margin-bottom:7px;'>";
                    echo "<label style='display:inline-block;'><input type='checkbox' name='stage_selected[]' value='$stage' onchange='toggleQty(this)'> $stage</label> ";
                    echo "<input type='number' name='stage_qty[$stage]' min='0' value='' placeholder='الكمية' style='width:70px;display:none;margin-right:8px;'>";
                    echo "</div>";
                }
                ?>
            </div>

            <button type="submit">إضافة الكتاب</button>
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
