<?php
// storage.php - Supabase Storage helper using REST API

class SupabaseStorage
{
    private $url;
    private $key;

    public function __construct()
    {
        $this->url = rtrim($this->env('SUPABASE_URL'), '/');
        $this->key = $this->env('SUPABASE_SERVICE_KEY');
    }

    private function env($name)
    {
        return getenv($name) ?: ($_ENV[$name] ?? ($_SERVER[$name] ?? ''));
    }

    /**
     * Upload a file from local path to a Supabase bucket.
     * Returns true on success.
     */
    public function upload($bucket, $filename, $localPath, $mimeType)
    {
        if (empty($this->url) || empty($this->key)) {
            error_log('Supabase Storage not configured');
            return false;
        }

        $endpoint = $this->url . '/storage/v1/object/' . $bucket . '/' . rawurlencode($filename);
        $fileContent = file_get_contents($localPath);
        if ($fileContent === false) return false;

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->key,
            'Content-Type: ' . $mimeType,
            'x-upsert: true',
            'Cache-Control: 3600',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) return true;
        error_log("Supabase upload failed [$httpCode]: $response");
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

        return $httpCode >= 200 && $httpCode < 300 || $httpCode === 404;
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
