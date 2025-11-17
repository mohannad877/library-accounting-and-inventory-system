<?php
require_once __DIR__ . '/config/bootstrap.php';
require_login();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة المكتبة</title>
    <style>
        body {
            background: #f4f4f6;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px #0001;
            border: 1px solid #e0e0e0;
            padding: 32px 18px 22px 18px;
            text-align: center;
            position: relative;
        }
        .main-icon {
            width: 54px;
            height: 54px;
            background: #ececec;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
            box-shadow: 0 1px 4px #0001;
        }
        .main-icon svg {
            width: 26px;
            height: 26px;
            fill: #222;
        }
        h1 {
            color: #222;
            margin-bottom: 8px;
            font-size: 1.25em;
            letter-spacing: 0.5px;
            margin-top: 36px;
        }
        p {
            color: #444;
            margin-bottom: 24px;
            font-size: 1em;
        }
        .links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 18px;
        }
        .main-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: #444;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 0;
            font-size: 0.93em;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 1px 4px #0001;
            transition: background 0.18s, transform 0.13s;
        }
        .main-btn:hover {
            background: #222;
            transform: translateY(-1px) scale(1.01);
        }
        .main-btn svg {
            width: 15px;
            height: 15px;
            fill: #fff;
        }
        @media (max-width: 700px) {
            .container {
                max-width: 99vw;
                margin: 2vw auto 0 auto;
                padding: 4px 1vw 8px 1vw;
                border-radius: 6px;
            }
            .main-icon {
                width: 36px;
                height: 36px;
                margin-bottom: 7px;
            }
            h1 {
                font-size: 0.92em;
                margin-top: 4px;
                margin-bottom: 2px;
                letter-spacing: 0;
            }
            p {
                font-size: 0.91em;
                margin-bottom: 8px;
            }
            .links {
                grid-template-columns: 1fr;
                gap: 6px;
                margin-top: 5px;
            }
            .main-btn {
                font-size: 0.95em;
                padding: 8px 0;
                border-radius: 5px;
            }
            /* تصغير الهيدر */
            header, .header, .main-header {
                min-height: 28px !important;
                padding: 2px 0 2px 0 !important;
            }
            header h2, .header h2, .main-header h2 {
                font-size: 1em !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'public/header.php'; ?>
    <div class="container container-medium">
        <div class="main-icon">
            <svg viewBox="0 0 48 48"><path d="M8 40V8a2 2 0 0 1 2-2h28a2 2 0 0 1 2 2v32a2 2 0 0 1-2 2H10a2 2 0 0 1-2-2zm4-2h24V10H12v28zm2-24h20v4H14v-4zm0 8h20v2H14v-2zm0 6h12v2H14v-2z"/></svg>
        </div>
        <h1>النظام المحاسبي للمكتبة</h1>
        <p style="direction: rtl;">مرحبًا بك في نظام إدارة الكتب والمخزون والفواتير.<br>اختر العملية التي تريد تنفيذها:</p>

        <div class="links">
            <a href="public/view_books.php" class="main-btn" title="عرض جميع الكتب والكميات المتوفرة لكل مرحلة دراسية مع تفاصيل المخزون.">
                <svg viewBox="0 0 24 24"><path d="M3 6v15c0 .6.4 1 1 1h16c.6 0 1-.4 1-1V6c0-.6-.4-1-1-1h-4V3c0-.6-.4-1-1-1H9C8.4 2 8 2.4 8 3v2H4c-.6 0-1 .4-1 1zm2 0h14v14H5V6zm6-2h2v2h-2V4z"/></svg>
                عرض المخازن
            </a>
            <a href="public/add_book.php" class="main-btn" title="إضافة كتاب جديد وتحديد المراحل والكميات المتوفرة له في النظام.">
                <svg viewBox="0 0 24 24"><path d="M19 2H8c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 18H8V4h11v16zM6 6H4v16c0 1.1.9 2 2 2h12v-2H6V6z"/></svg>
                إضافة كتب جديدة
            </a>
            <a href="public/add_transaction.php" class="main-btn" title="تسجيل فاتورة شراء كتب وإضافة الكميات للمخزون حسب المرحلة.">
                <svg viewBox="0 0 24 24"><path d="M12 8V4l8 8-8 8v-4.1C7.1 15.9 4.5 17.5 2 20c1-5 4.5-8.5 10-12z"/></svg>
                توريد كتب
            </a>
            <a href="public/view_invoices.php" class="main-btn" title="استعراض جميع فواتير الشراء السابقة مع تفاصيل الكميات المضافة للمخزون.">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm-1 7V3.5L18.5 9H13z"/></svg>
                عرض فواتير الشراء
            </a>
            <a href="public/group_sale.php" class="main-btn" title="إنشاء فاتورة بيع جماعي لمدرسة مع اختيار الكتب والمراحل والكميات وخصمها من المخزون.">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                بيع جماعي لمدرسة
            </a>
            <a href="public/view_transactions.php" class="main-btn" title="عرض جميع عمليات البيع المنفردة للكتب حسب المرحلة مع تفاصيل كل عملية.">
                <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 8h14v-2H7v2zm0-4h14v-2H7v2zm0-6v2h14V7H7z"/></svg>
                عرض عمليات البيع
            </a>
            <a href="public/view_sales.php" class="main-btn" title="متابعة فواتير البيع الجماعي للمدارس مع تفاصيل الكتب والكميات والمدفوعات.">
                <svg viewBox="0 0 24 24"><path d="M3 17v-2h2v2H3zm0-4v-2h2v2H3zm0-4V7h2v2H3v2zm4 8h14v-2H7v2zm0-4h14v-2H7v2zm0-4h14V7H7v2z"/></svg>
                متابعة عمليات البيع للمدارس
            </a>
            <a href="public/reports.php" class="main-btn" title="عرض تقارير شاملة عن المبيعات والكميات والمدفوعات وتحليل أداء المكتبة.">
                <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c2.54 0 4.04 1.61 4.5 2.09C12.46 4.61 13.96 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                تقارير المبيعات
            </a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="public/admin/dashboard.php" class="main-btn" title="إدارة المستخدمين وصلاحياتهم وضبط إعدادات النظام (خاص بالمدير).">
                <svg viewBox="0 0 24 24"><path d="M19.4 12c.38-1.19.38-2.4-.01-3.6L22 6.55l-1.95-1.95-2.45.62c-.68-.53-1.46-.94-2.3-1.22L15 2H9l-.3 2.01c-.84.28-1.62.69-2.3 1.22L3.95 4.6l-1.95 1.95 2.61 2.85c-.39 1.2-.39 2.41 0 3.61l-2.61 2.85 1.95 1.95 2.45-.62c.68.53 1.46.94 2.3 1.22L9 22h6l.3-2.01c.84-.28 1.62-.69 2.3-1.22l2.45.62 1.95-1.95-2.61-2.85zM12 15c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/></svg>
                لوحة المدير
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php include 'public/footer.php'; ?>
</body>
</html>
