<?php
// storage.php - Supabase Storage helper using REST API

class SupabaseStorage
{
    private $url;
    private $key;
    private $lastError = '';

    public function __construct()
    {
        $this->url = rtrim($this->env('SUPABASE_URL'), '/');
        $this->key = $this->env('SUPABASE_SERVICE_KEY');
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
     * Upload a file from local path to a Supabase bucket.
     * Returns true on success.
     */
    public function upload($bucket, $filename, $localPath, $mimeType)
    {
        $this->lastError = '';

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
     * Delete a file from a Supabase bucket. Returns true on success or if it didn't exist.
     */
    public function delete($bucket, $filename)
    {
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
     */
    public function publicUrl($bucket, $filename)
    {
        if (empty($this->url)) return '';
        return $this->url . '/storage/v1/object/public/' . $bucket . '/' . rawurlencode($filename);
    }
}

function supabaseStorage()
{
    static $instance = null;
    if ($instance === null) {
        $instance = new SupabaseStorage();
    }
    return $instance;
}
?>
