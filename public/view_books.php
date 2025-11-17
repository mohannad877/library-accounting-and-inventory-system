<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$error_message = '';
$books = [];

$sql = "SELECT * FROM books ORDER BY category, series, title";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("خطأ في إعداد استعلام عرض الكتب: " . $conn->error);
    $error_message = "حدث خطأ أثناء إعداد جلب بيانات الكتب. يرجى المحاولة لاحقاً.";
} else {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>عرض المخازن</title>
    <style>
        body { background: #f4f4f6; font-family: 'Cairo', Tahoma, Arial, sans-serif; margin: 0; padding-top: 44px; }
        .container { max-width: 1100px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0; padding: 32px 18px; }
        h2 { text-align: center; color: #333; margin-bottom: 24px; font-size: 1.18em; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; background: #fff; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: center; color: #333; }
        th { background: #ececec; color: #444; font-weight: bold; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f0f0f0; }
        @media (max-width: 700px) {
            .container {
                max-width: 99vw;
                margin: 2vw auto 0 auto;
                padding: 6px 2vw 12px 2vw;
                border-radius: 7px;
            }
            h2 {
                font-size: 1.13em;
                margin-top: 10px;
                margin-bottom: 12px;
            }
            table, th, td {
                font-size: 15px !important;
                padding: 8px 4px !important;
            }
            th, td {
                min-width: 48px;
            }
            .container-large {
                padding: 0 2vw !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container container-large">
        <h2>عرض المخازن (الكتب)</h2>
        <div style="display: flex; justify-content: center; margin-bottom: 18px;">
            <form method="get" action="export_books.php" style="margin: 0;">
                <button type="submit" style="background: #218838; color: #fff; padding: 7px 18px; font-size: 1em; border-radius: 7px; border: none; margin-bottom: 0; cursor: pointer;">تصدير إلى CSV</button>
            </form>
        </div>
        <?php if ($error_message): ?>
            <div class="alert error"><?= $error_message ?></div>
        <?php endif; ?>
        <table>
            <tr>
                <th>الاسم</th>
                <th>الفئة</th>
                <th>السلسلة</th>
                <th>الترم/السنة</th>
                <th>السعر</th>
                <th>الكميات لكل مرحلة</th>
                <th>تعديل</th>
            </tr>
            <?php if (!empty($books)): ?>
                <?php
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
                ?>
                <?php foreach($books as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><?= htmlspecialchars($row['series']) ?></td>
                    <td><?= htmlspecialchars($row['term']) ?></td>
                    <td><?= number_format($row['price']) ?></td>
                    <td>
                        <table style="width:100%;background:#f9f9f9;border-radius:6px;">
                            <tr>
                                <?php if (!empty($stage_quantities[$row['id']])): ?>
                                    <?php foreach($stage_quantities[$row['id']] as $stage => $qty): ?>
                                        <th style="font-size:12px;min-width:60px;"> <?= htmlspecialchars($stage) ?> </th>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <th style="color:#aaa;">لا يوجد مخزون</th>
                                <?php endif; ?>
                            </tr>
                            <tr>
                                <?php if (!empty($stage_quantities[$row['id']])): ?>
                                    <?php foreach($stage_quantities[$row['id']] as $stage => $qty): ?>
                                        <td style="font-size:13px;<?= ($qty == 0) ? 'color:#aaa;' : '' ?>;min-width:60px;"> <?= $qty ?> </td>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <td style="color:#aaa;">-</td>
                                <?php endif; ?>
                            </tr>
                        </table>
                    </td>
                    <td><a href="edit_book.php?id=<?= $row['id'] ?>" style="color:#fff;background:#007bff;padding:4px 10px;border-radius:5px;text-decoration:none;">تعديل</a></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align: center;">لا توجد كتب لعرضها.</td></tr>
            <?php endif; ?>
        </table>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?> 