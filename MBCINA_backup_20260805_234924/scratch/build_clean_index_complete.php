<?php
$baseHtml = file_get_contents(__DIR__ . '/full_index_542.html');
$m3Html   = file_get_contents(__DIR__ . '/m3_extracted.html');
$m4Html   = file_get_contents(__DIR__ . '/m4_extracted.html');
$m5Html   = file_get_contents(__DIR__ . '/m5_extracted.html');

// Extract M2 block from baseHtml
$m2Start = strpos($baseHtml, '<div id="admin-tab-m2_org" class="admin-tab-content"');
$usersStart = strpos($baseHtml, '<div id="admin-tab-users" class="admin-tab-content"');
$m2Block = substr($baseHtml, $m2Start, $usersStart - $m2Start);

// Extract Users block from baseHtml
$auditStart = strpos($baseHtml, '<div id="admin-tab-audit" class="admin-tab-content"');
$usersBlock = substr($baseHtml, $usersStart, $auditStart - $usersStart);

// Extract Audit block from baseHtml
$settingsStart = strpos($baseHtml, '<div id="admin-tab-settings" class="admin-tab-content"');
$auditBlock = substr($baseHtml, $auditStart, $settingsStart - $auditStart);

// Extract Settings block from baseHtml
$landingStart = strpos($baseHtml, '<!-- VIEW A: LANDING PAGE -->');
$settingsBlock = substr($baseHtml, $settingsStart, $landingStart - $settingsStart);
// Trim closing divs at end of settingsBlock
$settingsBlock = substr($settingsBlock, 0, strrpos($settingsBlock, '</div>'));
$settingsBlock = substr($settingsBlock, 0, strrpos($settingsBlock, '</div>'));

// Extract Dashboard block from baseHtml
$dashStart = strpos($baseHtml, '<div id="admin-tab-dashboard" class="admin-tab-content">');
$dashBlock = substr($baseHtml, $dashStart, $m2Start - $dashStart);

// Convert sub-blocks to subtabs
$m1Dash = str_replace('id="admin-tab-dashboard" class="admin-tab-content"', 'id="m1sub-dashboard" class="m1-subtab-content"', $dashBlock);
$m1Users = str_replace('id="admin-tab-users" class="admin-tab-content" style="display:none;"', 'id="m1sub-users" class="m1-subtab-content" style="display:none;"', $usersBlock);
$m1Audit = str_replace('id="admin-tab-audit" class="admin-tab-content" style="display:none;"', 'id="m1sub-audit" class="m1-subtab-content" style="display:none;"', $auditBlock);
$m1Settings = str_replace('id="admin-tab-settings" class="admin-tab-content" style="display:none;"', 'id="m1sub-settings" class="m1-subtab-content" style="display:none;"', $settingsBlock);

// Assemble M1 Module Block
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
            <button class="sub-tab-btn" data-m1sub="settings" onclick="AppEngine.switchSettingsSubtab(\'general\'); AppEngine.switchM1Subtab(\'settings\')">⚙️ 1.4 Pengaturan Sistem</button>
          </div>

          ' . trim($m1Dash) . '
          ' . trim($m1Users) . '
          ' . trim($m1Audit) . '
          ' . trim($m1Settings) . '

        </div>
      </div>';

// Top Part of index.html
$topPart = substr($baseHtml, 0, $dashStart);
// Replace admin-tab-bar in topPart
$oldBar = preg_match('/<div class="admin-tab-bar">.*?<\/div>/s', $topPart, $m) ? $m[0] : '';
$newBar = '<div class="admin-tab-bar">
        <button class="admin-tab-btn active" data-admin-tab="m1_portal" onclick="AppEngine.switchAdminTab(\'m1_portal\')">🛡️ M1 - Portal Admin</button>
        <button class="admin-tab-btn" data-admin-tab="m2_org" onclick="AppEngine.switchAdminTab(\'m2_org\')">🏛️ M2 - Manajemen Organisasi</button>
        <button class="admin-tab-btn" data-admin-tab="m3_membership" onclick="AppEngine.switchAdminTab(\'m3_membership\')">👥 M3 - Manajemen Keanggotaan</button>
        <button class="admin-tab-btn" data-admin-tab="m4_registration" onclick="AppEngine.switchAdminTab(\'m4_registration\')">📝 M4 - Pendaftaran Klub</button>
        <button class="admin-tab-btn" data-admin-tab="m5_forum" onclick="AppEngine.switchAdminTab(\'m5_forum\')">💬 M5 - Forum & Interaksi</button>
      </div>';

$topPart = str_replace($oldBar, $newBar, $topPart);

// Ensure M3 block has outer container
if (strpos($m3Html, 'id="admin-tab-m3_membership"') === false) {
    $m3Html = '
      <!-- TAB M3: MODUL M3 - MANAJEMEN KEANGGOTAAN -->
      <div id="admin-tab-m3_membership" class="admin-tab-content" style="display:none;">
        ' . trim($m3Html) . '
      </div>';
}

// Ensure M4 block has outer container
if (strpos($m4Html, 'id="admin-tab-m4_registration"') === false) {
    $m4Html = '
      <!-- TAB M4: MODUL M4 - PENDAFTARAN KLUB -->
      <div id="admin-tab-m4_registration" class="admin-tab-content" style="display:none;">
        ' . trim($m4Html) . '
      </div>';
}

// Ensure M5 block has outer container
if (strpos($m5Html, 'id="admin-tab-m5_forum"') === false) {
    $m5Html = '
      <!-- TAB M5: MODUL M5 - FORUM & INTERAKSI -->
      <div id="admin-tab-m5_forum" class="admin-tab-content" style="display:none;">
        ' . trim($m5Html) . '
      </div>';
}

$bottomPart = substr($baseHtml, $landingStart);

$finalHtml = $topPart . "\n" . $m1ModuleBlock . "\n\n" . trim($m2Block) . "\n\n" . trim($m3Html) . "\n\n" . trim($m4Html) . "\n\n" . trim($m5Html) . "\n\n    </div><!-- END view-admin-dashboard -->\n\n" . $bottomPart;

file_put_contents(__DIR__ . '/../index.html', $finalHtml);
echo "✔ COMPLETE RE-ASSEMBLY OF index.html FINISHED!\n";
