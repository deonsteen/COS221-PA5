<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
    ]);
}
 
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}
 
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}
 
function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role) {
        header('Location: /login.php?error=unauthorized');
        exit;
    }
}
 
function currentUser(): array {
    return [
        'user_id'  => $_SESSION['user_id']  ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role'     => $_SESSION['role']     ?? null,
        'sub_id'   => $_SESSION['sub_id']   ?? null,
    ];
}
 
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
 
function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}