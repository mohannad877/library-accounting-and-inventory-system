<?php

if (!function_exists('start_secure_session')) {
    function start_secure_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'],
            'path'     => $cookieParams['path'],
            'domain'   => $cookieParams['domain'],
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        if (!headers_sent()) {
            session_name('library_session');
        }

        session_start();
    }
}

if (!function_exists('regenerate_session_id_once')) {
    function regenerate_session_id_once(): void
    {
        if (empty($_SESSION['__session_regenerated'])) {
            session_regenerate_id(true);
            $_SESSION['__session_regenerated'] = true;
        }
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(string $formKey = 'default'): string
    {
        if (empty($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }

        if (empty($_SESSION['csrf_tokens'][$formKey])) {
            $_SESSION['csrf_tokens'][$formKey] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_tokens'][$formKey];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token, string $formKey = 'default'): bool
    {
        if (empty($token) || empty($_SESSION['csrf_tokens'][$formKey])) {
            return false;
        }

        $isValid = hash_equals($_SESSION['csrf_tokens'][$formKey], $token);

        if ($isValid) {
            unset($_SESSION['csrf_tokens'][$formKey]);
        }

        return $isValid;
    }
}

if (!function_exists('require_post')) {
    function require_post(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('طريقة الطلب غير مسموح بها.');
        }
    }
}

if (!function_exists('require_login')) {
    function require_login(?string $role = null): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /library-system/public/login.php');
            exit();
        }

        if ($role !== null && (!isset($_SESSION['role']) || $_SESSION['role'] !== $role)) {
            http_response_code(403);
            exit('غير مصرح لك بالوصول إلى هذه الصفحة.');
        }
    }
}

if (!function_exists('sanitize_string')) {
    function sanitize_string(?string $value): string
    {
        $value = (string)($value ?? '');
        $value = strip_tags($value);
        return trim($value);
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): ?string
    {
        if ($message === null) {
            if (empty($_SESSION['flash'][$key])) {
                return null;
            }
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }

        $_SESSION['flash'][$key] = $message;
        return null;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header("Location: {$path}");
        exit();
    }
}

