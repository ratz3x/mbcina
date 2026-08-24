<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$m4Html = file_get_contents(__DIR__ . '/html_edit_36.txt');
$m5Html = file_get_contents(__DIR__ . '/html_edit_41.txt');
$m5Modals = file_get_contents(__DIR__ . '/html_edit_42.txt');

echo "Original index.html len: " . strlen($html) . "\n";

// Replace content inside #admin-tab-m4_registration
$m4Start = strpos($html, '<div id="admin-tab-m4_registration" class="admin-tab-content"');
$m5Start = strpos($html, '<div id="admin-tab-m5_forum" class="admin-tab-content"');

$newM4Block = '
      <!-- TAB M4: MODUL M4 - PENDAFTARAN KLUB -->
      <div id="admin-tab-m4_registration" class="admin-tab-content" style="display:none;">
        <div class="glass-panel" style="padding:24px; margin-bottom:24px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
            <div>
              <h3 style="font-size:1.3rem;" class="text-gradient">📝 MODUL M4 - PENDAFTARAN KLUB BARU</h3>
              <p style="font-size:0.85rem; color:var(--text-muted);">Sistem pengelolaan pendaftaran klub baru, pengajuan formulir, verifikasi, approval, dan evaluasi kinerja klub</p>
            </div>
            <span class="tier-badge" style="background:rgba(245,158,11,0.2); color:var(--accent-gold); border:1px solid var(--accent-gold);">REGISTRATION ENGINE ACTIVE</span>
          </div>

          <!-- M4 Sub-Tab Navigation Pills -->
          <div style="display:flex; gap:8px; flex-wrap:wrap; border-bottom:1px solid var(--chrome-border); padding-bottom:16px; margin-bottom:24px;">
            <button class="sub-tab-btn active" data-m4sub="form" onclick="AppEngine.switchM4Subtab(\'form\')">4.1 Formulir Pendaftaran</button>
            <button class="sub-tab-btn" data-m4sub="pending" onclick="AppEngine.switchM4Subtab(\'pending\')">4.2 & 4.3 Verifikasi Pending</button>
            <button class="sub-tab-btn" data-m4sub="evaluations" onclick="AppEngine.switchM4Subtab(\'evaluations\')">4.4 Evaluasi Kinerja & Rentang Waktu ⏱️</button>
          </div>

          <!-- 4.1 FORMULIR PENDAFTARAN KLUB BARU -->
          <div id="m4sub-form" class="m4-subtab-content">
            ' . trim($m4Html) . '
          </div>

          <!-- 4.2 & 4.3 VERIFIKASI PENDING & APPROVAL WORKFLOW -->
          <div id="m4sub-pending" class="m4-subtab-content" style="display:none;">
            <div id="m4-pending-applications-container"></div>
          </div>

          <!-- 4.4 EVALUASI KINERJA KLUB & RENTANG WAKTU PENILAIAN -->
          <div id="m4sub-evaluations" class="m4-subtab-content" style="display:none;">
            <div class="glass-panel" style="padding:20px; margin-bottom:20px; background:rgba(15,23,42,0.6);">
              <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                  <h4 style="font-size:1.05rem; color:var(--accent-gold);">⏱️ 4.4 Evaluasi Kinerja Klub & Rentang Waktu Penilaian</h4>
                  <p style="font-size:0.8rem; color:var(--text-muted);">Sistem penilaian keaktifan, touring, laporan keanggotaan, & transparansi penilaian periodik.</p>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                  <label style="font-size:0.85rem; color:var(--text-muted);">Rentang Waktu:</label>
                  <select id="m4-evaluation-period-filter" class="form-input" style="width:180px;" onchange="AppEngine.renderM4Evaluations()">
                    <option value="6_MONTHS">Semester (6 Bulan)</option>
                    <option value="1_YEAR" selected>Tahunan (1 Tahun)</option>
                    <option value="ALL_TIME">Semua Periode</option>
                  </select>
                  <button class="btn-primary" style="padding:8px 16px; font-size:0.85rem;" onclick="AppEngine.openM4CreateEvaluationModal()">+ Buat Evaluasi Klub</button>
                </div>
              </div>
            </div>

            <div id="m4-evaluations-grid-container" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px;"></div>
          </div>

        </div>
      </div>
';

// Replace M5 Block
$m5BlockClean = '
      <!-- TAB M5: MODUL M5 - FORUM & INTERAKSI -->
      <div id="admin-tab-m5_forum" class="admin-tab-content" style="display:none;">
        ' . trim($m5Html) . '
      </div>';

$endDash = strpos($html, '</div><!-- END view-admin-dashboard -->');
$topHeaderPart = substr($html, 0, $m4Start);
$bottomLandingPart = substr($html, $endDash);

// Clean up m5Modals insertion
$beforeBodyEnd = strpos($bottomLandingPart, '<!-- GLOBAL GLASS LOADING SPINNER OVERLAY -->');
$bodyTopPart = substr($bottomLandingPart, 0, $beforeBodyEnd);
$bodyBottomPart = substr($bottomLandingPart, $beforeBodyEnd);

$finalCleanHtml = $topHeaderPart . "\n" . $newM4Block . "\n\n" . $m5BlockClean . "\n\n    </div><!-- END view-admin-dashboard -->\n\n" . $bodyTopPart . "\n\n" . $m5Modals . "\n\n" . $bodyBottomPart;

file_put_contents(__DIR__ . '/../index.html', $finalCleanHtml);
echo "✔ Successfully updated index.html with ALL M4 & M5 features and Modals!\n";
