<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
function security_headers(): void {
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");
    }
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $st = db()->prepare('SELECT id,email,name,role,active FROM users WHERE id=? AND active=1');
    $st->execute([$_SESSION['user_id']]);
    return $st->fetch() ?: null;
}
function require_login(): array { $u=current_user(); if(!$u){ header('Location: login.php'); exit; } return $u; }
function require_admin(): array { $u=require_login(); if(($u['role']??'')!=='admin'){ http_response_code(403); exit('Accès refusé'); } return $u; }
function redirect(string $url): void { header('Location: '.$url); exit; }
function is_logged(): bool { return current_user() !== null; }
function base_path(string $path=''): string { return rtrim(BASE_URL,'/').'/'.ltrim($path,'/'); }
