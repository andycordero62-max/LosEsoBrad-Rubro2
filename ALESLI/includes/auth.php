<?php
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function requireLogin(): array {
    $user = getUser();
    if (!$user) {
        header('Location: /index.php');
        exit;
    }
    return $user;
}

function requireRole(string ...$roles): array {
    $user = requireLogin();
    if (!in_array($user['rol'], $roles)) {
        header('Location: /index.php');
        exit;
    }
    return $user;
}

function isAdmin(): bool {
    $u = getUser();
    return $u && $u['rol'] === 'admin';
}

function isEmpleado(): bool {
    $u = getUser();
    return $u && in_array($u['rol'], ['admin', 'empleado']);
}
