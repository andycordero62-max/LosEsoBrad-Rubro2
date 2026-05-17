<?php
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
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
        header('Location: /ALESLI/index.php');
        exit;
    }
    return $user;
}

function requireRole(string ...$roles): array {
    $user = requireLogin();
    if (!in_array($user['rol'], $roles)) {
        header('Location: /ALESLI/dashboard.php');
        exit;
    }
    return $user;
}

function isAdmin(): bool {
    $u = getUser();
    return $u && $u['rol'] === 'admin';
}

function isEmpleadoOrAdmin(): bool {
    $u = getUser();
    return $u && in_array($u['rol'], ['admin', 'empleado']);
}
