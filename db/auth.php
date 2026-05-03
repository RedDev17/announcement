<?php
// auth.php - Cookie-based authentication for serverless (Vercel)
// Uses HMAC-signed tokens instead of PHP sessions

define('AUTH_SECRET', getenv('AUTH_SECRET') ?: 'annoucement-board-secret-key-change-me');
define('AUTH_COOKIE', 'admin_token');
define('AUTH_EXPIRY', 86400); // 24 hours

/**
 * Create a signed auth token for a user.
 */
function createAuthToken($username, $email, $accessLevel)
{
    $payload = json_encode([
        'username' => $username,
        'email' => $email,
        'access_level' => $accessLevel,
        'exp' => time() + AUTH_EXPIRY,
    ]);
    $signature = hash_hmac('sha256', $payload, AUTH_SECRET);
    return base64_encode($payload) . '.' . $signature;
}

/**
 * Verify and decode an auth token. Returns user array or null.
 */
function verifyAuthToken($token)
{
    $parts = explode('.', $token);
    if (count($parts) !== 2) return null;

    $payload = base64_decode($parts[0]);
    $signature = $parts[1];

    if (hash_hmac('sha256', $payload, AUTH_SECRET) !== $signature) return null;

    $data = json_decode($payload, true);
    if (!$data || !isset($data['exp']) || $data['exp'] < time()) return null;

    return $data;
}

/**
 * Set the auth cookie after successful login.
 */
function loginUser($username, $email, $accessLevel)
{
    $token = createAuthToken($username, $email, $accessLevel);
    setcookie(AUTH_COOKIE, $token, [
        'expires' => time() + AUTH_EXPIRY,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Get current logged-in user or null.
 */
function getLoggedInUser()
{
    if (!isset($_COOKIE[AUTH_COOKIE])) return null;
    return verifyAuthToken($_COOKIE[AUTH_COOKIE]);
}

/**
 * Require admin login, redirect to admin.php if not.
 */
function requireAdmin()
{
    $user = getLoggedInUser();
    if (!$user || $user['access_level'] !== 'admin') {
        header("Location: admin.php");
        exit();
    }
    return $user;
}

/**
 * Logout: clear the auth cookie.
 */
function logoutUser()
{
    setcookie(AUTH_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
?>
