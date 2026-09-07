$dt = Get-Date -Format "yyyyMMdd_HHmmss"
$zip = "MBCINA_FULL_BACKUP_$dt.zip"
$dir = "backups\MBCINA_BACKUP_$dt"

Write-Host "Creating backup directory $dir..."
New-Item -ItemType Directory -Force -Path $dir | Out-Null
New-Item -ItemType Directory -Force -Path "$dir\database" | Out-Null

Write-Host "Copying project files..."
Copy-Item -Path index.html, api.php, favicon.ico, manifest.json, vercel.json, *.sql, *.php, js, css, assets, uploads, images -Destination $dir -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "Copying latest database dumps..."
Copy-Item -Path "backups\backup_mbina_supabase_2026-09-07_19-34-56.*" -Destination "$dir\database" -Force -ErrorAction SilentlyContinue

Write-Host "Compressing archive $zip..."
Compress-Archive -Path "$dir\*" -DestinationPath $zip -Force

Write-Host "Copying archive to backups folder..."
Copy-Item -Path $zip -Destination "backups\$zip" -Force

Write-Host "Cleaning up staging directory..."
Remove-Item -Path $dir -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "FULL BACKUP SUCCESSFUL:"
Get-Item $zip | Select-Object Name, Length, LastWriteTime

