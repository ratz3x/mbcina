<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();
if (!$pdo) { echo "GAGAL konek!\n"; exit; }

echo "=== AUDIT DATA M2 DI SUPABASE ===\n\n";

// 1. org_profile
echo "--- 1. org_profile (Profil Organisasi) ---\n";
try {
    $rows = $pdo->query("SELECT id, full_name, short_name, established_date, headquarters_city, email, website, total_clubs, total_members, total_provinces, about_text FROM org_profile")->fetchAll();
    if (count($rows) === 0) echo "  ⚠️  KOSONG — belum ada data!\n";
    else foreach ($rows as $r) {
        echo "  ✅ [{$r['id']}] {$r['full_name']}\n";
        echo "     Berdiri: {$r['established_date']} | HQ: {$r['headquarters_city']}\n";
        echo "     Email: {$r['email']} | Web: {$r['website']}\n";
        echo "     Total Klub: {$r['total_clubs']} | Member: {$r['total_members']} | Provinsi: {$r['total_provinces']}\n";
        echo "     About: " . (strlen($r['about_text']) > 80 ? substr($r['about_text'],0,80).'...' : $r['about_text']) . "\n";
    }
} catch(Exception $e) { echo "  ❌ ERROR: " . $e->getMessage() . "\n"; }

// 2. founders
echo "\n--- 2. founders (Pendiri Organisasi) ---\n";
try {
    $rows = $pdo->query("SELECT id, name, role, year FROM founders ORDER BY year ASC")->fetchAll();
    if (count($rows) === 0) echo "  ⚠️  KOSONG!\n";
    else foreach ($rows as $r) echo "  ✅ [{$r['id']}] {$r['name']} — {$r['role']} ({$r['year']})\n";
} catch(Exception $e) { echo "  ❌ ERROR: " . $e->getMessage() . "\n"; }

// 3. vision_mission
echo "\n--- 3. vision_mission (Visi & Misi) ---\n";
try {
    $rows = $pdo->query("SELECT id, type, title, content, sort_order FROM vision_mission ORDER BY sort_order ASC")->fetchAll();
    if (count($rows) === 0) echo "  ⚠️  KOSONG!\n";
    else foreach ($rows as $r) {
        $preview = strlen($r['content']) > 80 ? substr($r['content'],0,80).'...' : $r['content'];
        echo "  ✅ [{$r['id']}] [{$r['type']}] {$r['title']}: {$preview}\n";
    }
} catch(Exception $e) { echo "  ❌ ERROR: " . $e->getMessage() . "\n"; }

// 4. presidents
echo "\n--- 4. presidents (Daftar Presiden) ---\n";
try {
    $rows = $pdo->query("SELECT id, name, period_start, period_end, is_current FROM presidents ORDER BY period_start ASC")->fetchAll();
    if (count($rows) === 0) echo "  ⚠️  KOSONG!\n";
    else foreach ($rows as $r) {
        $current = $r['is_current'] ? ' 👑 AKTIF' : '';
        echo "  ✅ [{$r['id']}] {$r['name']} ({$r['period_start']} – {$r['period_end']}{$current})\n";
    }
} catch(Exception $e) { echo "  ❌ ERROR: " . $e->getMessage() . "\n"; }

// 5. org_structure
echo "\n--- 5. org_structure (Pengurus Pusat) ---\n";
try {
    $rows = $pdo->query("SELECT id, jabatan_nama, jabatan_level, periode_mulai, periode_selesai, is_active FROM org_structure ORDER BY urutan ASC")->fetchAll();
    if (count($rows) === 0) echo "  ⚠️  KOSONG!\n";
    else foreach ($rows as $r) {
        $active = $r['is_active'] ? '✅' : '❌';
        echo "  {$active} [{$r['id']}] {$r['jabatan_nama']} | Level: {$r['jabatan_level']} | Periode: {$r['periode_mulai']} – {$r['periode_selesai']}\n";
    }
} catch(Exception $e) { echo "  ❌ ERROR: " . $e->getMessage() . "\n"; }

// 6. clubs (sample)
echo "\n--- 6. clubs (Klub/Chapter) — sample 5 ---\n";
try {
    $total = $pdo->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
    $rows = $pdo->query("SELECT id, code, name, city, province_id, member_count FROM clubs ORDER BY id ASC LIMIT 5")->fetchAll();
    echo "  Total: {$total} klub terdaftar\n";
    foreach ($rows as $r) echo "  ✅ [{$r['code']}] {$r['name']} | {$r['city']} | Members: {$r['member_count']}\n";
    if ($total > 5) echo "  ... dan " . ($total - 5) . " klub lainnya\n";
} catch(Exception $e) { echo "  ❌ ERROR: " . $e->getMessage() . "\n"; }

// 7. Cek field yang dipakai app.js di M2
echo "\n--- 7. Cek field M2 yang dipakai JS (fetchM2Data) ---\n";
$js = file_get_contents(__DIR__ . '/../js/app.js');
$lines = explode("\n", $js);
foreach ($lines as $i => $line) {
    if (strpos($line, 'fetchM2Data') !== false || strpos($line, 'get_m2_data') !== false || strpos($line, 'm2Data') !== false && strpos($line, '=') !== false && strpos($line, 'res.') !== false) {
        echo "  Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}

echo "\n=== SELESAI ===\n";
