<?php
$baseHtml = file_get_contents(__DIR__ . '/full_index_542.html');
$m4Html = file_get_contents(__DIR__ . '/m4_extracted.html');
$m5Html = file_get_contents(__DIR__ . '/m5_extracted.html');

echo "baseHtml len: " . strlen($baseHtml) . "\n";
echo "m4Html len: " . strlen($m4Html) . "\n";
echo "m5Html len: " . strlen($m5Html) . "\n";

/*
Structure of baseHtml in 542:
- Has top bar buttons (admin-tab-dashboard, admin-tab-m2_org, admin-tab-users, admin-tab-audit, admin-tab-settings)
- Has admin-tab-dashboard
- Has admin-tab-m2_org
- Has admin-tab-users
- Has admin-tab-audit
- Has admin-tab-settings
*/

// 1. First replace admin-tab-bar in baseHtml with the 5 Core Module buttons
$oldTabBar = preg_match('/<div class="admin-tab-bar">.*?<\/div>/s', $baseHtml, $m) ? $m[0] : '';
$newTabBar = '<div class="admin-tab-bar">
        <button class="admin-tab-btn active" data-admin-tab="m1_portal" onclick="AppEngine.switchAdminTab(\'m1_portal\')">🛡️ M1 - Portal Admin</button>
        <button class="admin-tab-btn" data-admin-tab="m2_org" onclick="AppEngine.switchAdminTab(\'m2_org\')">🏛️ M2 - Manajemen Organisasi</button>
        <button class="admin-tab-btn" data-admin-tab="m3_membership" onclick="AppEngine.switchAdminTab(\'m3_membership\')">👥 M3 - Manajemen Keanggotaan</button>
        <button class="admin-tab-btn" data-admin-tab="m4_registration" onclick="AppEngine.switchAdminTab(\'m4_registration\')">📝 M4 - Pendaftaran Klub</button>
        <button class="admin-tab-btn" data-admin-tab="m5_forum" onclick="AppEngine.switchAdminTab(\'m5_forum\')">💬 M5 - Forum & Interaksi</button>
      </div>';

$baseHtml = str_replace($oldTabBar, $newTabBar, $baseHtml);

// 2. Wrap M1 (dashboard, users, audit, settings) inside #admin-tab-m1_portal
$dashStart = strpos($baseHtml, '<div id="admin-tab-dashboard" class="admin-tab-content">');
$m2Start   = strpos($baseHtml, '<div id="admin-tab-m2_org" class="admin-tab-content"');

$m1InnerContent = substr($baseHtml, $dashStart, $m2Start - $dashStart);

// Clean m1InnerContent tags
// Replace id="admin-tab-dashboard" with id="m1sub-dashboard" class="m1-subtab-content"
$m1InnerContent = str_replace('id="admin-tab-dashboard" class="admin-tab-content"', 'id="m1sub-dashboard" class="m1-subtab-content"', $m1InnerContent);
$m1InnerContent = str_replace('id="admin-tab-users" class="admin-tab-content" style="display:none;"', 'id="m1sub-users" class="m1-subtab-content" style="display:none;"', $m1InnerContent);
$m1InnerContent = str_replace('id="admin-tab-audit" class="admin-tab-content" style="display:none;"', 'id="m1sub-audit" class="m1-subtab-content" style="display:none;"', $m1InnerContent);
$m1InnerContent = str_replace('id="admin-tab-settings" class="admin-tab-content" style="display:none;"', 'id="m1sub-settings" class="m1-subtab-content" style="display:none;"', $m1InnerContent);

$m1ModuleBlock = '
      <!-- TAB M1: MODUL M1 - PORTAL ADMIN -->
      <div id="admin-tab-m1_portal" class="admin-tab-content">
        <div class="glass-panel" style="padding:24px; margin-bottom:24px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
            <div>
              <h3 style="font-size:1.3rem;" class="text-gradient">🛡️ MODUL M1 - PORTAL ADMIN & OPERASIONAL</h3>
              <p style="font-size:0.85rem; color:var(--text-muted);">Pusat kendali dashboard real-time, manajemen user (RBAC), audit logs, dan pengaturan sistem MB INA</p>
            </div>
            <div style="display:flex; gap:10px;">
              <button class="btn-outline" style="padding:8px 16px; font-size:0.85rem;" onclick="AppEngine.simulateExport(\'excel\')">📊 Export Excel</button>
              <button class="btn-outline" style="padding:8px 16px; font-size:0.85rem;" onclick="AppEngine.simulateExport(\'pdf\')">📄 Export PDF</button>
              <button class="btn-primary" style="padding:8px 16px; font-size:0.85rem;" data-open-modal="modal-add-user">+ Tambah User Baru</button>
            </div>
          </div>

          <!-- M1 Sub-Tab Navigation Pills -->
          <div style="display:flex; gap:8px; flex-wrap:wrap; border-bottom:1px solid var(--chrome-border); padding-bottom:16px; margin-bottom:24px;">
            <button class="sub-tab-btn active" data-m1sub="dashboard" onclick="AppEngine.switchM1Subtab(\'dashboard\')">📊 1.1 Dashboard</button>
            <button class="sub-tab-btn" data-m1sub="users" onclick="AppEngine.switchM1Subtab(\'users\')">👥 1.2 Manajemen User</button>
            <button class="sub-tab-btn" data-m1sub="audit" onclick="AppEngine.switchM1Subtab(\'audit\')">📜 1.3 Audit Log</button>
            <button class="sub-tab-btn" data-m1sub="settings" onclick="AppEngine.switchM1Subtab(\'settings\')">⚙️ 1.4 Pengaturan Sistem</button>
          </div>

          ' . trim($m1InnerContent) . '

        </div>
      </div>
';

// 3. Assemble all 5 module containers
$topPart = substr($baseHtml, 0, $dashStart);
$m2Block = substr($baseHtml, $m2Start);
// Find where view-landing-page starts
$landingStart = strpos($m2Block, '<!-- VIEW A: LANDING PAGE -->');

$m2Only = substr($m2Block, 0, $landingStart);
$bottomPart = substr($m2Block, $landingStart);

// Clean up m2Only closing divs
$m2Only = trim($m2Only);

// Clean up M4 HTML block
if (strpos($m4Html, 'id="admin-tab-m4_registration"') === false) {
    $m4Html = '
      <!-- TAB M4: MODUL M4 - PENDAFTARAN KLUB -->
      <div id="admin-tab-m4_registration" class="admin-tab-content" style="display:none;">
        ' . $m4Html . '
      </div>';
}

// Clean up M5 HTML block
if (strpos($m5Html, 'id="admin-tab-m5_forum"') === false) {
    $m5Html = '
      <!-- TAB M5: MODUL M5 - FORUM & INTERAKSI -->
      <div id="admin-tab-m5_forum" class="admin-tab-content" style="display:none;">
        ' . $m5Html . '
      </div>';
}

$fullFinalHtml = $topPart . "\n" . $m1ModuleBlock . "\n\n" . $m2Only . "\n\n" . $m4Html . "\n\n" . $m5Html . "\n\n" . $bottomPart;

file_put_contents(__DIR__ . '/../index.html', $fullFinalHtml);
echo "✔ Perfectly assembled index.html with all 5 independent module tabs!\n";
