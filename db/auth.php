<?php
// auth.php - Cookie-based authentication for serverless (Vercel)
// Uses HMAC-signed tokens instead of PHP sessions

function _authEnv($name)
{
    return getenv($name) ?: ($_ENV[$name] ?? ($_SERVER[$name] ?? ''));
}

$_auth_secret = _authEnv('AUTH_SECRET');
// Fallback: derive a stable secret from DB_PASS so users don't need a second env var.
// Still secure because DB_PASS is not in source code.
if (empty($_auth_secret)) {
    $dbPass = _authEnv('DB_PASS');
    if (!empty($dbPass)) {
        $_auth_secret = hash('sha256', 'auth:' . $dbPass);
    }
}
if (empty($_auth_secret)) {
    error_log('No AUTH_SECRET or DB_PASS env var available to derive auth secret');
    http_response_code(500);
    die('Server configuration error.');
}
define('AUTH_SECRET', $_auth_secret);
define('AUTH_COOKIE', 'admin_token');
define('AUTH_EXPIRY', 86400); // 24 hours

// Detect HTTPS so the Secure cookie flag is set on production but not localhost
function authIsHttps()
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
    if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
    return false;
}

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
        'secure' => authIsHttps(),
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
        'secure' => authIsHttps(),
        'samesite' => 'Lax',
    ]);
}
?>
