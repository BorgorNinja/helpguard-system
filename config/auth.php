<?php
// config/auth.php - Role guard and portal redirect helper

function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php'); exit;
    }
}

function require_role(array $allowed): void {
    require_login();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $allowed, true)) {
        // Redirect to their own portal instead of 403
        header('Location: ' . portal_url($role)); exit;
    }
}

function require_approved(): void {
    require_login();
    if (!($_SESSION['is_approved'] ?? false)) {
        session_destroy();
        header('Location: /login.php?pending=1'); exit;
    }
}

function portal_url(string $role): string {
    return match($role) {
        'barangay'       => '/portal/barangay.php',
        'lgu'            => '/portal/lgu.php',
        'first_responder'=> '/portal/responder.php',
        'admin'          => '/admin.php',
        default          => '/portal/community.php',
    };
}

function redirect_to_portal(): void {
    header('Location: ' . portal_url($_SESSION['role'] ?? 'community')); exit;
}

function role_label(string $role): string {
    return match($role) {
        'community'      => 'Community Member',
        'barangay'       => 'Barangay Official',
        'lgu'            => 'LGU Official',
        'first_responder'=> 'First Responder',
        'admin'          => 'System Administrator',
        default          => 'User',
    };
}

function role_badge_color(string $role): string {
    return match($role) {
        'community'      => '#2563eb',
        'barangay'       => '#166534',
        'lgu'            => '#0a3d62',
        'first_responder'=> '#b91c1c',
        'admin'          => '#6d28d9',
        default          => '#555',
    };
}
