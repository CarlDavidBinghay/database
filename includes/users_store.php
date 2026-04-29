<?php
// ============================================================
//  AquaQueue — User Store (Database-backed)
//  All auth operations go through MySQL via PDO.
// ============================================================
require_once __DIR__ . '/db.php';

// ── Reset-token file (no email server in dev, so we use /tmp) ───
define('RESET_TOKENS_FILE', sys_get_temp_dir() . '/aquaqueue_reset_tokens.json');

// ────────────────────────────────────────────────────────────────
//  USER LOOKUP & VERIFICATION
// ────────────────────────────────────────────────────────────────

/**
 * Find a user row by email. Returns assoc array or false.
 */
function findUserByEmail(string $email): array|false {
    try {
        $stmt = getDB()->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.phone,
                    u.password_hash, u.is_active, r.name AS role,
                    (SELECT slug FROM booking_services bs
                     JOIN service_admin_assignments saa ON saa.service_id = bs.id
                     WHERE saa.user_id = u.id LIMIT 1) AS assigned_service
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch() ?: false;
    } catch (PDOException $e) {
        error_log('[AquaQueue] findUserByEmail: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verify email + plaintext password. Returns user array or false.
 */
function verifyUser(string $email, string $password): array|false {
    $user = findUserByEmail($email);
    if (!$user) return false;
    if (!$user['is_active']) return false;
    if (!password_verify($password, $user['password_hash'])) return false;

    // Update last_login_at
    try {
        getDB()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
               ->execute([$user['id']]);
    } catch (PDOException) {}

    return $user;
}

/**
 * Return display name from user row (first + last).
 */
function userDisplayName(array $user): string {
    return trim($user['first_name'] . ' ' . $user['last_name']);
}

// ────────────────────────────────────────────────────────────────
//  REGISTRATION
// ────────────────────────────────────────────────────────────────

/**
 * Register a new user. Returns new user id or throws PDOException.
 */
function registerUser(string $firstName, string $lastName, string $email,
                      string $phone, string $password): int {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = getDB()->prepare(
        'INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, is_active, email_verified)
         VALUES (4, ?, ?, ?, ?, ?, 1, 0)'
    );
    $stmt->execute([$firstName, $lastName, $email, $phone, $hash]);
    return (int) getDB()->lastInsertId();
}

/**
 * Check if an email is already registered.
 */
function emailExists(string $email): bool {
    $stmt = getDB()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return (bool) $stmt->fetch();
}

// ────────────────────────────────────────────────────────────────
//  PASSWORD RESET TOKENS (stored in /tmp JSON — no mail server)
// ────────────────────────────────────────────────────────────────

function loadTokens(): array {
    if (!file_exists(RESET_TOKENS_FILE)) return [];
    $raw = @file_get_contents(RESET_TOKENS_FILE);
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

function saveTokens(array $tokens): void {
    file_put_contents(RESET_TOKENS_FILE, json_encode($tokens), LOCK_EX);
}

function createResetToken(string $email): string {
    $raw    = bin2hex(random_bytes(32));
    $tokens = loadTokens();
    $tokens = array_values(array_filter($tokens, fn($t) => $t['email'] !== $email));
    $tokens[] = ['email' => $email, 'token' => hash('sha256', $raw), 'expires' => time() + 3600];
    saveTokens($tokens);
    return $raw;
}

function verifyResetToken(string $raw): string|false {
    $hash = hash('sha256', $raw);
    foreach (loadTokens() as $entry) {
        if (hash_equals($entry['token'], $hash) && $entry['expires'] > time()) {
            return $entry['email'];
        }
    }
    return false;
}

function consumeResetToken(string $raw): void {
    $hash   = hash('sha256', $raw);
    $tokens = array_values(array_filter(loadTokens(), fn($t) => !hash_equals($t['token'], $hash)));
    saveTokens($tokens);
}

/**
 * Update a user's password hash in the DB.
 */
function updatePassword(string $email, string $newPassword): bool {
    try {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = getDB()->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        $stmt->execute([$hash, $email]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('[AquaQueue] updatePassword: ' . $e->getMessage());
        return false;
    }
}
