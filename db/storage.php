<?php
// storage.php - File storage. Default driver is 'local' (filesystem).
// Optional 'supabase' driver kept for users who want it; configure via STORAGE_DRIVER in .env.

class FileStorage
{
    private $driver;
    private $url;
    private $key;
    private $localRoot;
    private $lastError = '';

    public function __construct()
    {
        $this->driver = strtolower($this->env('STORAGE_DRIVER', 'local'));
        $this->url = rtrim($this->env('SUPABASE_URL', ''), '/');
        $this->key = $this->env('SUPABASE_SERVICE_KEY', '');
        // realpath returns false if dir doesn't exist; fall back to relative path resolution
        $root = realpath(__DIR__ . '/..');
        if ($root === false) $root = __DIR__ . '/..';
        $this->localRoot = $root . DIRECTORY_SEPARATOR . 'uploads';
    }

    private function env($name, $default = '')
    {
        return getenv($name) ?: ($_ENV[$name] ?? ($_SERVER[$name] ?? $default));
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Detect the project base URL (e.g. /annoucement on XAMPP, '' on Vercel).
     */
    private function projectBaseUrl()
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        // /annoucement/index.php -> /annoucement
        // /annoucement/admin/dashboard.php -> /annoucement
        // /index.php -> ''
        $parts = explode('/', trim($script, '/'));
        if (count($parts) <= 1) return '';
        return '/' . $parts[0];
    }

    private function localBucketDir($bucket)
    {
        $safeBucket = preg_replace('/[^A-Za-z0-9_-]/', '_', $bucket);
        return $this->localRoot . DIRECTORY_SEPARATOR . $safeBucket;
    }

    /**
     * Upload a file from local path to a bucket.
     * Returns true on success.
     */
    public function upload($bucket, $filename, $localPath, $mimeType)
    {
        $this->lastError = '';

        if ($this->driver === 'local') {
            return $this->uploadLocal($bucket, $filename, $localPath);
        }
        return $this->uploadSupabase($bucket, $filename, $localPath, $mimeType);
    }

    private function uploadLocal($bucket, $filename, $localPath)
    {
        $dir = $this->localBucketDir($bucket);
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            $this->lastError = 'Could not create upload directory: ' . $dir;
            error_log($this->lastError);
            return false;
        }
        $safeName = basename($filename);
        $target = $dir . DIRECTORY_SEPARATOR . $safeName;
        // Prefer move_uploaded_file if it's a temp upload, fallback to copy
        if (is_uploaded_file($localPath)) {
            if (!move_uploaded_file($localPath, $target)) {
                $this->lastError = 'move_uploaded_file failed for: ' . $target;
                error_log($this->lastError);
                return false;
            }
        } else {
            if (!copy($localPath, $target)) {
                $this->lastError = 'copy failed for: ' . $target;
                error_log($this->lastError);
                return false;
            }
        }
        return true;
    }

    private function uploadSupabase($bucket, $filename, $localPath, $mimeType)
    {
        if (empty($this->url) || empty($this->key)) {
            $this->lastError = 'Supabase Storage not configured (URL or KEY missing)';
            error_log($this->lastError);
            return false;
        }

        $fileContent = file_get_contents($localPath);
        if ($fileContent === false) {
            $this->lastError = 'Could not read temp file: ' . $localPath;
            error_log($this->lastError);
            return false;
        }

        $endpoint = $this->url . '/storage/v1/object/' . $bucket . '/' . rawurlencode($filename);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->key,
            'Content-Type: ' . $mimeType,
            'x-upsert: true',
            'Cache-Control: max-age=3600',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->lastError = 'cURL error: ' . $curlError;
            error_log($this->lastError);
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) return true;

        $this->lastError = "Supabase upload failed [HTTP $httpCode]: $response";
        error_log($this->lastError);
        return false;
    }

    /**
     * Delete a file from a bucket. Returns true on success or if it didn't exist.
     */
    public function delete($bucket, $filename)
    {
        if ($this->driver === 'local') {
            $path = $this->localBucketDir($bucket) . DIRECTORY_SEPARATOR . basename($filename);
            if (!is_file($path)) return true;
            return @unlink($path);
        }

        if (empty($this->url) || empty($this->key)) return false;

        $endpoint = $this->url . '/storage/v1/object/' . $bucket . '/' . rawurlencode($filename);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->key,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode >= 200 && $httpCode < 300) || $httpCode === 404;
    }

    /**
     * Get the public URL for a file in a public bucket.
     *
     * - On localhost (XAMPP): returns a direct /uploads/... URL.
     * - On Vercel (STORAGE_PUBLIC_URL is set): returns a /files.php proxy URL
     *   because ngrok's free tier shows a browser-warning interstitial on
     *   direct <img>/<object> requests. files.php streams the file through
     *   with the required skip-warning header.
     */
    public function publicUrl($bucket, $filename)
    {
        if ($this->driver === 'local') {
            $override = rtrim($this->env('STORAGE_PUBLIC_URL', ''), '/');
            if ($override !== '') {
                return '/files.php?b=' . rawurlencode($bucket)
                     . '&f=' . rawurlencode($filename);
            }
            $base = $this->projectBaseUrl();
            return $base . '/uploads/' . $bucket . '/' . rawurlencode($filename);
        }
        if (empty($this->url)) return '';
        return $this->url . '/storage/v1/object/public/' . $bucket . '/' . rawurlencode($filename);
    }
}

function getStorage()
{
    static $instance = null;
    if ($instance === null) {
        $instance = new FileStorage();
    }
    return $instance;
}

// Backward-compatible alias for older code that called supabaseStorage()
if (!function_exists('supabaseStorage')) {
    function supabaseStorage()
    {
        return getStorage();
    }
}
?>
