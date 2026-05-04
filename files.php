<?php
// files.php - Proxies file requests from the Vercel deployment to the local
// Apache server via the ngrok HTTP tunnel. This is required because ngrok's
// free tier shows a browser-warning interstitial on <img>/<object> requests,
// which we must bypass with the `ngrok-skip-browser-warning` header.
//
// On localhost (XAMPP) this file is never used; storage.php returns a direct
// /uploads/... URL. On Vercel STORAGE_PUBLIC_URL is set, so storage.php
// returns `/files.php?b=...&f=...` and this script streams the file through.

function env_val($name, $default = '')
{
    $v = getenv($name);
    if ($v !== false && $v !== '') return $v;
    if (isset($_ENV[$name]) && $_ENV[$name] !== '') return $_ENV[$name];
    if (isset($_SERVER[$name]) && $_SERVER[$name] !== '') return $_SERVER[$name];
    return $default;
}

$allowedBuckets = ['images', 'modules'];
$bucket = $_GET['b'] ?? '';
$name   = $_GET['f'] ?? '';

if (!in_array($bucket, $allowedBuckets, true)) {
    http_response_code(400);
    exit('invalid bucket');
}
// Only allow safe filename characters to prevent path traversal
if ($name === '' || !preg_match('/^[A-Za-z0-9._\- ]+$/', $name)) {
    http_response_code(400);
    exit('invalid filename');
}

$base = rtrim(env_val('STORAGE_PUBLIC_URL', ''), '/');
if ($base === '') {
    http_response_code(500);
    exit('STORAGE_PUBLIC_URL not configured');
}

$upstream = $base . '/uploads/' . rawurlencode($bucket) . '/' . rawurlencode($name);

// Fetch upstream with the skip-warning header
$ch = curl_init($upstream);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'ngrok-skip-browser-warning: 1',
    'User-Agent: Vercel-FileProxy/1.0',
]);
curl_setopt($ch, CURLOPT_HEADER, false);

$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$err = curl_error($ch);
curl_close($ch);

if ($body === false) {
    http_response_code(502);
    header('Content-Type: text/plain');
    exit('upstream error: ' . $err);
}

if ($code < 200 || $code >= 400) {
    http_response_code($code ?: 502);
    header('Content-Type: text/plain');
    exit('upstream HTTP ' . $code);
}

// Fallback content-type guess from extension if upstream didn't send one
if (!$ctype || stripos($ctype, 'text/html') !== false) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $map = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif',  'webp' => 'image/webp', 'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
    ];
    $ctype = $map[$ext] ?? 'application/octet-stream';
}

header('Content-Type: ' . $ctype);
header('Cache-Control: public, max-age=300');
header('Content-Length: ' . strlen($body));
echo $body;
