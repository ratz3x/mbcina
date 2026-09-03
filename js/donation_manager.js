/**
 * ============================================================================
 * MB CLUB INDONESIA - STANDALONE DONATION & PHILANTHROPY ENGINE (V2 REBUILD)
 * ============================================================================
 * Fitur Utama:
 * 1. Monitoring & Progress Program Donasi (KPI & Progress Bar).
 * 2. Daftar Donatur & Verifikasi:
 *    - PENGURUS/ADMIN WAJIB MELIHAT BUKTI TRANSFER sebelum menyetujui.
 *    - Modal verifikasi menampilkan bukti transfer secara jelas + tombol zoom.
 *    - Approval menambahkan total donasi donatur dan MENAIKKAN TIER OTOMATIS:
 *      * BRONZE   : Rp 0 - Rp 1.499.999
 *      * SILVER   : Rp 1.500.000 - Rp 4.499.999
 *      * GOLD     : Rp 4.500.000 - Rp 8.999.999
 *      * PLATINUM : >= Rp 9.000.000
 *    - Penolakan donasi (REJECTED) hanya menampilkan tombol "Lihat Bukti" tanpa centang.
 * 3. Log Digital Receipt resmi dengan cetak (print) & unduh PDF.
 * 4. Matriks Tier Anggota & Evaluasi Otomatis.
 * 5. Buat Program Donasi Baru.
 * 6. Bebas dari freeze/hang, tidak mengunci scrollbar body.
 * ============================================================================
 */

