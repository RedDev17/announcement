---
description: Restart ngrok tunnels (DB + files) and sync new URLs to Vercel + redeploy
---

This workflow restarts the ngrok DUAL tunnel (TCP for PostgreSQL + HTTP for Apache file serving), updates Vercel env vars, and triggers a redeploy. Run this after a laptop reboot or whenever the ngrok URLs change.

Prereqs that must already be running: XAMPP Apache (port 80) and PostgreSQL (port 5432). The ngrok config at `%LOCALAPPDATA%\ngrok\ngrok.yml` must define two named tunnels `db` (tcp:5432) and `files` (http:80).

## Steps

1. Stop any existing ngrok processes (requires admin if a zombie remains).
// turbo
```powershell
Get-Process ngrok -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2
```

2. Start ngrok with BOTH tunnels defined in config.
// turbo
```powershell
$ngrok = 'C:\Users\extre\Downloads\ngrok.exe'
Start-Process -FilePath $ngrok -ArgumentList 'start','--all' -WindowStyle Hidden
Start-Sleep -Seconds 8
```

3. Read both tunnel URLs from ngrok API.
// turbo
```powershell
$t = Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 10
foreach ($x in $t.tunnels) {
    if ($x.name -eq 'db' -and $x.public_url -match 'tcp://([^:]+):(\d+)') {
        $script:NGROK_HOST = $matches[1]
        $script:NGROK_PORT = $matches[2]
    } elseif ($x.name -eq 'files') {
        $script:FILES_URL = $x.public_url
    }
}
Write-Host "DB: $($script:NGROK_HOST):$($script:NGROK_PORT)"
Write-Host "FILES: $($script:FILES_URL)"
if (-not $script:NGROK_HOST -or -not $script:FILES_URL) { throw 'Missing tunnel URL(s)' }
```

4. Verify PostgreSQL is reachable through the tunnel.
// turbo
```powershell
$test = @"
<?php
try {
    `$pdo = new PDO('pgsql:host=$($script:NGROK_HOST);port=$($script:NGROK_PORT);dbname=announcement', 'postgres', 'Ellyred20', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
    `$count = `$pdo->query('SELECT COUNT(*) FROM "user"')->fetchColumn();
    echo "PASS: tunnel reachable, users=`$count" . PHP_EOL;
} catch (Throwable `$e) { echo "FAIL: " . `$e->getMessage() . PHP_EOL; exit(1); }
"@
$test | Out-File -Encoding ascii _t.php
php _t.php
Remove-Item _t.php
```

5. Update Vercel env vars (DB_HOST + DB_PORT + STORAGE_PUBLIC_URL) for production.
// turbo
```powershell
vercel env rm DB_HOST production --yes 2>&1 | Out-Null
vercel env rm DB_PORT production --yes 2>&1 | Out-Null
vercel env rm STORAGE_PUBLIC_URL production --yes 2>&1 | Out-Null
$script:NGROK_HOST | & vercel env add DB_HOST production 2>&1 | Out-Null
$script:NGROK_PORT | & vercel env add DB_PORT production 2>&1 | Out-Null
"$($script:FILES_URL)/annoucement" | & vercel env add STORAGE_PUBLIC_URL production 2>&1 | Out-Null
Write-Host "Updated Vercel env vars"
```

6. Trigger production redeploy on Vercel.
// turbo
```powershell
vercel --prod --yes
```

7. Smoke-test the deployed site and a file proxied through Vercel -> ngrok -> laptop.
// turbo
```powershell
$response = Invoke-WebRequest -Uri 'https://announcement-lac.vercel.app/' -UseBasicParsing -TimeoutSec 30
if ($response.Content -match 'Database connection error') {
    Write-Host 'FAIL: site still shows DB error' -ForegroundColor Red
} elseif ($response.StatusCode -eq 200) {
    Write-Host 'PASS: announcement-lac.vercel.app is live' -ForegroundColor Green
}
$img = Get-ChildItem 'C:\xampp\htdocs\annoucement\uploads\images' -File -ErrorAction SilentlyContinue | Where-Object { $_.Name -notmatch '^\.' } | Select-Object -First 1
if ($img) {
    $r = Invoke-WebRequest -Uri "https://announcement-lac.vercel.app/files.php?b=images&f=$($img.Name)" -UseBasicParsing -TimeoutSec 60
    if ($r.Content.Length -eq $img.Length) {
        Write-Host "PASS: file proxy works ($($img.Name) $($img.Length) bytes)" -ForegroundColor Green
    } else {
        Write-Host "FAIL: proxy size mismatch ($($r.Content.Length) vs $($img.Length))" -ForegroundColor Red
    }
}
```
