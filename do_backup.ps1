$dt = Get-Date -Format "yyyyMMdd_HHmmss"
$zip = "MBCINA_FULL_BACKUP_$dt.zip"
$dir = "backups\MBCINA_BACKUP_$dt"

Write-Host "Creating backup directory $dir..."
New-Item -ItemType Directory -Force -Path $dir | Out-Null

Write-Host "Copying files..."
Copy-Item -Path index.html, api.php, favicon.ico, manifest.json, vercel.json, *.sql, *.php, js, css, assets, uploads, images -Destination $dir -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "Compressing archive $zip..."
Compress-Archive -Path "$dir\*" -DestinationPath $zip -Force

Write-Host "FULL BACKUP SUCCESSFUL:"
Get-Item $zip | Select-Object Name, Length, LastWriteTime
