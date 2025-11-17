<?php
// يتم تضمين الهيدر بعد تحميل bootstrap في الصفحات الرئيسية
?>
<div class="main-header">
    <div class="header-container">
        <a href="/library-system/index.php" class="header-logo">📚 نظام المكتبة</a>
        <nav class="header-nav">
            <a href="/library-system/index.php"><span class="nav-icon">🏠</span>الرئيسية</a>
            <?php if (isset($_SESSION['user_id'])): // إظهار الروابط فقط للمستخدمين المسجلين ?>
                <a href="/library-system/public/reports.php"><span class="nav-icon">📈</span>تقارير المبيعات</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): // رابط لوحة تحكم المدير ?>
                    <a href="/library-system/public/admin/dashboard.php"><span class="nav-icon">⚙️</span>لوحة المدير</a>
                <?php endif; ?>
                <a href="/library-system/public/logout.php" style="background:#dc3545; color:#fff;"><span class="nav-icon">🚪</span>تسجيل الخروج</a>
            <?php else: // إظهار رابط تسجيل الدخول لغير المسجلين ?>
                <a href="login.php"><span class="nav-icon">🔑</span>تسجيل الدخول</a>
            <?php endif; ?>
        </nav>
    </div>
</div>
<style>
@import url('/library-system/assets/style.css');
.main-header {
    background: #f8f9fa;
    box-shadow: 0 1px 6px #0001;
    padding: 0;
    margin-bottom: 14px;
    border-bottom: 1px solid #e0e0e0;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    border-radius: 0;
    height: 44px;
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 12px;
    height: 44px;
}

.header-logo {
    color: #222;
    font-size: 0.93em;
    font-weight: bold;
    text-decoration: none;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    white-space: nowrap;
    margin-right: 20px;
}

.header-nav {
    display: flex;
    align-items: center;
    gap: 0.1em;
}

.header-nav a {
    color: #222;
    text-decoration: none;
    margin-left: 5px;
    font-size: 0.85em;
    font-weight: 500;
    transition: color 0.2s, background 0.2s, box-shadow 0.2s;
    min-width: 120px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 10px;
    border-radius: 4px;
    background: #f3f3f3;
    border: 1.5px solid #e0e0e0;
    letter-spacing: 0.1px;
    box-sizing: border-box;
}

.header-nav a:hover {
    background: #222;
    color: #fff;
    box-shadow: 0 2px 8px #0001;
}

.nav-icon {
    font-size: 1em;
    margin-left: 2px;
    margin-right: 0.5px;
    display: inline-block;
    vertical-align: middle;
}

body {
    padding-top: 44px !important;
    direction: rtl;
}

@media (max-width: 900px) {
    .main-header {
        border-radius: 0;
        height: 44px;
    }

    .header-container {
        flex-direction: column;
        height: auto;
        padding: 0 2px;
    }

    .header-nav {
        margin-top: 3px;
        flex-wrap: wrap;
        gap: 0.05em;
    }

    .header-nav a {
        margin-left: 3px;
        font-size: 0.83em;
        min-width: 100px;
        height: 32px;
        padding: 0 6px;
    }
}

@media (max-width: 700px) {
    .main-header {
        height: 32px !important;
        min-height: 32px !important;
        margin-bottom: 6px !important;
    }
    .header-container {
        flex-direction: row !important;
        height: 32px !important;
        padding: 0 2px !important;
        align-items: center !important;
        justify-content: space-between !important;
    }
    .header-logo {
        font-size: 0.68em !important;
        font-weight: bold !important;
        margin-right: 6px !important;
        padding: 0 !important;
    }
    .header-nav {
        flex-direction: row !important;
        margin-top: 0 !important;
        gap: 0.05em !important;
        flex-wrap: nowrap !important;
    }
    .header-nav a {
        font-size: 0.59em !important;
        font-weight: bold !important;
        min-width: 54px !important;
        height: 22px !important;
        padding: 0 2px !important;
        margin-left: 2px !important;
        border-radius: 3px !important;
    }
    .nav-icon {
        font-size: 0.85em !important;
        margin-left: 1px !important;
    }
    body {
        padding-top: 32px !important;
    }
}
</style>