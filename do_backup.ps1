$dt = Get-Date -Format "yyyyMMdd_HHmmss"
$zip = "MBCINA_FULL_BACKUP_$dt.zip"
$dir = "backups\MBCINA_BACKUP_$dt"

Write-Host "Creating backup directory $dir..."
New-Item -ItemType Directory -Force -Path $dir | Out-Null
New-Item -ItemType Directory -Force -Path "$dir\database" | Out-Null

Write-Host "Copying project files..."
Copy-Item -Path index.html, api.php, favicon.ico, manifest.json, vercel.json, package.json, package-lock.json, *.sql, *.php, js, css, api, prisma, assets, uploads, images -Destination $dir -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "Copying latest database dumps..."
$latestSql = Get-ChildItem "backups\backup_mbina_supabase_*.sql" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
$latestJson = Get-ChildItem "backups\backup_mbina_supabase_*.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if ($latestSql) { Copy-Item -Path $latestSql.FullName -Destination "$dir\database" -Force }
if ($latestJson) { Copy-Item -Path $latestJson.FullName -Destination "$dir\database" -Force }

Write-Host "Compressing archive $zip..."
Compress-Archive -Path "$dir\*" -DestinationPath $zip -Force

Write-Host "Copying archive to backups folder..."
Copy-Item -Path $zip -Destination "backups\$zip" -Force

Write-Host "Creating uncompressed snapshot in htdocs..."
$snapshotDir = "..\MBCINA_BACKUP_SNAPSHOT_$dt"
New-Item -ItemType Directory -Force -Path $snapshotDir | Out-Null
Copy-Item -Path "$dir\*" -Destination $snapshotDir -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "Cleaning up staging directory..."
Remove-Item -Path $dir -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "FULL BACKUP SUCCESSFUL:"
Get-Item $zip | Select-Object Name, Length, LastWriteTime
Get-Item "backups\$zip" | Select-Object Name, Length, LastWriteTime
Get-Item $snapshotDir | Select-Object Name, FullName, LastWriteTime