(function(window, document) {
  'use strict';

  // ─── TIER CALCULATION SPECIFICATION ─────────────────────────────────────────
  const TIER_THRESHOLDS = [
    { tier: 'PLATINUM', min: 9000000, icon: '💎', color: '#38bdf8', bg: 'rgba(56,189,248,0.15)', border: 'rgba(56,189,248,0.3)' },
    { tier: 'GOLD',     min: 4500000, icon: '🥇', color: '#f59e0b', bg: 'rgba(245,158,11,0.15)', border: 'rgba(245,158,11,0.3)' },
    { tier: 'SILVER',   min: 1500000, icon: '🥈', color: '#94a3b8', bg: 'rgba(148,163,184,0.15)', border: 'rgba(148,163,184,0.3)' },
    { tier: 'BRONZE',   min: 0,       icon: '🥉', color: '#d97706', bg: 'rgba(217,119,6,0.15)', border: 'rgba(217,119,6,0.3)' }
  ];

  function calcTier(amount) {
    const val = Math.max(0, Number(amount) || 0);
    for (let i = 0; i < TIER_THRESHOLDS.length; i++) {
      if (val >= TIER_THRESHOLDS[i].min) return TIER_THRESHOLDS[i];
    }
    return TIER_THRESHOLDS[TIER_THRESHOLDS.length - 1];
  }

  // ─── DEFAULT DATA SEED ──────────────────────────────────────────────────────
  const defaultCampaigns = [
    {
      id: 'camp_yogya_2026',
      title: 'Donasi Bakti Sosial Yogyakarta 2026',
      description: 'Bantu kami berbagi kebahagiaan dengan masyarakat dan panti asuhan Yogyakarta dalam rangka Jamnas & Touring MB INA 2026.',
      target_amount: 20000000,
      collected_amount: 14500000,
      start_date: '2026-09-01',
      end_date: '2026-09-14',
      is_active: true
    },
    {
      id: 'camp_bencana_2026',
      title: 'Peduli Bencana & Kemanusiaan Nasional',
      description: 'Program tanggap darurat dan santunan korban bencana alam di berbagai wilayah nusantara bersama MB Club Indonesia.',
      target_amount: 50000000,
      collected_amount: 27500000,
      start_date: '2026-08-01',
      end_date: '2026-12-31',
      is_active: true
    }
  ];

  const defaultDonations = [
    {
      id: 'DON-TRX-2026-001',
      trx_code: 'DON-TRX-2026-001',
      campaign_id: 'camp_yogya_2026',
      donor_name: 'Andi Pratama',
      member_id: 'MBINA-JKT-2026-000005',
      user_id: 'usr_m3_001',
      amount: 2000000,
      payment_method: 'TRANSFER',
      payment_proof_url: 'assets/mb_hero.jpg',
      status: 'PENDING',
      notes: 'Donasi paket sembako baksos Yogyakarta',
      created_at: '2026-09-02 10:15'
    },
    {
      id: 'DON-TRX-2026-002',
      trx_code: 'DON-TRX-2026-002',
      campaign_id: 'camp_yogya_2026',
      donor_name: 'Budi Santoso',
      member_id: 'MBINA-BDG-2026-000006',
      user_id: 'usr_m3_002',
      amount: 1500000,
      payment_method: 'TRANSFER',
      payment_proof_url: 'assets/mb_hero.jpg',
      status: 'PENDING',
      notes: 'Partisipasi santunan anak yatim',
      created_at: '2026-09-02 11:30'
    },
    {
      id: 'DON-TRX-2026-003',
      trx_code: 'DON-TRX-2026-003',
      campaign_id: 'camp_yogya_2026',
      donor_name: 'Derist Touriano',
      member_id: 'MBINA-HQ-2026-000001',
      user_id: 'usr_superadmin',
      amount: 5000000,
      payment_method: 'TRANSFER',
      payment_proof_url: 'assets/mb_hero.jpg',
      status: 'SUCCESS',
      notes: 'Baksos Jamnas MB INA 2026',
      created_at: '2026-08-28 09:00'
    },
    {
      id: 'DON-TRX-2026-004',
      trx_code: 'DON-TRX-2026-004',
      campaign_id: 'camp_yogya_2026',
      donor_name: 'Siti Rahayu',
      member_id: 'MBINA-SBY-2026-000007',
      user_id: 'usr_m3_003',
      amount: 4500000,
      payment_method: 'QRIS',
      payment_proof_url: 'assets/mb_hero.jpg',
      status: 'SUCCESS',
      notes: 'Donasi pendidikan anak asuh',
      created_at: '2026-08-25 14:20'
    },
    {
      id: 'DON-TRX-2026-005',
      trx_code: 'DON-TRX-2026-005',
      campaign_id: 'camp_yogya_2026',
      donor_name: 'Denny Kurniawan',
      member_id: 'MBINA-JKT-2026-000009',
      user_id: 'usr_m3_005',
      amount: 500000,
      payment_method: 'TRANSFER',
      payment_proof_url: 'assets/mb_hero.jpg',
      status: 'REJECTED',
      notes: 'Bukti transfer buram/tidak terbaca',
      created_at: '2026-08-20 16:45'
    }
  ];

  const defaultReceipts = [
    {
      id: 'rec_001',
      receipt_number: 'REC-2026-0001',
      donation_id: 'DON-TRX-2026-003',
      donor_name: 'Derist Touriano',
      member_id: 'MBINA-HQ-2026-000001',
      amount: 5000000,
      campaign_title: 'Donasi Bakti Sosial Yogyakarta 2026',
      payment_method: 'TRANSFER',
      created_at: '2026-08-28 09:05'
    },
    {
      id: 'rec_002',
      receipt_number: 'REC-2026-0002',
      donation_id: 'DON-TRX-2026-004',
      donor_name: 'Siti Rahayu',
      member_id: 'MBINA-SBY-2026-000007',
      amount: 4500000,
      campaign_title: 'Donasi Bakti Sosial Yogyakarta 2026',
      payment_method: 'QRIS',
      created_at: '2026-08-25 14:25'
    }
  ];

  // ─── CORE DONATION MANAGER ─────────────────────────────────────────────────
  const DonationManager = {
    activeSubtab: 'monitoring',
    filterCampaign: 'ALL',
    filterStatus: 'ALL',
    searchQuery: '',
    selectedDonationId: null,
    selectedReceiptId: null,

    data: {
      campaigns: [],
      donations: [],
      receipts: []
    },

    // ─── INITIALIZATION ──────────────────────────────────────────────────────
    init() {
      console.log('[DonationManager] Initializing clean V2 module...');
      this.loadStoredData();
      this.fetchLiveData();
      this.injectContainerStyles();
      this.renderModuleUI();
      this.syncAllMemberTiers();
    },

    loadStoredData() {
      try {
        const stored = localStorage.getItem('mbcina_v2_donations');
        if (stored) {
          const parsed = JSON.parse(stored);
          this.data.campaigns = (parsed.campaigns && parsed.campaigns.length) ? parsed.campaigns : defaultCampaigns;
          this.data.donations = (parsed.donations && parsed.donations.length) ? parsed.donations : defaultDonations;
          this.data.receipts  = (parsed.receipts && parsed.receipts.length)   ? parsed.receipts  : defaultReceipts;
        } else {
          this.data.campaigns = defaultCampaigns;
          this.data.donations = defaultDonations;
          this.data.receipts  = defaultReceipts;
        }
      } catch (e) {
        this.data.campaigns = defaultCampaigns;
        this.data.donations = defaultDonations;
        this.data.receipts  = defaultReceipts;
      }
    },

    saveStoredData() {
      try {
        localStorage.setItem('mbcina_v2_donations', JSON.stringify(this.data));
      } catch (e) {
        console.warn('[DonationManager] Failed to save to localStorage:', e);
      }
      // Sinkronkan ke AppEngine & M6Engine jika ada
      if (window.AppEngine) window.AppEngine.donationData = this.data;
      if (window.M6Engine)  window.M6Engine.donationData  = this.data;
    },

    async fetchLiveData() {
      try {
        const res = await fetch('api.php?action=get_donation_campaigns').then(r => r.json());
        if (res && res.success) {
          if (Array.isArray(res.campaigns) && res.campaigns.length) this.data.campaigns = res.campaigns;
          if (Array.isArray(res.donations) && res.donations.length) this.data.donations = res.donations;
          if (Array.isArray(res.receipts) && res.receipts.length)   this.data.receipts  = res.receipts;
          this.saveStoredData();
          this.renderActiveSubtab();
        }
      } catch (e) {
        // Fallback local ok
      }
    },

    // ─── CSS INJECTION (ISOLATED, NO CONFLICTS) ──────────────────────────────
    injectContainerStyles() {
      if (document.getElementById('don-v2-isolated-styles')) return;
      const st = document.createElement('style');
      st.id = 'don-v2-isolated-styles';
      st.innerHTML = `
        .don-v2-container {
          color: #f1f5f9;
          font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .don-v2-nav-tabs {
          display: flex;
          gap: 8px;
          margin-bottom: 24px;
          border-bottom: 1px solid rgba(255,255,255,0.08);
          padding-bottom: 12px;
          overflow-x: auto;
          scrollbar-width: none;
        }
        .don-v2-tab-btn {
          background: rgba(255,255,255,0.03);
          color: #94a3b8;
          border: 1px solid rgba(255,255,255,0.08);
          padding: 10px 18px;
          border-radius: 12px;
          font-size: 0.85rem;
          font-weight: 600;
          cursor: pointer;
          display: inline-flex;
          align-items: center;
          gap: 8px;
          transition: all 0.2s ease;
          white-space: nowrap;
        }
        .don-v2-tab-btn:hover {
          background: rgba(255,255,255,0.08);
          color: #fff;
        }
        .don-v2-tab-btn.active {
          background: linear-gradient(135deg, rgba(245,158,11,0.2), rgba(217,119,6,0.1));
          color: #fbbf24;
          border-color: rgba(245,158,11,0.4);
          box-shadow: 0 4px 15px rgba(245,158,11,0.15);
        }
        .don-kpi-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
          gap: 16px;
          margin-bottom: 24px;
        }
        .don-kpi-card {
          background: rgba(15,23,42,0.65);
          border: 1px solid rgba(255,255,255,0.08);
          backdrop-filter: blur(10px);
          border-radius: 16px;
          padding: 18px;
          display: flex;
          align-items: center;
          gap: 14px;
        }
        .don-kpi-icon {
          width: 48px;
          height: 48px;
          border-radius: 14px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 1.3rem;
        }
        .don-action-btn {
          border: none;
          padding: 6px 14px;
          border-radius: 8px;
          font-size: 0.78rem;
          font-weight: 700;
          cursor: pointer;
          display: inline-flex;
          align-items: center;
          gap: 6px;
          transition: all 0.15s ease;
        }
        .don-action-btn:hover {
          transform: translateY(-1px);
        }
        /* ISOLATED MODAL (NEVER HANGS, NEVER LOCKS SCROLL) */
        .don-modal-root {
          position: fixed;
          inset: 0;
          z-index: 100000;
          background: rgba(4, 7, 13, 0.88);
          backdrop-filter: blur(12px);
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 16px;
          overflow-y: auto;
        }
        .don-modal-box {
          background: #0b111e;
          border: 1px solid rgba(255,255,255,0.12);
          border-radius: 20px;
          width: 100%;
          max-width: 640px;
          box-shadow: 0 25px 60px rgba(0,0,0,0.85);
          overflow: hidden;
          animation: donModalPop 0.2s ease-out;
        }
        @keyframes donModalPop {
          from { opacity: 0; transform: scale(0.95); }
          to { opacity: 1; transform: scale(1); }
        }
      `;
      document.head.appendChild(st);
    },

    // ─── RENDER MAIN SHELL ───────────────────────────────────────────────────
    renderModuleUI() {
      const container = document.getElementById('admin-tab-m7_donation');
      if (!container) {
        console.error('[DonationManager] Target #admin-tab-m7_donation not found!');
        return;
      }

      container.innerHTML = `
        <div class="don-v2-container" style="padding: 10px 4px 30px;">
          <!-- HEADER -->
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; margin-bottom:20px;">
            <div>
              <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:1.6rem;">❤️</span>
                <h2 style="margin:0; font-size:1.4rem; font-weight:800; background:linear-gradient(135deg,#fff,#f59e0b); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">
                  Manajemen Donasi & Filantropi
                </h2>
                <span style="font-size:0.7rem; background:rgba(245,158,11,0.15); color:#fbbf24; border:1px solid rgba(245,158,11,0.3); padding:3px 8px; border-radius:9999px; font-weight:700;">
                  OFFICIAL ENGINE V2
                </span>
              </div>
              <p style="margin:6px 0 0; font-size:0.82rem; color:#94a3b8;">
                Verifikasi bukti transfer donatur, otomatisasi kenaikan Tier Keanggotaan, penerbitan Digital Receipt resmi, dan monitoring program sosial MB INA.
              </p>
            </div>
            <div style="display:flex; gap:8px;">
              <button type="button" class="don-action-btn" style="background:rgba(255,255,255,0.06); color:#cbd5e1; border:1px solid rgba(255,255,255,0.1);" onclick="window.DonationManager.syncAllMemberTiers(true)">
                <span>🔄 Sinkronisasi Tier</span>
              </button>
            </div>
          </div>

          <!-- SUBTABS BAR -->
          <div class="don-v2-nav-tabs">
            <button class="don-v2-tab-btn ${this.activeSubtab === 'monitoring' ? 'active' : ''}" onclick="window.DonationManager.switchTab('monitoring')">
              <span>📊 Progress &amp; Monitoring Donasi</span>
            </button>
            <button class="don-v2-tab-btn ${this.activeSubtab === 'campaign_form' ? 'active' : ''}" onclick="window.DonationManager.switchTab('campaign_form')">
              <span>➕ Buat Program Donasi Baru</span>
            </button>
            <button class="don-v2-tab-btn ${this.activeSubtab === 'receipts' ? 'active' : ''}" onclick="window.DonationManager.switchTab('receipts')">
              <span>🧾 Log Digital Receipt (${this.data.receipts.length})</span>
            </button>
            <button class="don-v2-tab-btn ${this.activeSubtab === 'tier_matrix' ? 'active' : ''}" onclick="window.DonationManager.switchTab('tier_matrix')">
              <span>🏅 Matriks Tier &amp; Status Anggota</span>
            </button>
          </div>

          <!-- TAB CONTENT CONTAINER -->
          <div id="don-v2-tab-body"></div>
        </div>

        <!-- ISOLATED MODALS MOUNT POINT -->
        <div id="don-v2-modal-mount"></div>
      `;

      this.renderActiveSubtab();
    },

    switchTab(tabKey) {
      this.activeSubtab = tabKey;
      document.querySelectorAll('.don-v2-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('onclick').includes(`'${tabKey}'`));
      });
      this.renderActiveSubtab();
    },

    renderActiveSubtab() {
      const body = document.getElementById('don-v2-tab-body');
      if (!body) return;

      if (this.activeSubtab === 'monitoring') {
        this.renderMonitoringTab(body);
      } else if (this.activeSubtab === 'campaign_form') {
        this.renderCampaignFormTab(body);
      } else if (this.activeSubtab === 'receipts') {
        this.renderReceiptsLogTab(body);
      } else if (this.activeSubtab === 'tier_matrix') {
        this.renderTierMatrixTab(body);
      }
    },

    // ─── TAB 1: MONITORING & DAFTAR DONATUR ───────────────────────────────────
    renderMonitoringTab(container) {
      // Hitung KPI
      let totalCollected = 0;
      let pendingCount = 0;
      let successCount = 0;
      const donorSet = new Set();

      this.data.donations.forEach(d => {
        if (d.status === 'SUCCESS' || d.status === 'CONFIRMED') {
          totalCollected += Number(d.amount || 0);
          successCount++;
          if (d.member_id || d.donor_name) donorSet.add(d.member_id || d.donor_name);
        } else if (d.status === 'PENDING') {
          pendingCount++;
        }
      });

      // Filtered Donors List
      let filtered = [...this.data.donations];
      if (this.filterCampaign !== 'ALL') {
        filtered = filtered.filter(d => d.campaign_id === this.filterCampaign);
      }
      if (this.filterStatus !== 'ALL') {
        filtered = filtered.filter(d => d.status === this.filterStatus);
      }
      if (this.searchQuery.trim()) {
        const q = this.searchQuery.toLowerCase();
        filtered = filtered.filter(d =>
          (d.donor_name || '').toLowerCase().includes(q) ||
          (d.member_id || '').toLowerCase().includes(q) ||
          (d.trx_code || d.id || '').toLowerCase().includes(q)
        );
      }

      container.innerHTML = `
        <!-- KPI METRICS -->
        <div class="don-kpi-grid">
          <div class="don-kpi-card">
            <div class="don-kpi-icon" style="background:rgba(16,185,129,0.15); color:#34d399;">💰</div>
            <div>
              <div style="font-size:0.75rem; color:#94a3b8; font-weight:600; text-transform:uppercase;">Dana Terkumpul</div>
              <div style="font-size:1.3rem; font-weight:800; color:#34d399; font-family:monospace;">Rp ${totalCollected.toLocaleString('id-ID')}</div>
            </div>
          </div>
          <div class="don-kpi-card">
            <div class="don-kpi-icon" style="background:rgba(245,158,11,0.15); color:#fbbf24;">⏳</div>
            <div>
              <div style="font-size:0.75rem; color:#94a3b8; font-weight:600; text-transform:uppercase;">Menunggu Verifikasi</div>
              <div style="font-size:1.3rem; font-weight:800; color:#fbbf24; font-family:monospace;">${pendingCount} Transaksi</div>
            </div>
          </div>
          <div class="don-kpi-card">
            <div class="don-kpi-icon" style="background:rgba(56,189,248,0.15); color:#38bdf8;">👥</div>
            <div>
              <div style="font-size:0.75rem; color:#94a3b8; font-weight:600; text-transform:uppercase;">Total Donatur</div>
              <div style="font-size:1.3rem; font-weight:800; color:#ffffff; font-family:monospace;">${donorSet.size} Anggota</div>
            </div>
          </div>
          <div class="don-kpi-card">
            <div class="don-kpi-icon" style="background:rgba(168,85,247,0.15); color:#c084fc;">🎯</div>
            <div>
              <div style="font-size:0.75rem; color:#94a3b8; font-weight:600; text-transform:uppercase;">Program Aktif</div>
              <div style="font-size:1.3rem; font-weight:800; color:#ffffff; font-family:monospace;">${this.data.campaigns.filter(c => c.is_active !== false).length} Campaign</div>
            </div>
          </div>
        </div>

        <!-- CAMPAIGN CARDS -->
        <div style="margin-bottom:28px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h4 style="margin:0; font-size:1rem; color:#fbbf24; display:flex; align-items:center; gap:8px;">
              <span>🎯</span> PROGRAM DONASI AKTIF
            </h4>
            <button type="button" class="don-action-btn" style="background:rgba(245,158,11,0.15); color:#fbbf24; border:1px solid rgba(245,158,11,0.3);" onclick="window.DonationManager.switchTab('campaign_form')">
              <span>+ Buat Program Baru</span>
            </button>
          </div>
          <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:16px;">
            ${this.data.campaigns.map(c => {
              const col = Number(c.collected_amount || 0);
              const tar = Number(c.target_amount || 1);
              const pct = Math.min(100, Math.round((col / tar) * 100));
              return `
                <div style="background:rgba(15,23,42,0.7); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:18px; display:flex; flex-direction:column; justify-content:space-between;">
                  <div>
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px; margin-bottom:8px;">
                      <h4 style="margin:0; font-size:0.95rem; font-weight:700; color:#fff;">${c.title}</h4>
                      <span style="font-size:0.68rem; padding:2px 8px; border-radius:9999px; font-weight:700; background:rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.3);">AKTIF</span>
                    </div>
                    <p style="font-size:0.78rem; color:#94a3b8; margin:0 0 14px; line-height:1.5;">${c.description || '-'}</p>
                  </div>
                  <div>
                    <div style="display:flex; justify-content:space-between; font-size:0.78rem; margin-bottom:6px;">
                      <span style="color:#94a3b8;">Terkumpul: <strong style="color:#34d399;">Rp ${col.toLocaleString('id-ID')}</strong></span>
                      <span style="color:#cbd5e1;">Target: Rp ${tar.toLocaleString('id-ID')}</span>
                    </div>
                    <div style="height:8px; background:rgba(255,255,255,0.06); border-radius:9999px; overflow:hidden; margin-bottom:8px;">
                      <div style="height:100%; width:${pct}%; background:linear-gradient(90deg, #f59e0b, #10b981); border-radius:9999px;"></div>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.72rem; color:#94a3b8;">
                      <span>Progres Capaian: <strong style="color:#fbbf24;">${pct}%</strong></span>
                      <span>Periode: s/d ${c.end_date || '2026'}</span>
                    </div>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        </div>

        <!-- DONOR LIST & VERIFICATION TABLE -->
        <div style="background:rgba(15,23,42,0.7); border:1px solid rgba(255,255,255,0.08); border-radius:18px; padding:20px;">
          <!-- FILTER & SEARCH BAR -->
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
            <div>
              <h4 style="margin:0; font-size:1.05rem; font-weight:800; color:#fbbf24; display:flex; align-items:center; gap:8px;">
                <span>👥</span> DAFTAR TRANSAKSI &amp; VERIFIKASI DONATUR
              </h4>
              <p style="margin:3px 0 0; font-size:0.75rem; color:#94a3b8;">
                Klik tombol <strong>Periksa Bukti</strong> untuk meninjau bukti transfer sebelum menyetujui transaksi donasi.
              </p>
            </div>

            <!-- CONTROLS -->
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
              <!-- Search -->
              <input type="text" placeholder="🔍 Cari nama/ID..." value="${this.searchQuery}"
                oninput="window.DonationManager.handleSearch(this.value)"
                style="background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.12); color:#fff; padding:6px 12px; border-radius:8px; font-size:0.78rem; width:150px;">
              
              <!-- Filter Campaign -->
              <select onchange="window.DonationManager.handleCampaignFilter(this.value)"
                style="background:#0f172a; border:1px solid rgba(255,255,255,0.12); color:#fbbf24; padding:6px 10px; border-radius:8px; font-size:0.78rem; font-weight:600;">
                <option value="ALL" ${this.filterCampaign === 'ALL' ? 'selected' : ''}>Semua Program</option>
                ${this.data.campaigns.map(c => `<option value="${c.id}" ${this.filterCampaign === c.id ? 'selected' : ''}>${c.title}</option>`).join('')}
              </select>

              <!-- Filter Status -->
              <select onchange="window.DonationManager.handleStatusFilter(this.value)"
                style="background:#0f172a; border:1px solid rgba(255,255,255,0.12); color:#fff; padding:6px 10px; border-radius:8px; font-size:0.78rem;">
                <option value="ALL" ${this.filterStatus === 'ALL' ? 'selected' : ''}>Semua Status</option>
                <option value="PENDING" ${this.filterStatus === 'PENDING' ? 'selected' : ''}>⏳ Pending</option>
                <option value="SUCCESS" ${this.filterStatus === 'SUCCESS' ? 'selected' : ''}>✅ Success</option>
                <option value="REJECTED" ${this.filterStatus === 'REJECTED' ? 'selected' : ''}>❌ Rejected</option>
              </select>

              <!-- Export -->
              <button type="button" class="don-action-btn" style="background:rgba(255,255,255,0.06); color:#cbd5e1; border:1px solid rgba(255,255,255,0.1);" onclick="window.DonationManager.exportExcel()">
                <span>📤 Excel</span>
              </button>
              <button type="button" class="don-action-btn" style="background:rgba(255,255,255,0.06); color:#cbd5e1; border:1px solid rgba(255,255,255,0.1);" onclick="window.DonationManager.exportPdf()">
                <span>📄 PDF</span>
              </button>
            </div>
          </div>

          <!-- TABLE -->
          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.82rem; min-width:850px;">
              <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02); color:#94a3b8; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.04em;">
                  <th style="padding:12px 10px; text-align:center; width:40px;">No</th>
                  <th style="padding:12px 10px; text-align:left;">Kode Trx</th>
                  <th style="padding:12px 10px; text-align:left;">Donatur &amp; Member ID</th>
                  <th style="padding:12px 10px; text-align:left;">Program Donasi</th>
                  <th style="padding:12px 10px; text-align:right;">Nominal (Rp)</th>
                  <th style="padding:12px 10px; text-align:center;">Metode</th>
                  <th style="padding:12px 10px; text-align:center;">Status</th>
                  <th style="padding:12px 10px; text-align:center; width:170px;">Aksi Verifikasi</th>
                </tr>
              </thead>
              <tbody>
                ${filtered.length === 0 ? `
                  <tr>
                    <td colspan="8" style="text-align:center; padding:36px; color:#64748b;">
                      Tidak ada transaksi donasi yang sesuai kriteria pencarian.
                    </td>
                  </tr>
                ` : filtered.map((d, idx) => {
                  const camp = this.data.campaigns.find(c => c.id === d.campaign_id) || {};
                  const isPending = d.status === 'PENDING';
                  const isSuccess = d.status === 'SUCCESS' || d.status === 'CONFIRMED';
                  const isRejected = d.status === 'REJECTED';

                  // Status badge pill
                  let badgeHtml = '';
                  if (isSuccess) {
                    badgeHtml = `<span style="background:rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.3); font-size:0.72rem; padding:3px 10px; border-radius:9999px; font-weight:700;">✅ SUCCESS</span>`;
                  } else if (isRejected) {
                    badgeHtml = `<span style="background:rgba(244,63,94,0.15); color:#fb7185; border:1px solid rgba(244,63,94,0.3); font-size:0.72rem; padding:3px 10px; border-radius:9999px; font-weight:700;">❌ REJECTED</span>`;
                  } else {
                    badgeHtml = `<span style="background:rgba(245,158,11,0.15); color:#fbbf24; border:1px solid rgba(245,158,11,0.3); font-size:0.72rem; padding:3px 10px; border-radius:9999px; font-weight:700;">⏳ PENDING</span>`;
                  }

                  // Action Buttons:
                  // 1. PENDING: Wajib 'Periksa Bukti' (membuka modal verifikasi berisi foto bukti transfer)
                  // 2. SUCCESS: 'Bukti' dan 'Kwitansi'
                  // 3. REJECTED: Hanya 'Lihat Bukti' (tanpa opsi approve)
                  let actionHtml = '';
                  if (isPending) {
                    actionHtml = `
                      <button type="button" class="don-action-btn"
                        style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#000; font-weight:800; box-shadow:0 2px 8px rgba(245,158,11,0.25);"
                        onclick="window.DonationManager.openVerifyModal('${d.id}')"
                        title="Tinjau Bukti Transfer untuk verifikasi">
                        <span>🔍 Periksa Bukti</span>
                      </button>
                    `;
                  } else if (isSuccess) {
                    actionHtml = `
                      <div style="display:flex; gap:6px; justify-content:center;">
                        <button type="button" class="don-action-btn"
                          style="background:rgba(255,255,255,0.05); color:#cbd5e1; border:1px solid rgba(255,255,255,0.1);"
                          onclick="window.DonationManager.openVerifyModal('${d.id}')"
                          title="Lihat Bukti Transfer">
                          <span>👁️ Bukti</span>
                        </button>
                        <button type="button" class="don-action-btn"
                          style="background:rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.3);"
                          onclick="window.DonationManager.openReceiptModalByDonation('${d.id}')"
                          title="Buka Digital Receipt Resmi">
                          <span>🧾 Kwitansi</span>
                        </button>
                      </div>
                    `;
                  } else {
                    actionHtml = `
                      <button type="button" class="don-action-btn"
                        style="background:rgba(244,63,94,0.1); color:#fb7185; border:1px solid rgba(244,63,94,0.25);"
                        onclick="window.DonationManager.openVerifyModal('${d.id}')"
                        title="Lihat Bukti Transfer (Ditolak)">
                        <span>👁️ Lihat Bukti</span>
                      </button>
                    `;
                  }

                  return `
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.04); transition:background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                      <td style="padding:12px 10px; text-align:center; color:#64748b; font-family:monospace;">${idx + 1}</td>
                      <td style="padding:12px 10px; font-family:monospace; color:#94a3b8; font-size:0.75rem;">${d.trx_code || d.id}</td>
                      <td style="padding:12px 10px;">
                        <div style="font-weight:700; color:#fff;">${d.donor_name || 'Hamba Allah'}</div>
                        <div style="font-size:0.72rem; color:#fbbf24; font-family:monospace;">${d.member_id || 'Non-Member'}</div>
                      </td>
                      <td style="padding:12px 10px; color:#cbd5e1; font-size:0.78rem;">${camp.title || d.campaign_id}</td>
                      <td style="padding:12px 10px; text-align:right; font-family:monospace; font-weight:700; color:#34d399; font-size:0.88rem;">
                        Rp ${Number(d.amount || 0).toLocaleString('id-ID')}
                      </td>
                      <td style="padding:12px 10px; text-align:center; font-size:0.75rem; color:#38bdf8; font-weight:600;">${d.payment_method || 'TRANSFER'}</td>
                      <td style="padding:12px 10px; text-align:center;">${badgeHtml}</td>
                      <td style="padding:12px 10px; text-align:center;">${actionHtml}</td>
                    </tr>
                  `;
                }).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    },

    handleSearch(val) {
      this.searchQuery = val;
      const body = document.getElementById('don-v2-tab-body');
      if (body) this.renderMonitoringTab(body);
    },

    handleCampaignFilter(val) {
      this.filterCampaign = val;
      const body = document.getElementById('don-v2-tab-body');
      if (body) this.renderMonitoringTab(body);
    },

    handleStatusFilter(val) {
      this.filterStatus = val;
      const body = document.getElementById('don-v2-tab-body');
      if (body) this.renderMonitoringTab(body);
    },

    // ─── TAB 2: BUAT PROGRAM DONASI BARU ─────────────────────────────────────
    renderCampaignFormTab(container) {
      container.innerHTML = `
        <div style="max-width:720px; margin:0 auto; background:rgba(15,23,42,0.7); border:1px solid rgba(245,158,11,0.3); border-radius:20px; padding:28px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:12px;">
            <h3 style="margin:0; font-size:1.15rem; color:#fbbf24; display:flex; align-items:center; gap:8px;">
              <span>➕</span> BUAT PROGRAM DONASI BARU
            </h3>
            <button type="button" class="don-action-btn" style="background:rgba(255,255,255,0.06); color:#cbd5e1;" onclick="window.DonationManager.switchTab('monitoring')">✕ Batal</button>
          </div>

          <form id="don-create-campaign-form" onsubmit="window.DonationManager.handleCreateCampaign(event)">
            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:0.75rem; font-weight:700; color:#94a3b8; margin-bottom:6px; text-transform:uppercase;">Judul Program Donasi *</label>
              <input type="text" id="new-camp-title" required placeholder="Contoh: Donasi MB INA Peduli Gempa & Bencana 2026"
                style="width:100%; box-sizing:border-box; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:10px 14px; border-radius:10px; font-size:0.85rem;">
            </div>

            <div style="margin-bottom:16px;">
              <label style="display:block; font-size:0.75rem; font-weight:700; color:#94a3b8; margin-bottom:6px; text-transform:uppercase;">Deskripsi &amp; Tujuan Program *</label>
              <textarea id="new-camp-desc" rows="3" required placeholder="Jelaskan peruntukan donasi dan sasaran penerima manfaat secara transparan..."
                style="width:100%; box-sizing:border-box; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:10px 14px; border-radius:10px; font-size:0.85rem;"></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:18px;">
              <div>
                <label style="display:block; font-size:0.75rem; font-weight:700; color:#94a3b8; margin-bottom:6px; text-transform:uppercase;">Target Dana (Rp) *</label>
                <input type="number" id="new-camp-target" min="500000" step="500000" required value="25000000"
                  style="width:100%; box-sizing:border-box; background:rgba(0,0,0,0.4); border:1px solid rgba(245,158,11,0.4); color:#fbbf24; padding:10px 14px; border-radius:10px; font-size:0.9rem; font-weight:700; font-family:monospace;">
              </div>
              <div>
                <label style="display:block; font-size:0.75rem; font-weight:700; color:#94a3b8; margin-bottom:6px; text-transform:uppercase;">Tanggal Mulai *</label>
                <input type="date" id="new-camp-start" required value="${new Date().toISOString().slice(0,10)}"
                  style="width:100%; box-sizing:border-box; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:10px 14px; border-radius:10px; font-size:0.85rem;">
              </div>
              <div>
                <label style="display:block; font-size:0.75rem; font-weight:700; color:#94a3b8; margin-bottom:6px; text-transform:uppercase;">Tanggal Berakhir *</label>
                <input type="date" id="new-camp-end" required value="2026-12-31"
                  style="width:100%; box-sizing:border-box; background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:10px 14px; border-radius:10px; font-size:0.85rem;">
              </div>
            </div>

            <div style="margin-bottom:24px;">
              <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#34d399; font-size:0.85rem; font-weight:700;">
                <input type="checkbox" id="new-camp-active" checked style="width:18px; height:18px; accent-color:#10b981;">
                <span>Publikasikan &amp; Aktifkan Segera untuk Menerima Donasi</span>
              </label>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
              <button type="button" class="don-action-btn" style="background:rgba(255,255,255,0.06); color:#cbd5e1;" onclick="window.DonationManager.switchTab('monitoring')">Batal</button>
              <button type="submit" class="don-action-btn" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#000; padding:10px 24px; font-size:0.88rem; font-weight:800;">
                💾 Simpan &amp; Rilis Program
              </button>
            </div>
          </form>
        </div>
      `;
    },

    handleCreateCampaign(e) {
      e.preventDefault();
      const title = document.getElementById('new-camp-title').value.trim();
      const desc  = document.getElementById('new-camp-desc').value.trim();
      const target= parseFloat(document.getElementById('new-camp-target').value) || 0;
      const start = document.getElementById('new-camp-start').value;
      const end   = document.getElementById('new-camp-end').value;
      const active= document.getElementById('new-camp-active').checked;

      const newC = {
        id: 'camp_' + Date.now(),
        title,
        description: desc,
        target_amount: target,
        collected_amount: 0,
        start_date: start,
        end_date: end,
        is_active: active
      };

      this.data.campaigns.unshift(newC);
      this.saveStoredData();
      this.notifyToast(`✅ Program Donasi '${title}' berhasil dibuat!`, 'success');
      this.switchTab('monitoring');
    },

    // ─── TAB 3: LOG DIGITAL RECEIPT ──────────────────────────────────────────
    renderReceiptsLogTab(container) {
      container.innerHTML = `
        <div style="background:rgba(15,23,42,0.7); border:1px solid rgba(255,255,255,0.08); border-radius:18px; padding:20px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
            <div>
              <h4 style="margin:0; font-size:1.05rem; font-weight:800; color:#fbbf24; display:flex; align-items:center; gap:8px;">
                <span>🧾</span> LOG DIGITAL RECEIPT RESMI
              </h4>
              <p style="margin:3px 0 0; font-size:0.75rem; color:#94a3b8;">
                Arsip kuitansi digital resmi ber-QR dan stempel MB Club Indonesia yang diterbitkan otomatis saat donasi disetujui.
              </p>
            </div>
          </div>

          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.82rem; min-width:800px;">
              <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02); color:#94a3b8; font-size:0.72rem; text-transform:uppercase;">
                  <th style="padding:12px 10px; text-align:center; width:40px;">No</th>
                  <th style="padding:12px 10px; text-align:left;">No. Receipt</th>
                  <th style="padding:12px 10px; text-align:left;">Donatur</th>
                  <th style="padding:12px 10px; text-align:left;">Program</th>
                  <th style="padding:12px 10px; text-align:right;">Nominal</th>
                  <th style="padding:12px 10px; text-align:center;">Tanggal Terbit</th>
                  <th style="padding:12px 10px; text-align:center; width:120px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                ${this.data.receipts.length === 0 ? `
                  <tr><td colspan="7" style="text-align:center; padding:30px; color:#64748b;">Belum ada kuitansi yang diterbitkan.</td></tr>
                ` : this.data.receipts.map((r, idx) => `
                  <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                    <td style="padding:12px 10px; text-align:center; color:#64748b; font-family:monospace;">${idx + 1}</td>
                    <td style="padding:12px 10px; font-family:monospace; color:#fbbf24; font-weight:700;">${r.receipt_number}</td>
                    <td style="padding:12px 10px;">
                      <div style="font-weight:700; color:#fff;">${r.donor_name}</div>
                      <div style="font-size:0.72rem; color:#94a3b8; font-family:monospace;">${r.member_id || '-'}</div>
                    </td>
                    <td style="padding:12px 10px; color:#cbd5e1; font-size:0.78rem;">${r.campaign_title || '-'}</td>
                    <td style="padding:12px 10px; text-align:right; font-family:monospace; color:#34d399; font-weight:700;">Rp ${Number(r.amount || 0).toLocaleString('id-ID')}</td>
                    <td style="padding:12px 10px; text-align:center; color:#94a3b8; font-size:0.75rem;">${r.created_at}</td>
                    <td style="padding:12px 10px; text-align:center;">
                      <button type="button" class="don-action-btn" style="background:rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.3);" onclick="window.DonationManager.openReceiptModalById('${r.id}')">
                        <span>👁️ Lihat Kuitansi</span>
                      </button>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    },

    // ─── TAB 4: TIER MATRIX & DAFTAR MEMBER ──────────────────────────────────
    renderTierMatrixTab(container) {
      const members = this.getAllMembers();

      container.innerHTML = `
        <!-- TIER EXPLANATION CARDS -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:24px;">
          ${TIER_THRESHOLDS.map(t => `
            <div style="background:${t.bg}; border:1px solid ${t.border}; border-radius:16px; padding:18px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <span style="font-size:1.8rem;">${t.icon}</span>
                <span style="font-size:0.8rem; font-weight:800; color:${t.color}; text-transform:uppercase;">${t.tier} TIER</span>
              </div>
              <div style="font-size:0.75rem; color:#94a3b8; margin-bottom:4px;">Batas Minimal Donasi:</div>
              <div style="font-size:1.05rem; font-weight:800; color:#fff; font-family:monospace; margin-bottom:8px;">
                ${t.min === 0 ? 'Rp 0 (Standar)' : `Rp ${t.min.toLocaleString('id-ID')}`}
              </div>
              <div style="font-size:0.7rem; color:#cbd5e1; line-height:1.4;">
                ${t.tier === 'PLATINUM' ? 'Akses VIP penuh, diskon 30% event, merchandise eksklusif, meet & greet' :
                  t.tier === 'GOLD' ? 'Diskon 20% event, prioritas registrasi touring, badge gold' :
                  t.tier === 'SILVER' ? 'Diskon 10% event, akses forum khusus, badge silver' :
                  'Akses reguler forum, newsletter, dan kegiatan klub'}
              </div>
            </div>
          `).join('')}
        </div>

        <!-- MEMBER TIERS TABLE -->
        <div style="background:rgba(15,23,42,0.7); border:1px solid rgba(255,255,255,0.08); border-radius:18px; padding:20px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
            <div>
              <h4 style="margin:0; font-size:1.05rem; font-weight:800; color:#fbbf24; display:flex; align-items:center; gap:8px;">
                <span>🏅</span> STATUS AKUMULASI DONASI &amp; LEVEL TIER ANGGOTA
              </h4>
              <p style="margin:3px 0 0; font-size:0.75rem; color:#94a3b8;">
                Tier keanggotaan terupdate secara real-time berdasarkan total akumulasi donasi yang telah disetujui.
              </p>
            </div>
            <button type="button" class="don-action-btn" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#000; font-weight:800;" onclick="window.DonationManager.syncAllMemberTiers(true)">
              <span>🔄 Hitung Ulang Semua Tier</span>
            </button>
          </div>

          <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.82rem; min-width:750px;">
              <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02); color:#94a3b8; font-size:0.72rem; text-transform:uppercase;">
                  <th style="padding:12px 10px; text-align:center; width:40px;">No</th>
                  <th style="padding:12px 10px; text-align:left;">Member ID</th>
                  <th style="padding:12px 10px; text-align:left;">Nama Lengkap</th>
                  <th style="padding:12px 10px; text-align:right;">Akumulasi Donasi</th>
                  <th style="padding:12px 10px; text-align:center;">Tier Saat Ini</th>
                  <th style="padding:12px 10px; text-align:left;">Target Tier Berikutnya</th>
                </tr>
              </thead>
              <tbody>
                ${members.slice(0, 15).map((m, idx) => {
                  const donAmt = Number(m.total_donation || m.totalDonation || 0);
                  const tInfo  = calcTier(donAmt);
                  const nextT  = tInfo.tier === 'PLATINUM' ? null : (tInfo.tier === 'GOLD' ? TIER_THRESHOLDS[0] : (tInfo.tier === 'SILVER' ? TIER_THRESHOLDS[1] : TIER_THRESHOLDS[2]));
                  const diff   = nextT ? (nextT.min - donAmt) : 0;

                  return `
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">
                      <td style="padding:12px 10px; text-align:center; color:#64748b; font-family:monospace;">${idx + 1}</td>
                      <td style="padding:12px 10px; font-family:monospace; color:#fbbf24; font-weight:700;">${m.member_id || m.memberId || '-'}</td>
                      <td style="padding:12px 10px; font-weight:700; color:#fff;">${m.name || m.username || '-'}</td>
                      <td style="padding:12px 10px; text-align:right; font-family:monospace; color:#34d399; font-weight:700;">
                        Rp ${donAmt.toLocaleString('id-ID')}
                      </td>
                      <td style="padding:12px 10px; text-align:center;">
                        <span style="background:${tInfo.bg}; color:${tInfo.color}; border:1px solid ${tInfo.border}; font-size:0.75rem; padding:3px 12px; border-radius:9999px; font-weight:800; display:inline-flex; align-items:center; gap:5px;">
                          <span>${tInfo.icon}</span> <span>${tInfo.tier}</span>
                        </span>
                      </td>
                      <td style="padding:12px 10px; font-size:0.75rem; color:#94a3b8;">
                        ${nextT ? `Kurang <strong style="color:#fbbf24;">Rp ${diff.toLocaleString('id-ID')}</strong> menuju <strong>${nextT.tier}</strong>` : `<span style="color:#38bdf8; font-weight:700;">🏆 Pangkat Maksimal (PLATINUM)</span>`}
                      </td>
                    </tr>
                  `;
                }).join('')}
              </tbody>
            </table>
          </div>
        </div>
      `;
    },

    // ─── MODAL 1: VERIFIKASI BUKTI TRANSFER (ADMIN WAJIB MELIHAT BUKTI) ──────
    openVerifyModal(donationId) {
      const don = this.data.donations.find(d => String(d.id) === String(donationId) || String(d.trx_code) === String(donationId));
      if (!don) {
        this.notifyToast('Transaksi donasi tidak ditemukan!', 'error');
        return;
      }
      this.selectedDonationId = don.id;

      const camp = this.data.campaigns.find(c => c.id === don.campaign_id) || {};
      const proofUrl = don.payment_proof_url || 'assets/mb_hero.jpg';
      const isPending = don.status === 'PENDING';
      const isSuccess = don.status === 'SUCCESS' || don.status === 'CONFIRMED';
      const isRejected = don.status === 'REJECTED';

      // Hitung simulasi tier
      const member = this.findMemberRecord(don.member_id, don.donor_name);
      const currentDon = Number(member ? (member.total_donation || member.totalDonation || 0) : 0);
      const currentTier = calcTier(currentDon);
      const projectedDon = currentDon + Number(don.amount || 0);
      const projectedTier = calcTier(projectedDon);
      const willUpgrade = projectedTier.tier !== currentTier.tier && isPending;

      const mount = document.getElementById('don-v2-modal-mount');
      if (!mount) return;

      mount.innerHTML = `
        <div class="don-modal-root" id="don-verify-modal-root" onclick="if(event.target===this) window.DonationManager.closeModal()">
          <div class="don-modal-box" style="max-width:680px;">
            <!-- MODAL HEADER -->
            <div style="display:flex; justify-content:space-between; align-items:center; padding:18px 24px; border-bottom:1px solid rgba(255,255,255,0.08);">
              <div>
                <h3 style="margin:0; font-size:1.15rem; font-weight:800; color:#fbbf24; display:flex; align-items:center; gap:8px;">
                  <span>🔍</span> Verifikasi Bukti Transfer Donasi
                </h3>
                <span style="font-size:0.75rem; color:#94a3b8; font-family:monospace;">ID: ${don.trx_code || don.id}</span>
              </div>
              <button type="button" class="don-action-btn" style="background:transparent; color:#94a3b8; font-size:1.2rem; padding:4px 8px;" onclick="window.DonationManager.closeModal()">✕</button>
            </div>

            <!-- MODAL BODY -->
            <div style="padding:24px; max-height:75vh; overflow-y:auto;">
              <!-- 1. BUKTI TRANSFER (WAJIB DILIHAT SECARA JELAS) -->
              <div style="margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                  <span style="font-size:0.75rem; font-weight:800; color:#fbbf24; text-transform:uppercase; letter-spacing:0.05em;">
                    📸 FOTO BUKTI TRANSFER PEMBAYARAN:
                  </span>
                  <a href="${proofUrl}" target="_blank" style="font-size:0.75rem; color:#38bdf8; text-decoration:none; font-weight:600;">
                    🔍 Buka Gambar Penuh (Tab Baru)
                  </a>
                </div>
                <div style="background:#020617; border:2px dashed rgba(245,158,11,0.4); border-radius:14px; padding:8px; text-align:center; overflow:hidden;">
                  <img src="${proofUrl}" alt="Bukti Transfer Donasi" onerror="this.src='assets/mb_hero.jpg'"
                    style="max-height:260px; max-width:100%; border-radius:10px; object-fit:contain; box-shadow:0 10px 25px rgba(0,0,0,0.5);">
                </div>
                <div style="font-size:0.7rem; color:#94a3b8; text-align:center; margin-top:6px;">
                  ℹ️ Mohon periksa kecocokan nominal transfer, nama pengirim, dan tanggal transaksi pada struk di atas.
                </div>
              </div>

              <!-- 2. DETAIL TRANSAKSI & PREVIEW TIER -->
              <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:16px; margin-bottom:20px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px; font-size:0.82rem;">
                  <div>
                    <span style="color:#94a3b8;">Nama Donatur:</span>
                    <strong style="display:block; color:#fff; font-size:0.95rem;">${don.donor_name || 'Hamba Allah'}</strong>
                  </div>
                  <div>
                    <span style="color:#94a3b8;">Member ID &amp; Tier Saat Ini:</span>
                    <div style="display:flex; align-items:center; gap:6px; margin-top:2px;">
                      <span style="font-family:monospace; color:#fbbf24; font-weight:700;">${don.member_id || 'Non-Member'}</span>
                      <span style="background:${currentTier.bg}; color:${currentTier.color}; border:1px solid ${currentTier.border}; font-size:0.68rem; padding:1px 8px; border-radius:9999px; font-weight:800;">
                        ${currentTier.icon} ${currentTier.tier}
                      </span>
                    </div>
                  </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px; font-size:0.82rem;">
                  <div>
                    <span style="color:#94a3b8;">Program Donasi:</span>
                    <strong style="display:block; color:#cbd5e1;">${camp.title || don.campaign_id}</strong>
                  </div>
                  <div>
                    <span style="color:#94a3b8;">Metode &amp; Waktu:</span>
                    <div style="color:#cbd5e1;">${don.payment_method || 'TRANSFER'} • ${don.created_at || 'Hari Ini'}</div>
                  </div>
                </div>

                <div style="border-top:1px dashed rgba(255,255,255,0.1); padding-top:12px; display:flex; justify-content:space-between; align-items:center;">
                  <div>
                    <span style="color:#94a3b8; font-size:0.75rem; text-transform:uppercase;">Nominal Donasi:</span>
                    <div style="font-size:1.3rem; font-weight:800; color:#34d399; font-family:monospace;">
                      Rp ${Number(don.amount || 0).toLocaleString('id-ID')}
                    </div>
                  </div>

                  <!-- SIMULASI KENAIKAN TIER -->
                  ${isPending ? (willUpgrade ? `
                    <div style="text-align:right; background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.35); padding:8px 14px; border-radius:12px;">
                      <span style="font-size:0.7rem; color:#fbbf24; font-weight:700; display:block;">🎉 PROYEKSI KENAIKAN TIER:</span>
                      <span style="font-size:0.85rem; font-weight:800; color:#fff;">
                        ${currentTier.tier} ➔ <strong style="color:${projectedTier.color};">${projectedTier.icon} ${projectedTier.tier}</strong>
                      </span>
                    </div>
                  ` : `
                    <div style="text-align:right; font-size:0.75rem; color:#94a3b8;">
                      Akumulasi Baru: <strong style="color:#fff; font-family:monospace;">Rp ${projectedDon.toLocaleString('id-ID')}</strong><br>
                      (Tetap Tier <strong>${currentTier.tier}</strong>)
                    </div>
                  `) : ''}
                </div>
              </div>

              <!-- STATUS NOTIF JIKA SUDAH DIPROSES -->
              ${isSuccess ? `
                <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.25); border-radius:12px; padding:12px 16px; color:#34d399; font-size:0.8rem; display:flex; align-items:center; gap:10px;">
                  <span style="font-size:1.2rem;">✅</span>
                  <div>Donasi ini telah <strong>disetujui</strong> dan tier keanggotaan donatur telah disesuaikan secara otomatis.</div>
                </div>
              ` : (isRejected ? `
                <div style="background:rgba(244,63,94,0.1); border:1px solid rgba(244,63,94,0.25); border-radius:12px; padding:12px 16px; color:#fb7185; font-size:0.8rem; display:flex; align-items:center; gap:10px;">
                  <span style="font-size:1.2rem;">❌</span>
                  <div>Donasi ini telah <strong>ditolak</strong>. Tidak ada tombol approval untuk transaksi yang telah ditolak.</div>
                </div>
              ` : '')}
            </div>

            <!-- MODAL ACTIONS FOOTER -->
            <div style="padding:16px 24px; border-top:1px solid rgba(255,255,255,0.08); display:flex; justify-content:flex-end; gap:10px; background:rgba(0,0,0,0.2);">
              ${isPending ? `
                <button type="button" class="don-action-btn"
                  style="background:rgba(244,63,94,0.15); color:#fb7185; border:1px solid rgba(244,63,94,0.3); padding:10px 20px;"
                  onclick="window.DonationManager.processVerification('${don.id}', false)">
                  <span>✕ Tolak Donasi</span>
                </button>
                <button type="button" class="don-action-btn"
                  style="background:linear-gradient(135deg,#10b981,#059669); color:#fff; font-weight:800; padding:10px 24px; box-shadow:0 4px 15px rgba(16,185,129,0.3);"
                  onclick="window.DonationManager.processVerification('${don.id}', true)">
                  <span>✓ Setujui &amp; Naikkan Tier Donatur</span>
                </button>
              ` : (isSuccess ? `
                <button type="button" class="don-action-btn" style="background:rgba(255,255,255,0.06); color:#cbd5e1;" onclick="window.DonationManager.closeModal()">Tutup</button>
                <button type="button" class="don-action-btn" style="background:rgba(16,185,129,0.15); color:#34d399; border:1px solid rgba(16,185,129,0.3);" onclick="window.DonationManager.openReceiptModalByDonation('${don.id}')">
                  <span>🧾 Buka Kwitansi Resmi</span>
                </button>
              ` : `
                <button type="button" class="don-action-btn" style="background:rgba(255,255,255,0.06); color:#cbd5e1;" onclick="window.DonationManager.closeModal()">Tutup</button>
              `)}
            </div>
          </div>
        </div>
      `;

      document.body.style.overflow = 'hidden';
    },

    // ─── ACTION: VERIFIKASI (APPROVAL & TIER UPGRADE ENGINE) ──────────────────
    async processVerification(donationId, isApproved) {
      const don = this.data.donations.find(d => String(d.id) === String(donationId));
      if (!don) return;

      const newStatus = isApproved ? 'SUCCESS' : 'REJECTED';
      don.status = newStatus;

      if (isApproved) {
        const donAmt = Number(don.amount || 0);

        // 1. UPDATE CAMPAIGN
        const camp = this.data.campaigns.find(c => c.id === don.campaign_id);
        if (camp) {
          camp.collected_amount = Number(camp.collected_amount || 0) + donAmt;
        }

        // 2. UPGRADE MEMBER TIER DI SELURUH DATABASE
        const member = this.findMemberRecord(don.member_id, don.donor_name);
        let tierUpgradedMessage = '';

        if (member) {
          const oldTot = Number(member.total_donation || member.totalDonation || 0);
          const oldTierObj = calcTier(oldTot);
          const newTot = oldTot + donAmt;

          member.total_donation = newTot;
          member.totalDonation = newTot;

          const newTierObj = calcTier(newTot);
          member.tier = newTierObj.tier;

          if (newTierObj.tier !== oldTierObj.tier) {
            tierUpgradedMessage = `\n🎉 Anggota '${member.name || don.donor_name}' NAIK LEVEL ke Tier ${newTierObj.tier} ${newTierObj.icon}!`;
          }
        }

        // Sinkronkan ke users, m3Data, dan currentUser
        this.applyTierToAllEngines(don.member_id, don.donor_name, donAmt);

        // 3. TERBITKAN DIGITAL RECEIPT OTOMATIS
        const existingReceipt = this.data.receipts.find(r => r.donation_id === don.id);
        if (!existingReceipt) {
          const newRec = {
            id: 'rec_' + Date.now(),
            receipt_number: 'REC-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000),
            donation_id: don.id,
            donor_name: don.donor_name || 'Hamba Allah',
            member_id: don.member_id || 'Non-Member',
            amount: donAmt,
            campaign_title: camp ? camp.title : (don.campaign_id || 'Donasi MB INA'),
            payment_method: don.payment_method || 'TRANSFER',
            created_at: new Date().toLocaleString('id-ID')
          };
          this.data.receipts.unshift(newRec);
        }

        this.notifyToast(`✅ DONASI DISETUJUI! Digital Receipt diterbitkan.${tierUpgradedMessage}`, 'success');
      } else {
        this.notifyToast(`❌ Donasi dari ${don.donor_name} telah DITOLAK.`, 'error');
      }

      this.saveStoredData();

      // Kirim status ke server backend
      try {
        await fetch('api.php?action=verify_donation', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ donation_id: donationId, status: newStatus })
        });
      } catch (e) {
        // Local persistence sudah tersimpan
      }

      // Tutup modal verifikasi & render ulang
      this.closeModal();
      this.renderActiveSubtab();
    },

    // ─── MODAL 2: DIGITAL RECEIPT RESMI ──────────────────────────────────────
    openReceiptModalByDonation(donationId) {
      let rcpt = this.data.receipts.find(r => r.donation_id === donationId);
      if (!rcpt) {
        const don = this.data.donations.find(d => d.id === donationId);
        if (don) {
          rcpt = {
            id: 'rec_' + Date.now(),
            receipt_number: 'REC-2026-' + Math.floor(1000 + Math.random() * 9000),
            donation_id: donationId,
            donor_name: don.donor_name || 'Hamba Allah',
            member_id: don.member_id || 'Non-Member',
            amount: don.amount || 0,
            campaign_title: 'Donasi Bakti Sosial MB INA 2026',
            payment_method: don.payment_method || 'TRANSFER',
            created_at: new Date().toLocaleString('id-ID')
          };
          this.data.receipts.unshift(rcpt);
          this.saveStoredData();
        }
      }
      if (rcpt) this.openReceiptModalById(rcpt.id);
    },

    openReceiptModalById(receiptId) {
      const rcpt = this.data.receipts.find(r => r.id === receiptId);
      if (!rcpt) return;

      const mount = document.getElementById('don-v2-modal-mount');
      if (!mount) return;

      mount.innerHTML = `
        <div class="don-modal-root" id="don-receipt-modal-root" onclick="if(event.target===this) window.DonationManager.closeModal()">
          <div class="don-modal-box" style="max-width:640px;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 22px; border-bottom:1px solid rgba(255,255,255,0.08);">
              <h3 style="margin:0; font-size:1.1rem; color:#fbbf24; font-weight:800; display:flex; align-items:center; gap:8px;">
                <span>🧾</span> DIGITAL RECEIPT RESMI - MB INA
              </h3>
              <button type="button" class="don-action-btn" style="background:transparent; color:#94a3b8; font-size:1.2rem; padding:4px 8px;" onclick="window.DonationManager.closeModal()">✕</button>
            </div>

            <div style="padding:22px; max-height:75vh; overflow-y:auto;" id="don-printable-receipt">
              <div style="background:#0f172a; border:2px solid #f59e0b; border-radius:18px; padding:24px; color:#fff; box-shadow:0 15px 35px rgba(0,0,0,0.5);">
                <!-- BRANDING HEADER -->
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #f59e0b; padding-bottom:12px; margin-bottom:16px;">
                  <div>
                    <div style="font-size:1.15rem; font-weight:900; color:#fbbf24;">⭐ MERCEDES-BENZ CLUB INDONESIA</div>
                    <div style="font-size:0.72rem; color:#94a3b8;">Sekretariat Pengurus Pusat • NPWP: 02.704.3975-411.000</div>
                  </div>
                  <img src="assets/mb_badge.jpg" alt="Logo MB INA" style="width:44px; height:44px; border-radius:50%; border:2px solid #f59e0b;">
                </div>

                <div style="text-align:center; margin-bottom:18px;">
                  <h4 style="font-size:1.15rem; margin:0; letter-spacing:1px; color:#fbbf24;">OFFICIAL DIGITAL RECEIPT</h4>
                  <div style="font-size:0.75rem; color:#94a3b8;">Bukti Penerimaan Donasi &amp; Filantropi Sah</div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:0.82rem; border-bottom:1px dashed rgba(255,255,255,0.15); padding-bottom:12px; margin-bottom:14px;">
                  <div>
                    <span style="color:#94a3b8; font-size:0.72rem;">No. Receipt:</span>
                    <strong style="display:block; color:#fbbf24; font-family:monospace; font-size:0.95rem;">${rcpt.receipt_number}</strong>
                  </div>
                  <div style="text-align:right;">
                    <span style="color:#94a3b8; font-size:0.72rem;">Tanggal Terbit:</span>
                    <strong style="display:block; color:#fff;">${rcpt.created_at}</strong>
                  </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:0.82rem; border-bottom:1px dashed rgba(255,255,255,0.15); padding-bottom:12px; margin-bottom:14px;">
                  <div>
                    <span style="color:#94a3b8; font-size:0.72rem;">Nama Donatur:</span>
                    <strong style="display:block; color:#fff; font-size:0.95rem;">${rcpt.donor_name}</strong>
                  </div>
                  <div style="text-align:right;">
                    <span style="color:#94a3b8; font-size:0.72rem;">Member ID:</span>
                    <strong style="display:block; font-family:monospace; color:#fbbf24;">${rcpt.member_id || '-'}</strong>
                  </div>
                </div>

                <div style="font-size:0.82rem; border-bottom:1px dashed rgba(255,255,255,0.15); padding-bottom:12px; margin-bottom:14px;">
                  <span style="color:#94a3b8; font-size:0.72rem;">Alokasi Program:</span>
                  <strong style="display:block; color:#cbd5e1; margin-bottom:8px;">${rcpt.campaign_title || 'Donasi MB INA'}</strong>
                  <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:#94a3b8;">Metode: <strong>${rcpt.payment_method || 'TRANSFER'}</strong></span>
                    <div style="font-size:1.25rem; font-weight:900; color:#34d399; font-family:monospace;">
                      Rp ${Number(rcpt.amount || 0).toLocaleString('id-ID')}
                    </div>
                  </div>
                </div>

                <div style="text-align:center; font-size:0.75rem; color:#94a3b8; line-height:1.5;">
                  ❤️ <strong>Terima kasih atas kontribusi dan kepedulian Anda!</strong><br>
                  Kuitansi ini diterbitkan secara elektronik oleh MB Club Indonesia dan sah tanpa tanda tangan basah.
                </div>
              </div>
            </div>

            <div style="padding:14px 22px; border-top:1px solid rgba(255,255,255,0.08); display:flex; justify-content:space-between; gap:10px; background:rgba(0,0,0,0.2);">
              <button type="button" class="don-action-btn" style="background:rgba(255,255,255,0.06); color:#cbd5e1;" onclick="window.DonationManager.closeModal()">✕ Tutup</button>
              <div style="display:flex; gap:8px;">
                <button type="button" class="don-action-btn" style="background:rgba(56,189,248,0.15); color:#38bdf8; border:1px solid rgba(56,189,248,0.3);" onclick="window.DonationManager.downloadReceiptPdf('${rcpt.receipt_number}')">
                  <span>📥 Unduh PDF</span>
                </button>
                <button type="button" class="don-action-btn" style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#000; font-weight:800;" onclick="window.print()">
                  <span>🖨️ Cetak Kuitansi</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      `;

      document.body.style.overflow = 'hidden';
    },

    downloadReceiptPdf(receiptNumber) {
      this.notifyToast(`📥 File '${receiptNumber}_MB_INA.pdf' siap dicetak/diunduh. Silakan gunakan opsi Simpan sebagai PDF di menu Cetak.`, 'info');
      window.print();
    },

    // ─── CLOSE MODALS & RESTORE SCROLLBAR (CRITICAL: NEVER LOCKS SCREEN) ──────
    closeModal() {
      const mount = document.getElementById('don-v2-modal-mount');
      if (mount) mount.innerHTML = '';
      document.body.style.overflow = '';
      document.body.style.removeProperty('overflow');
      document.documentElement.style.overflow = '';
      this.selectedDonationId = null;
    },

    // ─── MEMBER LOOKUP & TIER SYNCHRONIZATION ─────────────────────────────────
    findMemberRecord(memberId, name) {
      const mid = (memberId || '').toUpperCase();
      const n = (name || '').toLowerCase();

      const list = this.getAllMembers();
      return list.find(m => {
        const mMid = (m.member_id || m.memberId || '').toUpperCase();
        const mN   = (m.name || m.username || '').toLowerCase();
        return (mid && mMid && mid === mMid) || (n && mN && (n === mN || mN.includes(n) || n.includes(mN)));
      });
    },

    getAllMembers() {
      if (window.AppEngine && window.AppEngine.m3Data && Array.isArray(window.AppEngine.m3Data.members)) {
        return window.AppEngine.m3Data.members;
      }
      if (window.AppEngine && Array.isArray(window.AppEngine.users)) {
        return window.AppEngine.users;
      }
      return [
        { member_id: 'MBINA-HQ-2026-000001', name: 'Derist Touriano', total_donation: 5000000, tier: 'GOLD' },
        { member_id: 'MBINA-JKT-2026-000005', name: 'Andi Pratama', total_donation: 2000000, tier: 'SILVER' },
        { member_id: 'MBINA-BDG-2026-000006', name: 'Budi Santoso', total_donation: 500000, tier: 'BRONZE' },
        { member_id: 'MBINA-SBY-2026-000007', name: 'Siti Rahayu', total_donation: 4500000, tier: 'GOLD' },
        { member_id: 'MBINA-JKT-2026-000009', name: 'Denny Kurniawan', total_donation: 10000000, tier: 'PLATINUM' }
      ];
    },

    applyTierToAllEngines(memberId, name, addAmount) {
      const mid = (memberId || '').toUpperCase();
      const n   = (name || '').toLowerCase();

      const apply = (u) => {
        if (!u) return;
        const uMid = (u.member_id || u.memberId || '').toUpperCase();
        const uN   = (u.name || u.username || '').toLowerCase();
        if ((mid && uMid && mid === uMid) || (n && uN && (n === uN || uN.includes(n) || n.includes(uN)))) {
          const curTot = Number(u.total_donation || u.totalDonation || 0);
          const newTot = curTot + addAmount;
          u.total_donation = newTot;
          u.totalDonation  = newTot;
          u.tier           = calcTier(newTot).tier;
        }
      };

      if (window.AppEngine) {
        if (Array.isArray(window.AppEngine.users)) window.AppEngine.users.forEach(apply);
        if (window.AppEngine.m3Data && Array.isArray(window.AppEngine.m3Data.members)) window.AppEngine.m3Data.members.forEach(apply);
        if (window.AppEngine.currentUser) apply(window.AppEngine.currentUser);
        if (typeof window.AppEngine.populateMemberPortalData === 'function') window.AppEngine.populateMemberPortalData();
      }
    },

    syncAllMemberTiers(showToastNotice = false) {
      const members = this.getAllMembers();
      let updated = 0;

      members.forEach(m => {
        // Hitung akumulasi dari transaksi donasi berstatus SUCCESS
        const mMid = (m.member_id || m.memberId || '').toUpperCase();
        const mN   = (m.name || m.username || '').toLowerCase();

        const memberSuccessDonations = this.data.donations.filter(d => {
          if (d.status !== 'SUCCESS' && d.status !== 'CONFIRMED') return false;
          const dMid = (d.member_id || '').toUpperCase();
          const dN   = (d.donor_name || '').toLowerCase();
          return (mMid && dMid && mMid === dMid) || (mN && dN && (mN === dN || dN.includes(mN) || mN.includes(dN)));
        });

        const sum = memberSuccessDonations.reduce((acc, curr) => acc + Number(curr.amount || 0), 0);
        const actualTot = Math.max(sum, Number(m.total_donation || m.totalDonation || 0));
        m.total_donation = actualTot;
        m.totalDonation  = actualTot;

        const calculatedTier = calcTier(actualTot).tier;
        if (m.tier !== calculatedTier) {
          m.tier = calculatedTier;
          updated++;
        }
      });

      if (showToastNotice) {
        this.notifyToast(`🔄 Evaluasi Tier Selesai! ${updated} anggota disesuaikan tiernya berdasarkan akumulasi donasi.`, 'success');
        this.renderActiveSubtab();
      }
    },

    // ─── EXPORT HELPERS (NO ALERT, SAFE DOWNLOAD) ────────────────────────────
    exportExcel() {
      const rows = [
        ['No', 'Kode Transaksi', 'Member ID', 'Nama Donatur', 'Program Donasi', 'Nominal (Rp)', 'Metode', 'Status', 'Tanggal']
      ];
      this.data.donations.forEach((d, idx) => {
        const camp = this.data.campaigns.find(c => c.id === d.campaign_id) || {};
        rows.push([
          idx + 1,
          `"${d.trx_code || d.id}"`,
          `"${d.member_id || '-'}"`,
          `"${d.donor_name || 'Hamba Allah'}"`,
          `"${camp.title || d.campaign_id}"`,
          d.amount || 0,
          `"${d.payment_method || 'TRANSFER'}"`,
          `"${d.status || 'PENDING'}"`,
          `"${d.created_at || '-'}"`
        ]);
      });

      const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + rows.map(e => e.join(',')).join('\n');
      const encodedUri = encodeURI(csvContent);
      const link = document.createElement('a');
      link.setAttribute('href', encodedUri);
      link.setAttribute('download', `Laporan_Donasi_MBINA_${new Date().toISOString().slice(0,10)}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      this.notifyToast('📥 Laporan donasi berhasil di-export ke format CSV / Excel!', 'success');
    },

    exportPdf() {
      this.notifyToast('📄 Menyiapkan dokumen laporan donasi untuk dicetak / disimpan sebagai PDF...', 'info');
      window.print();
    },

    // ─── TOAST NOTIFICATION ──────────────────────────────────────────────────
    notifyToast(msg, type = 'info') {
      if (window.showToast) {
        window.showToast(msg, type);
        return;
      }
      // Fallback floating toast non-blocking
      let toastBox = document.getElementById('don-floating-toast');
      if (!toastBox) {
        toastBox = document.createElement('div');
        toastBox.id = 'don-floating-toast';
        toastBox.style.cssText = 'position:fixed; bottom:24px; right:24px; z-index:999999; display:flex; flex-direction:column; gap:8px;';
        document.body.appendChild(toastBox);
      }
      const item = document.createElement('div');
      const bg = type === 'success' ? '#059669' : (type === 'error' ? '#e11d48' : '#2563eb');
      item.style.cssText = `background:${bg}; color:#fff; padding:12px 20px; border-radius:12px; font-size:0.85rem; font-weight:600; box-shadow:0 10px 25px rgba(0,0,0,0.5); display:flex; align-items:center; gap:8px; animation:donModalPop 0.2s;`;
      item.innerText = msg;
      toastBox.appendChild(item);
      setTimeout(() => { item.remove(); }, 4000);
    }
  };

  // Expose to window
  window.DonationManager = DonationManager;

  // Auto-wire with AppEngine tab switching
  function wireToAppEngine() {
    if (window.AppEngine) {
      window.AppEngine.renderDonationModule = function() {
        DonationManager.init();
      };
      window.AppEngine.openDonationVerifyModal = function(id) {
        DonationManager.openVerifyModal(id);
      };
      window.AppEngine.openDigitalReceiptModalByDonationId = function(id) {
        DonationManager.openReceiptModalByDonation(id);
      };
    }
    if (window.M6Engine) {
      window.M6Engine.renderDonationModule = function() {
        DonationManager.init();
      };
      window.M6Engine.openDonationVerifyModal = function(id) {
        DonationManager.openVerifyModal(id);
      };
    }
    window.openDonationVerifyModal = function(id) {
      DonationManager.openVerifyModal(id);
    };
    window.switchDonationSubtab = function(subtab) {
      const tabMap = {
        '7_3_1_progress': 'monitoring',
        '7_3_2_form': 'campaign_form',
        '7_3_4_receipts': 'receipts'
      };
      DonationManager.switchTab(tabMap[subtab] || subtab);
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      wireToAppEngine();
      setTimeout(wireToAppEngine, 500);
    });
  } else {
    wireToAppEngine();
    setTimeout(wireToAppEngine, 500);
  }

})(window, document);
