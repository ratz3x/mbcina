<?php
$html = file_get_contents(__DIR__ . '/../index.html');

// Cari posisi </main> untuk insert modal sebelumnya
$insertBefore = '</main>';
$pos = strrpos($html, $insertBefore);
if ($pos === false) {
    echo "ERROR: </main> tidak ditemukan!\n";
    exit;
}

// Juga ambil data provinsi dan klub untuk dropdown
$clubs_js = '';

$modals = <<<'MODALS'

  <!-- ============================================================ -->
  <!-- MODAL M3: DETAIL PROFIL MEMBER -->
  <!-- ============================================================ -->
  <div id="modal-m3-member-detail" class="modal-overlay" style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); align-items:center; justify-content:center;">
    <div class="modal-box glass-panel" style="width:min(780px,95vw); max-height:90vh; overflow-y:auto; padding:0; border-radius:20px; border:1px solid var(--chrome-border); position:relative;">
      <div style="padding:20px 24px; border-bottom:1px solid var(--chrome-border); display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3);">
        <h3 class="text-gradient" style="font-size:1.1rem;">👁️ Detail Profil Member MB INA</h3>
        <button onclick="AuthEngine.closeAllModals()" style="background:none; border:none; color:var(--text-muted); font-size:1.4rem; cursor:pointer; line-height:1;">✕</button>
      </div>
      <div id="m3-detail-content-body" style="padding:24px;">
        <div style="text-align:center; padding:40px; color:var(--accent-gold);">⏳ Memuat...</div>
      </div>
    </div>
  </div>

  <!-- ============================================================ -->
  <!-- MODAL M3: EDIT DATA MEMBER -->
  <!-- ============================================================ -->
  <div id="modal-m3-edit-member" class="modal-overlay" style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); align-items:center; justify-content:center;">
    <div class="modal-box glass-panel" style="width:min(640px,95vw); max-height:90vh; overflow-y:auto; padding:0; border-radius:20px; border:1px solid var(--chrome-border); position:relative;">
      <div style="padding:20px 24px; border-bottom:1px solid var(--chrome-border); display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3);">
        <h3 class="text-gradient" style="font-size:1.1rem;">✏️ Edit Data Member</h3>
        <button onclick="AuthEngine.closeAllModals()" style="background:none; border:none; color:var(--text-muted); font-size:1.4rem; cursor:pointer; line-height:1;">✕</button>
      </div>
      <form onsubmit="AppEngine.saveM3MemberFromModal(event)" style="padding:24px;">
        <input type="hidden" id="m3-edit-id">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div>
            <label class="form-label">Member ID</label>
            <input id="m3-edit-member-id" class="form-input" readonly style="opacity:0.6; font-family:monospace; font-weight:700; color:var(--accent-gold);">
          </div>
          <div>
            <label class="form-label">Status</label>
            <select id="m3-edit-status" class="form-input">
              <option value="ACTIVE">ACTIVE</option>
              <option value="PENDING">PENDING</option>
              <option value="SUSPENDED">SUSPENDED</option>
              <option value="REJECTED">REJECTED</option>
            </select>
          </div>
        </div>
        <div style="margin-bottom:14px;">
          <label class="form-label">Nama Lengkap *</label>
          <input id="m3-edit-name" class="form-input" required placeholder="Nama lengkap member">
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:14px;">
          <div>
            <label class="form-label">Email *</label>
            <input id="m3-edit-email" class="form-input" type="email" required>
          </div>
          <div>
            <label class="form-label">No. WhatsApp *</label>
            <input id="m3-edit-phone" class="form-input" required>
          </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:14px;">
          <div>
            <label class="form-label">Kota</label>
            <input id="m3-edit-city" class="form-input" placeholder="Kota domisili">
          </div>
          <div>
            <label class="form-label">Provinsi</label>
            <input id="m3-edit-province" class="form-input" placeholder="Kode provinsi">
          </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:14px;">
          <div>
            <label class="form-label">Tier Keanggotaan</label>
            <select id="m3-edit-tier" class="form-input">
              <option value="BRONZE">🥉 BRONZE</option>
              <option value="SILVER">🥈 SILVER</option>
              <option value="GOLD">🥇 GOLD</option>
              <option value="PLATINUM">💎 PLATINUM</option>
            </select>
          </div>
          <div>
            <label class="form-label">Klub / Chapter</label>
            <input id="m3-edit-club" class="form-input" placeholder="Nama klub">
          </div>
        </div>
        <div style="margin-bottom:20px;">
          <label class="form-label">Catatan Admin</label>
          <textarea id="m3-edit-notes" class="form-input" rows="2" placeholder="Catatan internal admin..."></textarea>
        </div>
        <div style="display:flex; gap:12px; justify-content:flex-end;">
          <button type="button" onclick="AuthEngine.closeAllModals()" class="btn-outline" style="padding:10px 20px;">Batal</button>
          <button type="submit" class="btn-primary" style="padding:10px 24px;">💾 Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ============================================================ -->
  <!-- MODAL M3: CATAT DONASI & UPGRADE TIER -->
  <!-- ============================================================ -->
  <div id="modal-m3-add-donation" class="modal-overlay" style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); align-items:center; justify-content:center;">
    <div class="modal-box glass-panel" style="width:min(500px,95vw); padding:0; border-radius:20px; border:1px solid var(--chrome-border); position:relative;">
      <div style="padding:20px 24px; border-bottom:1px solid var(--chrome-border); display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3);">
        <h3 class="text-gradient" style="font-size:1.1rem;">💰 Catat Donasi & Upgrade Tier</h3>
        <button onclick="AuthEngine.closeAllModals()" style="background:none; border:none; color:var(--text-muted); font-size:1.4rem; cursor:pointer; line-height:1;">✕</button>
      </div>
      <form onsubmit="AppEngine.saveM3Donation(event)" style="padding:24px;">
        <input type="hidden" id="m3-don-userid">
        <div style="background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); border-radius:10px; padding:14px; margin-bottom:20px;">
          <div id="m3-don-member-name" style="font-weight:800; font-size:1rem; color:var(--accent-gold); margin-bottom:4px;"></div>
          <div id="m3-don-member-id" style="font-size:0.78rem; color:var(--text-muted); font-family:monospace;"></div>
        </div>
        <div style="margin-bottom:16px;">
          <label class="form-label">Jumlah Donasi (Rp) *</label>
          <input id="m3-don-amount" class="form-input" type="number" min="0" required placeholder="Contoh: 4500000">
        </div>
        <div style="margin-bottom:16px;">
          <label class="form-label">Metode Pembayaran</label>
          <select id="m3-don-method" class="form-input">
            <option value="TRANSFER_BANK">Transfer Bank</option>
            <option value="CASH">Cash / Tunai</option>
            <option value="QRIS">QRIS</option>
            <option value="VIRTUAL_ACCOUNT">Virtual Account</option>
          </select>
        </div>
        <div style="margin-bottom:20px;">
          <label class="form-label">Keterangan / Catatan</label>
          <textarea id="m3-don-notes" class="form-input" rows="2" placeholder="Contoh: Donasi Jamnas 2026..."></textarea>
        </div>
        <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); border-radius:8px; padding:10px 14px; font-size:0.8rem; color:var(--text-muted); margin-bottom:18px;">
          💡 Tier akan diupgrade otomatis: BRONZE (0–1,4jt) → SILVER (1,5–4,4jt) → GOLD (4,5–8,9jt) → PLATINUM (≥9jt)
        </div>
        <div style="display:flex; gap:12px; justify-content:flex-end;">
          <button type="button" onclick="AuthEngine.closeAllModals()" class="btn-outline" style="padding:10px 20px;">Batal</button>
          <button type="submit" class="btn-primary" style="padding:10px 24px;">💾 Simpan Donasi</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ============================================================ -->
  <!-- MODAL M3: VERIFIKASI MEMBER (APPROVE / REJECT) -->
  <!-- ============================================================ -->
  <div id="modal-m3-verify" class="modal-overlay" style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); align-items:center; justify-content:center;">
    <div class="modal-box glass-panel" style="width:min(520px,95vw); padding:0; border-radius:20px; border:1px solid var(--chrome-border); position:relative;">
      <div style="padding:20px 24px; border-bottom:1px solid var(--chrome-border); display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3);">
        <h3 class="text-gradient" style="font-size:1.1rem;" id="m3-verify-modal-title">✅ Verifikasi Keanggotaan</h3>
        <button onclick="AuthEngine.closeAllModals()" style="background:none; border:none; color:var(--text-muted); font-size:1.4rem; cursor:pointer; line-height:1;">✕</button>
      </div>
      <div style="padding:24px;">
        <input type="hidden" id="m3-verify-userid">
        <input type="hidden" id="m3-verify-action">
        <div id="m3-verify-member-info" style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:10px; padding:14px; margin-bottom:20px;"></div>
        <div style="margin-bottom:16px;">
          <label class="form-label">Tindakan</label>
          <select id="m3-verify-type" class="form-input" onchange="document.getElementById('m3-verify-action').value=this.value">
            <option value="APPROVE">✅ Setujui (APPROVE) — Aktifkan sebagai MEMBER</option>
            <option value="REJECT">❌ Tolak (REJECT) — Kembalikan sebagai CALON_MEMBER</option>
          </select>
        </div>
        <div style="margin-bottom:20px;">
          <label class="form-label">Catatan Admin</label>
          <textarea id="m3-verify-notes" class="form-input" rows="3" placeholder="Alasan persetujuan / penolakan..."></textarea>
        </div>
        <div style="display:flex; gap:12px; justify-content:flex-end;">
          <button type="button" onclick="AuthEngine.closeAllModals()" class="btn-outline" style="padding:10px 20px;">Batal</button>
          <button type="button" onclick="AppEngine.processM3Verification()" class="btn-primary" style="padding:10px 24px;">Proses Verifikasi</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ============================================================ -->
  <!-- MODAL M3: EXPORT DATA KEANGGOTAAN -->
  <!-- ============================================================ -->
  <div id="modal-m3-export" class="modal-overlay" style="display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,0.75); backdrop-filter:blur(6px); align-items:center; justify-content:center;">
    <div class="modal-box glass-panel" style="width:min(460px,95vw); padding:0; border-radius:20px; border:1px solid var(--chrome-border); position:relative;">
      <div style="padding:20px 24px; border-bottom:1px solid var(--chrome-border); display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.3);">
        <h3 class="text-gradient" style="font-size:1.1rem;">📤 Export Data Keanggotaan</h3>
        <button onclick="AuthEngine.closeAllModals()" style="background:none; border:none; color:var(--text-muted); font-size:1.4rem; cursor:pointer; line-height:1;">✕</button>
      </div>
      <div style="padding:24px;">
        <div style="margin-bottom:16px;">
          <label class="form-label">Format Export</label>
          <select id="m3-export-format" class="form-input">
            <option value="excel">📊 Microsoft Excel (.xlsx)</option>
            <option value="pdf">📄 PDF Report</option>
            <option value="csv">📋 CSV (Raw Data)</option>
          </select>
        </div>
        <div style="margin-bottom:16px;">
          <label class="form-label">Filter Tier</label>
          <select id="m3-export-tier" class="form-input">
            <option value="">Semua Tier</option>
            <option value="PLATINUM">💎 PLATINUM</option>
            <option value="GOLD">🥇 GOLD</option>
            <option value="SILVER">🥈 SILVER</option>
            <option value="BRONZE">🥉 BRONZE</option>
          </select>
        </div>
        <div style="margin-bottom:20px;">
          <label class="form-label">Filter Status</label>
          <select id="m3-export-status" class="form-input">
            <option value="">Semua Status</option>
            <option value="ACTIVE">ACTIVE</option>
            <option value="PENDING">PENDING</option>
            <option value="SUSPENDED">SUSPENDED</option>
          </select>
        </div>
        <div style="display:flex; gap:12px; justify-content:flex-end;">
          <button type="button" onclick="AuthEngine.closeAllModals()" class="btn-outline" style="padding:10px 20px;">Batal</button>
          <button type="button" class="btn-primary" style="padding:10px 24px;" onclick="alert('🚧 Fitur export sedang dalam pengembangan.')">📥 Download</button>
        </div>
      </div>
    </div>
  </div>

MODALS;

// Insert sebelum </main>
$newHtml = substr($html, 0, $pos) . $modals . substr($html, $pos);
file_put_contents(__DIR__ . '/../index.html', $newHtml);

echo "✅ 5 modal M3 berhasil ditambahkan ke index.html!\n";
echo "Total karakter: " . strlen($newHtml) . "\n";
