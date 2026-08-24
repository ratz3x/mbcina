<?php
$html = file_get_contents(__DIR__ . '/../index.html');

// Ganti class modal-overlay → modal-backdrop KHUSUS untuk modal M3
$m3Modals = [
    'modal-m3-member-detail',
    'modal-m3-edit-member',
    'modal-m3-add-donation',
    'modal-m3-verify',
    'modal-m3-export',
];

$changed = 0;
foreach ($m3Modals as $modalId) {
    // Cari div dengan id modal ini dan ganti class-nya
    $pattern = '/<div id="' . preg_quote($modalId, '/') . '" class="modal-overlay"/';
    $replacement = '<div id="' . $modalId . '" class="modal-backdrop"';
    $new = preg_replace($pattern, $replacement, $html, 1, $count);
    if ($count > 0) {
        $html = $new;
        echo "✅ $modalId — class diubah ke modal-backdrop\n";
        $changed++;
    } else {
        echo "⚠️  $modalId — pola tidak ditemukan, coba pattern lain...\n";
        // Try flexible match
        $pattern2 = '/id="' . preg_quote($modalId, '/') . '" class="modal-overlay"/';
        $replacement2 = 'id="' . $modalId . '" class="modal-backdrop"';
        $new2 = preg_replace($pattern2, $replacement2, $html, 1, $count2);
        if ($count2 > 0) {
            $html = $new2;
            echo "  → ✅ Berhasil dengan pattern alternatif\n";
            $changed++;
        } else {
            echo "  → ❌ Gagal!\n";
        }
    }
}

file_put_contents(__DIR__ . '/../index.html', $html);
echo "\nTotal diubah: $changed modal\n";
echo "File size: " . strlen($html) . " bytes\n";
