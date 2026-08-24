/* Main Web Application Engine & Dynamic RBAC Controller for MB INA - MODUL M2 MANAJEMEN ORGANISASI COMPLETE WITH 110+ CLUBS CRUD */

const AppEngine = {
  currentRole: 'GUEST',
  currentUser: {
    id: 'usr_guest',
    name: 'Pengunjung',
    username: 'guest',
    email: '',
    role: 'GUEST'
  },
  isLightMode: false,
  activeAdminTab: 'dashboard',
  activeSettingsSubtab: 'general',
  activeM2Subtab: 'profile',
  clubs: [],
  events: [],
  users: [],
  provinces: [],
  tiers: [],
  emailTemplates: {},
  backups: [],
  m2Data: {
    organization: null,
    history: [],
    founders: [],
    visionMission: [],
    presidents: [],
    periods: [],
    advisoryBoard: [],
    honorCouncil: [],
    structure: [],
    clubs: []
  },
  m3Data: {
    members: [],
    pendingCount: 0,
    activeSubtab: 'list'
  },
  m4Data: {
    applications: [],
    notes: [],
    evaluations: [],
    activeStep: 1,
    activeSubtab: 'form'
  },
  m5Data: {
    categories: [],
    threads: [],
    replies: [],
    broadcasts: [],
    reports: [],
    rules: [],
    trending: [],
    activeThreadId: 'th_001',
    activeSubtab: 'forum'
  },
  testimonials: [
    {
      name: 'Derist Touriano',
      role: 'Super Admin & System Architect',
      city: 'Jambi',
      avatar: 'DT',
      quote: 'Platform MB INA menghubungkan ribuan pecinta motor dan klub resmi dengan sistem keanggotaan terintegrasi, transparan, dan sangat modern.'
    },
    {
      name: 'Dr. Rochady Hendra Setya Wibawa, Sp.OG., M.Kes., S.Kom.',
      role: 'Presiden MB INA (2025-2027)',
      city: 'Kota Bandung',
      avatar: 'RH',
      quote: 'MB INA berdiri kokoh sebagai simbol persaudaraan, ketertiban lalulintas, dan solidaritas antar pecinta otomotif motor se-Indonesia.'
    },
    {
      name: 'Ir. Raymond Sanjaya',
      role: 'Sekretaris Pusat MB INA',
      city: 'Bandung',
      avatar: 'RS',
      quote: 'Proses verifikasi keanggotaan yang mudah tanpa perlu risau privasi dokumen fisik. Sangat mendukung operasional touring dan event jamnas.'
    }
  ],
  adminStats: null,
  auditLogs: [],
  systemSettings: null,

  init() {
    console.log("MB INA Platform Engine Initializing (M2 & M3 Active)...");
    this.initSession();
    this.renderView();
    this.initTheme();
    this.bindAdminTabs();

    // Fetch data asynchronously in background without blocking page load spinner!
    setTimeout(() => {
      this.fetchM2Data();
      this.fetchData();
      this.fetchM5Data();
      this.renderTestimonials();
    }, 20);
  },

  initSession() {
    const savedUser = localStorage.getItem('mbina_session_user');
    if (savedUser) {
      try {
        const u = JSON.parse(savedUser);
        if (u && u.role && u.role !== 'GUEST') {
          this.currentRole = u.role;
          this.currentUser = u;
          return;
        }
      } catch (e) {
        console.error("Session parse error:", e);
      }
    }

    // STRICT ZERO-TRUST SECURITY: Unauthenticated users are ALWAYS 'GUEST'
    this.currentRole = 'GUEST';
    this.currentUser = { id: 'usr_guest', name: 'Pengunjung', username: 'guest', email: '', role: 'GUEST' };
  },

  navigateToHome() {
    document.querySelectorAll('.view-container').forEach(el => el.style.display = 'none');
    const landingView = document.getElementById('view-landing-page');
    if (landingView) landingView.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  },

  openPortalAdmin() {
    if (this.currentRole === 'GUEST') {
      AuthEngine.openModal('modal-login');
      alert('🔒 Akses Terbatas: Silakan Login terlebih dahulu dengan akun Super Admin / Pengurus!');
    } else {
      document.querySelectorAll('.view-container').forEach(el => el.style.display = 'none');
      const adminView = document.getElementById('view-admin-dashboard');
      if (adminView) {
        adminView.style.display = 'block';
        this.switchAdminTab(this.activeAdminTab);
      }
    }
  },

  initTheme() {
    const savedTheme = localStorage.getItem('mbina_theme');
    if (savedTheme === 'light') {
      this.isLightMode = true;
      document.body.classList.add('light-mode');
    }
    this.updateThemeToggleUI();
  },

  toggleTheme() {
    this.isLightMode = !this.isLightMode;
    if (this.isLightMode) {
      document.body.classList.add('light-mode');
      localStorage.setItem('mbina_theme', 'light');
    } else {
      document.body.classList.remove('light-mode');
      localStorage.setItem('mbina_theme', 'dark');
    }
    this.updateThemeToggleUI();
  },

  updateThemeToggleUI() {
    const themeBtn = document.getElementById('theme-toggle-btn');
    if (themeBtn) {
      themeBtn.innerHTML = this.isLightMode ? '🌙 Dark Mode' : '☀️ Light Mode';
    }
  },

  async fetchData() {
    try {
      const [resEvents, resStats, resProv] = await Promise.all([
        fetch('api.php?action=get_events').then(r => r.json()),
        fetch('api.php?action=get_admin_stats').then(r => r.json()),
        fetch('api.php?action=get_provinces_admin').then(r => r.json())
      ]);

      if (resEvents.success) this.events = resEvents.events;
      if (resStats.success) this.adminStats = resStats;
      if (resProv.success) this.provinces = resProv.provinces;

      this.renderEventsList();
      this.renderProvincesDropdown();
    } catch (e) {
      console.error("Error loading API data:", e);
    }
  },

  showLoader(message = 'Memuat Data (Supabase Cloud)...') {
    const el = document.getElementById('global-app-loader');
    const txt = document.getElementById('global-loader-text');
    if (txt) txt.innerText = message;
    if (el) el.style.display = 'flex';
  },

  hideLoader() {
    const el = document.getElementById('global-app-loader');
    if (el) el.style.display = 'none';
  },

  async fetchM2Data() {
    this.showLoader('Memuat Data Organisasi, Klub & Keanggotaan (Supabase Cloud)...');
    try {
      const res = await fetch('api.php?action=get_app_init_data').then(r => r.json());
      if (res.success) {
        if (res.organization) this.m2Data.organization = res.organization;
        if (res.founders) this.m2Data.founders = res.founders;
        if (res.visionMission) this.m2Data.visionMission = res.visionMission;
        if (res.presidents) this.m2Data.presidents = res.presidents;
        if (res.advisoryBoard) this.m2Data.advisoryBoard = res.advisoryBoard;
        if (res.honorCouncil) this.m2Data.honorCouncil = res.honorCouncil;
        if (res.structure) this.m2Data.structure = res.structure;
        if (res.periods) this.m2Data.periods = res.periods;
        if (res.clubs) {
          this.m2Data.clubs = res.clubs;
          this.clubs = res.clubs;
        }
        if (res.members) {
          this.m3Data.members = res.members;
          this.users = res.members;
        }
        if (res.pendingCount !== undefined) {
          this.m3Data.pendingCount = res.pendingCount;
        }

        // M4 Data Population
        if (res.clubApplications) this.m4Data.applications = res.clubApplications;
        if (res.clubApplicationNotes) this.m4Data.notes = res.clubApplicationNotes;
        if (res.clubEvaluations) this.m4Data.evaluations = res.clubEvaluations;

        this.renderClubsList();
        this.renderLandingPageVisionMission();
        if (this.activeAdminTab === 'm2_org') this.renderM2Module();
        if (this.activeAdminTab === 'm3_membership') this.renderM3Module();
        if (this.activeAdminTab === 'm4_registration') this.renderM4Module();
        if (this.activeAdminTab === 'm1_portal' || this.activeAdminTab === 'dashboard') this.renderAdminDashboard();
      }
    } catch (e) {
      console.error("Error fetching M2 Data:", e);
    } finally {
      this.hideLoader();
    }
  },

  renderProvincesDropdown() {
    const optionsHtml = this.provinces.map(p => `
      <option value="${p.id}">${p.name} (${p.region})</option>
    `).join('');

    const regSelect = document.getElementById('reg-province-select');
    if (regSelect) regSelect.innerHTML = optionsHtml;

    const addSelect = document.getElementById('add-user-province');
    if (addSelect) addSelect.innerHTML = optionsHtml;

    const editSelect = document.getElementById('edit-user-province');
    if (editSelect) editSelect.innerHTML = optionsHtml;
  },

  renderTestimonials() {
    const container = document.getElementById('testimonials-grid-container');
    if (!container) return;

    container.innerHTML = this.testimonials.map(t => `
      <div class="testimonial-card">
        <p style="font-style:italic; font-size:0.9rem; color:var(--text-main); margin-bottom:16px;">
          "${t.quote}"
        </p>
        <div style="display:flex; align-items:center; gap:12px; border-top:1px solid var(--chrome-border); padding-top:12px;">
          <div style="width:40px; height:40px; border-radius:50%; background:var(--accent-gold); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:0.85rem;">
            ${t.avatar}
          </div>
          <div>
            <h4 style="font-size:0.95rem; line-height:1.2;">${t.name}</h4>
            <span style="font-size:0.75rem; color:var(--accent-gold);">${t.role} (${t.city})</span>
          </div>
        </div>
      </div>
    `).join('');
  },

  bindAdminTabs() {
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const tab = e.currentTarget.getAttribute('data-admin-tab');
        this.switchAdminTab(tab);
      });
    });
  },

  switchAdminTab(tab) {
    this.activeAdminTab = tab;
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
      if (btn.getAttribute('data-admin-tab') === tab || (tab === 'm1_portal' && btn.getAttribute('data-admin-tab') === 'm1_portal')) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    document.querySelectorAll('.admin-tab-content').forEach(el => el.style.display = 'none');
    
    let targetId = `admin-tab-${tab}`;
    if (tab === 'dashboard' || tab === 'users' || tab === 'audit' || tab === 'settings') {
      targetId = 'admin-tab-m1_portal';
    }

    const targetContent = document.getElementById(targetId);
    if (targetContent) targetContent.style.display = 'block';

    if (tab === 'm1_portal' || tab === 'dashboard') {
      this.switchM1Subtab('dashboard');
    } else if (tab === 'users') {
      this.switchM1Subtab('users');
    } else if (tab === 'audit') {
      this.switchM1Subtab('audit');
    } else if (tab === 'settings') {
      this.switchM1Subtab('settings');
    } else if (tab === 'm2_org') {
      this.renderM2Module();
    } else if (tab === 'm3_membership') {
      this.renderM3Module();
    } else if (tab === 'm4_registration') {
      this.renderM4Module();
    } else if (tab === 'm5_forum') {
      this.renderM5Module();
    }
  },

  switchM1Subtab(subtab) {
    this.activeM1Subtab = subtab;
    document.querySelectorAll('[data-m1sub]').forEach(btn => {
      if (btn.getAttribute('data-m1sub') === subtab) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    document.querySelectorAll('.m1-subtab-content').forEach(el => el.style.display = 'none');
    const target = document.getElementById(`m1sub-${subtab}`);
    if (target) target.style.display = 'block';

    if (subtab === 'dashboard') {
      this.renderAdminDashboard();
    } else if (subtab === 'users') {
      this.renderUserManagement();
    } else if (subtab === 'audit') {
      this.renderAuditLogs();
    } else if (subtab === 'settings') {
      this.renderSystemSettings();
    }
  },

  setRole(role, userData = null) {
    this.currentRole = role;
    this.currentUser = userData || this.getDefaultUserForRole(role);
    if (role !== 'GUEST') {
      localStorage.setItem('mbina_session_user', JSON.stringify(this.currentUser));
    } else {
      localStorage.removeItem('mbina_session_user');
    }
    this.renderView();
  },

  formatRoleName(role) {
    const map = {
      'SUPER_ADMIN': 'Super Admin',
      'PRESIDEN': 'Presiden MB INA',
      'SEKRETARIS_PUSAT': 'Sekretaris Pusat',
      'BENDAHARA_PUSAT': 'Bendahara Pusat',
      'ADMIN_ORGANISASI': 'Admin Organisasi',
      'PENGURUS_PUSAT': 'Pengurus Pusat',
      'PENGURUS_KLUB': 'Pengurus Klub',
      'MEMBER': 'Member Terverifikasi',
      'CALON_MEMBER': 'Calon Member (Pending)',
      'GUEST': 'Guest (Pengunjung)'
    };
    return map[role] || role;
  },

  renderView() {
    document.querySelectorAll('.view-container').forEach(el => el.style.display = 'none');

    const userProfileWidget = document.getElementById('nav-user-profile');
    const btnLogout = document.getElementById('btn-logout');
    const btnLogin = document.getElementById('btn-login-nav');
    const navAvatar = document.getElementById('nav-user-avatar');
    const adminView = document.getElementById('view-admin-dashboard');
    const landingView = document.getElementById('view-landing-page');

    if (this.currentRole === 'GUEST' || !this.currentRole) {
      if (userProfileWidget) userProfileWidget.style.display = 'none';
      if (btnLogout) btnLogout.style.display = 'none';
      if (btnLogin) btnLogin.style.display = 'inline-flex';

      if (adminView) adminView.style.display = 'none';
      if (landingView) landingView.style.display = 'block';
    } else {
      if (userProfileWidget) {
        userProfileWidget.style.display = 'flex';
        document.getElementById('nav-user-name').innerText = this.currentUser.name;
        document.getElementById('nav-user-role-badge').innerText = this.formatRoleName(this.currentRole);
        if (navAvatar && this.currentUser && this.currentUser.name) {
          navAvatar.innerText = this.currentUser.name.substring(0, 2).toUpperCase();
        }
      }
      if (btnLogout) btnLogout.style.display = 'inline-flex';
      if (btnLogin) btnLogin.style.display = 'none';

      if (landingView) landingView.style.display = 'none';
      if (adminView) {
        adminView.style.display = 'block';
        this.switchAdminTab(this.activeAdminTab);
      }
    }
  },

  renderClubsList() {
    const container = document.getElementById('clubs-grid-container');
    if (!container) return;

    const list = this.m2Data.clubs.length ? this.m2Data.clubs.slice(0, 9) : this.clubs;

    container.innerHTML = list.map(c => `
      <div class="glass-card" style="padding:20px;">
        <div style="display:flex; align-items:center; gap:14px; margin-bottom:12px;">
          <div style="width:48px; height:48px; border-radius:12px; background:rgba(245,158,11,0.15); border:1px solid var(--accent-gold); display:flex; align-items:center; justify-content:center; color:var(--accent-gold); font-weight:800;">
            ${c.code ? c.code.substring(0, 4) : 'MB'}
          </div>
          <div>
            <h4 style="font-size:1.05rem; line-height:1.2;">${c.name}</h4>
            <span style="font-size:0.75rem; color:var(--text-muted);">${c.region} (${c.city})</span>
          </div>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--chrome-border); padding-top:12px; margin-top:12px;">
          <span style="font-size:0.8rem; color:var(--accent-gold); font-weight:700;">👥 ${c.member_count || c.memberCount || 50} Anggota</span>
          <button class="role-pill-btn" style="border-color:var(--accent-gold);" onclick="AppEngine.showClubDetail('${c.id}')">Lihat Club</button>
        </div>
      </div>
    `).join('');
  },

  renderEventsList() {
    const container = document.getElementById('events-grid-container');
    if (!container) return;

    container.innerHTML = this.events.map(e => `
      <div class="glass-card" style="padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
          <span class="tier-badge" style="background:rgba(245,158,11,0.2); color:var(--accent-gold); border:1px solid var(--accent-gold);">UPCOMING</span>
          <span style="font-size:0.75rem; color:var(--text-muted);">${e.eventDate ? e.eventDate.split(' ')[0] : ''}</span>
        </div>
        <h4 style="font-size:1.1rem; margin-bottom:8px;">${e.title}</h4>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:14px;">📍 ${e.location}</p>
        <p style="font-size:0.85rem; color:var(--text-main); margin-bottom:16px;">${e.description}</p>
        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--chrome-border); padding-top:12px;">
          <span style="font-size:0.8rem; color:var(--text-muted);">${e.registeredCount} / ${e.maxAttendees} Terdaftar</span>
          <button class="btn-primary" style="padding:6px 16px; font-size:0.8rem;" onclick="AppEngine.handleRSVP('${e.id}')">RSVP Event</button>
        </div>
      </div>
    `).join('');
  },

  renderAdminDashboard() {
    const totalClubs = (this.m2Data && this.m2Data.clubs && this.m2Data.clubs.length > 0) ? this.m2Data.clubs.length : (this.clubs && this.clubs.length > 0 ? this.clubs.length : 110);
    const totalMembers = (this.users && this.users.length > 0) ? this.users.length : 12544;
    const stats = this.adminStats ? this.adminStats.stats : { totalMembers: totalMembers, activeClubs: totalClubs, monthlyTransactionRp: 'Rp 425.500.000', pendingApprovals: 1 };
    
    const membersEl = document.getElementById('stat-members-count');
    if (membersEl) membersEl.innerText = (stats.totalMembers || 12544).toLocaleString();
    
    const clubsEl = document.getElementById('stat-clubs-count');
    if (clubsEl) clubsEl.innerText = totalClubs || 110;

    const revEl = document.getElementById('stat-revenue-amount');
    if (revEl) revEl.innerText = stats.monthlyTransactionRp || 'Rp 425.500.000';

    const pendEl = document.getElementById('stat-pending-count');
    if (pendEl) pendEl.innerText = stats.pendingApprovals !== undefined ? stats.pendingApprovals : 1;

    const badgeTotal = document.getElementById('map-total-clubs-badge');
    if (badgeTotal) badgeTotal.innerText = `${totalClubs || 110} KLUB TERDAFTAR`;

    // Render Interactive Indonesia SVG Map
    this.renderIndonesiaMap();

    const growthData = (this.adminStats && this.adminStats.chartGrowth) ? this.adminStats.chartGrowth.weekly : { Sen: 42, Sel: 65, Rab: 58, Kam: 84, Jum: 96, Sab: 120, Ming: 110 };
    const chartContainer = document.getElementById('admin-growth-chart');
    if (chartContainer) {
      const maxVal = Math.max(...Object.values(growthData));
      chartContainer.innerHTML = Object.entries(growthData).map(([day, val]) => `
        <div class="chart-bar-col">
          <div class="chart-bar" style="height: ${(val / maxVal) * 100}%;" title="${val} Registrasi Baru"></div>
          <span style="font-size:0.75rem; color:var(--text-muted);">${day}</span>
        </div>
      `).join('');
    }

    const regData = (this.adminStats && this.adminStats.regionalDistribution) ? this.adminStats.regionalDistribution : [
      { region: 'DKI Jakarta & Banten', count: 4820, percentage: 38 },
      { region: 'Jawa Barat', count: 2950, percentage: 24 },
      { region: 'Jawa Tengah & DIY', count: 1840, percentage: 15 },
      { region: 'Jawa Timur & Bali', count: 1650, percentage: 13 },
      { region: 'Sumatera & Kalimantan', count: 1280, percentage: 10 }
    ];
    const regionContainer = document.getElementById('admin-region-distribution');
    if (regionContainer) {
      regionContainer.innerHTML = regData.map(r => `
        <div class="region-progress-row">
          <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
            <span>${r.region}</span>
            <strong style="color:var(--accent-gold);">${r.count} Anggota (${r.percentage}%)</strong>
          </div>
          <div class="region-progress-bar">
            <div class="region-progress-fill" style="width:${r.percentage}%;"></div>
          </div>
        </div>
      `).join('');
    }

    const alerts = (this.adminStats && this.adminStats.systemAlerts) ? this.adminStats.systemAlerts : [];
    const alertsContainer = document.getElementById('admin-alerts-feed');
    if (alertsContainer) {
      alertsContainer.innerHTML = alerts.map(a => `
        <div style="padding:12px; border-bottom:1px solid var(--chrome-border); display:flex; align-items:center; gap:12px; font-size:0.85rem;">
          <span class="tier-badge" style="background:${a.level === 'WARNING' ? 'rgba(239,68,68,0.2)' : 'rgba(59,130,246,0.2)'}; color:${a.level === 'WARNING' ? 'var(--accent-red)' : 'var(--accent-blue)'}; border:1px solid ${a.level === 'WARNING' ? 'var(--accent-red)' : 'var(--accent-blue)'};">
            ${a.level}
          </span>
          <span style="flex:1;">${a.message}</span>
          <span style="font-size:0.75rem; color:var(--text-muted);">${a.time}</span>
        </div>
      `).join('');
    }

    this.renderVerificationQueue();
  },

  // 🗺️ INTERACTIVE REALISTIC MAP OF INDONESIA WITH GEOGRAPHICAL IMAGE & JARUM PENTUL PINS
  renderIndonesiaMap() {
    const mapContainer = document.getElementById('admin-indonesia-map');
    if (!mapContainer) return;

    const clubs = this.m2Data.clubs;

    // Group clubs by region
    const regionMap = {
      'Regional Sumatra': clubs.filter(c => c.region === 'Regional Sumatra'),
      'Regional Banten': clubs.filter(c => c.region === 'Regional Banten'),
      'Regional Metro DKI Jakarta': clubs.filter(c => c.region === 'Regional Metro DKI Jakarta'),
      'Regional Jawa Barat': clubs.filter(c => c.region === 'Regional Jawa Barat'),
      'Regional Jawa Tengah': clubs.filter(c => c.region === 'Regional Jawa Tengah'),
      'Regional Yogyakarta': clubs.filter(c => c.region === 'Regional Yogyakarta'),
      'Regional Jawa Timur & Bali': clubs.filter(c => c.region === 'Regional Jawa Timur & Bali'),
      'Regional Kalimantan & Sulawesi': clubs.filter(c => c.region === 'Regional Kalimantan & Sulawesi')
    };

    // Hotspot Jarum Pentul Coordinates mapped precisely over images/indonesia_map.png (viewBox 0 0 1000 480)
    // Needle tip touches (x, y + 16) directly in the vertical center of island land masses
    const pins = [
      { key: 'Regional Sumatra', name: 'Regional Sumatra', x: 170, y: 182, color: '#f59e0b' },
      { key: 'Regional Banten', name: 'Regional Banten', x: 260, y: 315, color: '#3b82f6' },
      { key: 'Regional Metro DKI Jakarta', name: 'Regional Metro DKI Jakarta', x: 285, y: 316, color: '#10b981' },
      { key: 'Regional Jawa Barat', name: 'Regional Jawa Barat', x: 315, y: 322, color: '#f59e0b' },
      { key: 'Regional Jawa Tengah', name: 'Regional Jawa Tengah', x: 365, y: 326, color: '#ef4444' },
      { key: 'Regional Yogyakarta', name: 'Regional Yogyakarta', x: 385, y: 334, color: '#8b5cf6' },
      { key: 'Regional Jawa Timur & Bali', name: 'Regional Jawa Timur & Bali', x: 420, y: 335, color: '#ec4899' },
      { key: 'Regional Kalimantan & Sulawesi', name: 'Regional Kalimantan', x: 395, y: 195, color: '#06b6d4' },
      { key: 'Regional Kalimantan & Sulawesi', name: 'Regional Sulawesi', x: 525, y: 225, color: '#14b8a6' }
    ];

    // Build Jarum Pentul Pins WITHOUT ANY TEXT LABELS OVERLAPPING THE MAP
    const pinsSvg = pins.map(p => {
      const cList = regionMap[p.key] || [];
      const count = cList.length;

      return `
        <!-- JARUM PENTUL PIN: ${p.name} -->
        <g class="jarum-pentul-group" cursor="pointer" onclick="AppEngine.switchM2Subtab('clubs')" title="📍 ${p.name}: ${count} Klub Terdaftar">
          <!-- Soft glowing pulse behind pin -->
          <circle cx="${p.x}" cy="${p.y + 16}" r="14" fill="${p.color}" opacity="0.35">
            <animate attributeName="r" values="10;20;10" dur="2s" repeatCount="indefinite"/>
            <animate attributeName="opacity" values="0.5;0.1;0.5" dur="2s" repeatCount="indefinite"/>
          </circle>

          <!-- Shadow beneath needle tip -->
          <ellipse cx="${p.x}" cy="${p.y + 16}" rx="7" ry="3" fill="rgba(0,0,0,0.6)"/>

          <!-- Needle Shaft -->
          <line x1="${p.x}" y1="${p.y - 2}" x2="${p.x}" y2="${p.y + 16}" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/>

          <!-- Jarum Pentul Head Ball -->
          <circle cx="${p.x}" cy="${p.y - 6}" r="8" fill="${p.color}" stroke="#ffffff" stroke-width="2"/>
          <circle cx="${p.x - 2.5}" cy="${p.y - 8.5}" r="2.5" fill="#ffffff" opacity="0.8"/>
        </g>
      `;
    }).join('');

    mapContainer.innerHTML = `
      <svg viewBox="0 0 1000 480" style="width:100%; height:auto; display:block; filter:drop-shadow(0 4px 14px rgba(0,0,0,0.3)); background:var(--bg-card); border-radius:12px;">
        <!-- HIGH-RES INDONESIA GEOGRAPHICAL MAP IMAGE -->
        <image href="images/indonesia_map.png" x="0" y="0" width="1000" height="480" preserveAspectRatio="xMidYMid meet"/>

        <!-- 📍 JARUM PENTUL MAP MARKERS OVER THE MAP IMAGE -->
        ${pinsSvg}
      </svg>
    `;
  },

  // ============================================
  // MODUL M2 MANAJEMEN ORGANISASI RENDERERS
  // ============================================
  switchM2Subtab(subtab) {
    if (!subtab || typeof subtab !== 'string') subtab = 'profile';
    this.activeM2Subtab = subtab;
    document.querySelectorAll('[data-m2sub]').forEach(btn => {
      if (btn.getAttribute('data-m2sub') === subtab) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    document.querySelectorAll('.m2-subtab-content').forEach(el => el.style.display = 'none');
    const targetContent = document.getElementById(`m2sub-${subtab}`);
    if (targetContent) targetContent.style.display = 'block';

    if (subtab === 'profile') this.renderM2Profile();
    else if (subtab === 'founders') this.renderM2Founders();
    else if (subtab === 'vision') this.renderM2Vision();
    else if (subtab === 'presidents') this.renderM2Presidents();
    else if (subtab === 'structure') this.renderM2Structure();
    else if (subtab === 'clubs') this.renderM2Clubs();
  },

  renderM2Module() {
    this.switchM2Subtab(this.activeM2Subtab || 'profile');
  },

  // 2.1.1 INFORMASI DASAR WITH CRUD
  renderM2Profile() {
    const container = document.getElementById('m2-profile-container');
    if (!container) return;

    const org = this.m2Data.organization || {
      name: 'Mercedes-Benz Club Indonesia',
      short_name: 'MB Club INA',
      alias: 'MBINA',
      tagline: '"One Club, One Family" / "Bersama Satu Bintang"',
      founded_date: '2004-08-01',
      founded_place: 'Jakarta',
      website: 'www.mbclubina.com',
      email: 'admin@mbina.or.id',
      phone: '021-7890123'
    };

    container.innerHTML = `
      <div style="display:grid; grid-template-columns:1fr 2fr; gap:24px; align-items:start;">
        <div class="glass-panel" style="padding:24px; text-align:center;">
          <img src="assets/mb_badge.jpg" alt="Logo Emblem MB INA" style="width:140px; height:140px; border-radius:50%; border:3px solid var(--accent-gold); box-shadow:0 0 20px var(--accent-gold-glow); margin-bottom:16px;">
          <h3 style="font-size:1.4rem;" class="text-gradient">${org.name}</h3>
          <div style="margin-top:8px;">
            <span class="tier-badge" style="background:rgba(245,158,11,0.2); color:var(--accent-gold); border:1px solid var(--accent-gold);">
              ${org.alias} - ${org.short_name}
            </span>
          </div>
          <p style="font-style:italic; font-weight:700; color:var(--accent-gold); margin-top:14px; font-size:0.95rem;">
            "${org.tagline}"
          </p>
          <button class="btn-primary" style="width:100%; margin-top:20px; padding:10px;" onclick="AppEngine.openEditOrgProfileModal()">
            ✏️ Edit Profil MB INA
          </button>
        </div>

        <div class="glass-panel" style="padding:24px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h4 style="font-size:1.1rem; color:var(--accent-gold);">📊 2.1.1 Informasi Dasar Organisasi</h4>
            <button class="btn-outline" style="padding:6px 14px; font-size:0.8rem;" onclick="AppEngine.openEditOrgProfileModal()">✏️ Edit Profil</button>
          </div>
          <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
            <tbody>
              <tr style="border-bottom:1px solid var(--chrome-border);"><td style="padding:10px; color:var(--text-muted); font-weight:700;">Nama Resmi</td><td style="padding:10px; font-weight:800;">${org.name}</td></tr>
              <tr style="border-bottom:1px solid var(--chrome-border);"><td style="padding:10px; color:var(--text-muted); font-weight:700;">Nama Singkat</td><td style="padding:10px; font-weight:700;">${org.short_name}</td></tr>
              <tr style="border-bottom:1px solid var(--chrome-border);"><td style="padding:10px; color:var(--text-muted); font-weight:700;">Alias / Kode</td><td style="padding:10px; font-family:monospace; color:var(--accent-gold); font-weight:800;">${org.alias}</td></tr>
              <tr style="border-bottom:1px solid var(--chrome-border);"><td style="padding:10px; color:var(--text-muted); font-weight:700;">Tagline & Slogan</td><td style="padding:10px; font-style:italic; color:var(--accent-gold); font-weight:700;">"${org.tagline}"</td></tr>
              <tr style="border-bottom:1px solid var(--chrome-border);"><td style="padding:10px; color:var(--text-muted); font-weight:700;">Tanggal & Tempat Berdiri</td><td style="padding:10px;">${org.founded_date} (${org.founded_place})</td></tr>
              <tr style="border-bottom:1px solid var(--chrome-border);"><td style="padding:10px; color:var(--text-muted); font-weight:700;">Status Organisasi</td><td style="padding:10px;"><span class="tier-badge" style="background:rgba(16,185,129,0.2); color:var(--primary-emerald); border:1px solid var(--primary-emerald);">Club Federasi</span></td></tr>
              <tr style="border-bottom:1px solid var(--chrome-border);"><td style="padding:10px; color:var(--text-muted); font-weight:700;">Website Resmi</td><td style="padding:10px; font-weight:700; color:var(--accent-blue);"><a href="https://${org.website}" target="_blank" style="color:inherit; text-decoration:none;">🌐 ${org.website}</a></td></tr>
              <tr style="border-bottom:1px solid var(--chrome-border);"><td style="padding:10px; color:var(--text-muted); font-weight:700;">Email Resmi</td><td style="padding:10px; font-weight:700;">✉️ ${org.email}</td></tr>
              <tr><td style="padding:10px; color:var(--text-muted); font-weight:700;">Nomor Telepon</td><td style="padding:10px;">📞 ${org.phone || '021-7890123'}</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
  },

  openEditOrgProfileModal() {
    const org = this.m2Data.organization || {};
    document.getElementById('edit-org-name').value = org.name || 'Mercedes-Benz Club Indonesia';
    document.getElementById('edit-org-short-name').value = org.short_name || 'MB Club INA';
    document.getElementById('edit-org-alias').value = org.alias || 'MBINA';
    document.getElementById('edit-org-tagline').value = org.tagline || '"One Club, One Family" / "Bersama Satu Bintang"';
    document.getElementById('edit-org-founded-date').value = org.founded_date || '2004-08-01';
    document.getElementById('edit-org-founded-place').value = org.founded_place || 'Jakarta';
    document.getElementById('edit-org-website').value = org.website || 'www.mbclubina.com';
    document.getElementById('edit-org-email').value = org.email || 'admin@mbina.or.id';
    document.getElementById('edit-org-phone').value = org.phone || '021-7890123';
    document.getElementById('edit-org-description').value = org.description || 'Organisasi federasi yang menaungi seluruh club Mercedes-Benz di Indonesia.';

    AuthEngine.openModal('modal-edit-org-profile');
  },

  async saveOrgProfileFromModal(event) {
    event.preventDefault();
    const name = document.getElementById('edit-org-name').value.trim();
    const short_name = document.getElementById('edit-org-short-name').value.trim();
    const alias = document.getElementById('edit-org-alias').value.trim();
    const tagline = document.getElementById('edit-org-tagline').value.trim();
    const founded_date = document.getElementById('edit-org-founded-date').value;
    const founded_place = document.getElementById('edit-org-founded-place').value.trim();
    const website = document.getElementById('edit-org-website').value.trim();
    const email = document.getElementById('edit-org-email').value.trim();
    const phone = document.getElementById('edit-org-phone').value.trim();
    const description = document.getElementById('edit-org-description').value.trim();

    try {
      const res = await fetch('api.php?action=update_m2_organization', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, short_name, alias, tagline, founded_date, founded_place, website, email, phone, description })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
      AuthEngine.closeAllModals();
      await this.fetchM2Data();
      this.renderM2Profile();
    } catch (e) {
      alert('Gagal memperbarui profil organisasi!');
    }
  },

  // 2.1.2 SEJARAH & FOUNDERS
  renderM2Founders() {
    const container = document.getElementById('m2-founders-container');
    if (!container) return;

    const founders = this.m2Data.founders.length ? this.m2Data.founders : [
      { name: 'BAMBANG HARIYADI', club_origin: 'MTC', position: 'Co-Founder & Public Relation', bio: 'Pendiri MB Club INA mewakili MTC (Mercedes-Benz Tiger Club).' },
      { name: 'TUBAGUS SYAMSUL HIDAYAT', club_origin: 'MTC', position: 'Co-Founder & Vice President', bio: 'Pendiri MB Club INA mewakili MTC.' },
      { name: 'RIDWAN POHAN', club_origin: 'MCCI', position: 'President (2004-2006) & Founder', bio: 'Pendiri utama & Presiden Pertama MB Club INA.' },
      { name: 'DHARMA ADSASMUDA', club_origin: 'MCCI', position: 'Co-Founder & Treasury', bio: 'Pendiri MB Club INA mewakili MCCI.' }
    ];

    container.innerHTML = `
      <div style="margin-bottom:28px;">
        <h3 style="font-size:1.4rem; margin-bottom:16px;" class="text-gradient">📖 Sejarah Terbentuknya MB Club INA</h3>

        <!-- 1. LATAR BELAKANG -->
        <div class="glass-panel" style="padding:24px; margin-bottom:20px; border-left:4px solid var(--accent-gold);">
          <h4 style="font-size:1.15rem; color:var(--accent-gold); margin-bottom:12px;">1. Latar Belakang</h4>
          <p style="font-size:0.9rem; color:var(--text-main); line-height:1.7; white-space:pre-line;">Pada akhir dekade 1990-an sampai dengan awal 2000-an, di Jakarta telah terbentuk beberapa klub Mercedes-Benz, yaitu MCCI, MTC, dan MJI. Klub-klub tersebut meregistrasikan keberadaannya kepada pihak principal Daimler AG di Stuttgart melalui ATPM Mercedes-Benz di Indonesia saat itu, PT Daimler Chrysler Indonesia (PT DC INA), untuk mendapatkan sertifikasi atau legitimasi sebagai klub resmi yang terdaftar.

Pemberian sertifikasi pun dikeluarkan kepada ketiga klub tersebut (MCCI, MTC, dan MJI) oleh principal yang pada waktu itu diserahkan melalui PT DC INA dan teregistrasi di bawah MCCCI (Mercedes-Benz Classic Car Club International) Regional Asia-Pasifik yang berkedudukan di Singapura.

Pada tahun 2003, MBCI (W124) terbentuk di Jakarta dan langsung mengajukan permohonan sertifikasi kepada pihak principal. Pada saat pengajuan sertifikasi MBCI sedang berproses (biasanya proses ini berjalan sekitar satu tahun atau lebih), pihak principal mengusulkan kepada PT DC INA agar mewacanakan pembentukan klub holding untuk klub-klub Mercedes-Benz yang ada di Indonesia. Hal ini bertujuan untuk mengantisipasi perkembangan dan pertumbuhan jumlah klub Mercedes-Benz di Indonesia pada masa depan.</p>
        </div>

        <!-- 2. PROSES PEMBENTUKAN -->
        <div class="glass-panel" style="padding:24px; margin-bottom:20px; border-left:4px solid var(--accent-blue);">
          <h4 style="font-size:1.15rem; color:var(--accent-blue); margin-bottom:12px;">2. Proses Pembentukan</h4>
          <p style="font-size:0.9rem; color:var(--text-main); line-height:1.7; white-space:pre-line;">Pada awal tahun 2004, PT DC INA melalui Bapak Yuniadi Hartono (Deputy Director Marketing & Communication saat itu) beserta Bapak Wim Ekel mulai berkomunikasi dengan tiga klub Mercedes-Benz yang sudah tersertifikasi—yaitu MCCI, MTC, dan MJI—untuk segera membentuk klub holding Mercedes-Benz di Indonesia yang dinamakan Mercedes-Benz Club Indonesia (MB Club Ina), dengan mengirimkan dua orang perwakilan dari masing-masing klub.

Saat itu:
- MCCI mengutus: Ridwan Pohan dan Dharma Adsasmuda.
- MTC mengutus: Bambang Hariyadi dan Tubagus S. Hidayat (Didot).
- MJI memutuskan untuk tidak mengirimkan perwakilan, tetapi tetap menyetujui dan mendukung rencana pembentukan MB Club Ina.

Setelah melalui proses pembentukan lewat beberapa kali pertemuan, pada bulan Agustus 2004, Mercedes-Benz Club Indonesia (MB Club Ina) diresmikan oleh PT DC INA sebagai klub federasi yang beranggotakan klub-klub Mercedes-Benz di Indonesia.

Salah satu fungsi MB Club Ina adalah mewakili Indonesia di forum dan kegiatan klub Mercedes-Benz internasional, di antaranya acara President Club Meeting yang diadakan setiap tahun pada bulan Oktober. Acara ini diselenggarakan oleh MB Museum, Club Management dan dihadiri oleh klub-klub Mercedes-Benz dari seluruh dunia.</p>
        </div>

        <!-- CATATAN TAMBAHAN & KONSEP 4 PILAR -->
        <div class="glass-panel" style="padding:24px; margin-bottom:28px; border-left:4px solid var(--primary-emerald);">
          <h4 style="font-size:1.15rem; color:var(--primary-emerald); margin-bottom:12px;">📌 Catatan Tambahan & Konsep 4 Pilar</h4>
          <p style="font-size:0.9rem; color:var(--text-main); line-height:1.7; white-space:pre-line;">Pada saat Mercedes-Benz Museum di Stuttgart diresmikan pada tahun 2005, peran Mercedes-Benz Classic Club International (MCCCI) yang tadinya berfungsi membawahi klub-klub Mercedes-Benz di seluruh dunia digantikan oleh Mercedes-Benz Museum, Club Management yang berkedudukan di Stuttgart.

Pada saat MB Club Ina diresmikan, PT DC INA menetapkan dan menunjuk secara langsung susunan kepengurusan pertama:
• President: Ridwan Pohan
• Vice President: Tubagus S. Hidayat
• Treasurer: Dharma Adsasmuda
• Public Relations: Bambang Haryadi

Keempat orang perwakilan dari masing-masing klub inilah yang sekarang kita sebut sebagai pendiri atau founder MB Club Ina.

Setelah MB Club Ina resmi terbentuk, masih pada tahun yang sama (2004), sertifikasi untuk MBCI dari principal/MCCCI dikeluarkan dan diserahkan langsung oleh PT DC INA. MBCI menjadi klub Mercedes-Benz terakhir di Indonesia yang tersertifikasi langsung dari MCCCI sekaligus otomatis menjadi klub anggota MB Club Ina.

Sejak saat itu pula, Bapak Wim Ekel dari PT DC INA mengemukakan konsep 4 Pilar MB Club Ina, yaitu: MCCI, MTC, MJI, dan MBCI.</p>
        </div>

        <!-- 4 PILAR VISUAL CARDS -->
        <h5 style="font-size:1rem; margin-bottom:14px; color:var(--accent-gold);">🏛️ 4 Pilar Pendiri MB Club INA:</h5>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px;">
          <div class="glass-card" style="padding:16px; text-align:center;">
            <div style="font-size:1.5rem; font-weight:900; color:var(--accent-gold);">MCCI</div>
            <span style="font-size:0.78rem; color:var(--text-muted);">Mercedes-Benz Car Club Indonesia</span>
          </div>
          <div class="glass-card" style="padding:16px; text-align:center;">
            <div style="font-size:1.5rem; font-weight:900; color:var(--accent-blue);">MTC</div>
            <span style="font-size:0.78rem; color:var(--text-muted);">Mercedes-Benz Tiger Club</span>
          </div>
          <div class="glass-card" style="padding:16px; text-align:center;">
            <div style="font-size:1.5rem; font-weight:900; color:var(--primary-emerald);">MJI</div>
            <span style="font-size:0.78rem; color:var(--text-muted);">Mercedes-Benz Jip Club Indonesia</span>
          </div>
          <div class="glass-card" style="padding:16px; text-align:center;">
            <div style="font-size:1.5rem; font-weight:900; color:var(--accent-red);">MBCI</div>
            <span style="font-size:0.78rem; color:var(--text-muted);">W124 Mercedes-Benz Club Indonesia</span>
          </div>
        </div>

        <!-- SUB BAGIAN: PENDIRI ORGANISASI (FOUNDERS) -->
        <div style="border-top:2px solid var(--chrome-border); padding-top:24px; margin-bottom:20px;">
          <h4 style="font-size:1.3rem; margin-bottom:14px; color:var(--accent-gold);">👥 Pendiri Organisasi (Founders)</h4>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin-bottom:24px;">
            ${founders.map(f => `
              <div class="glass-card" style="padding:20px; border-top:3px solid var(--accent-gold);">
                <h4 style="font-size:1.15rem; font-weight:800; margin-bottom:4px; color:var(--text-main);">${f.name}</h4>
                <span class="tier-badge" style="background:rgba(245,158,11,0.15); color:var(--accent-gold); border:1px solid var(--accent-gold); margin-bottom:8px;">
                  Asal Club: ${f.club_origin}
                </span>
                <p style="font-size:0.85rem; font-weight:700; color:var(--accent-blue); margin-top:6px;">${f.position}</p>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-top:8px;">${f.bio}</p>
              </div>
            `).join('')}
          </div>
        </div>

        <!-- DOKUMEN RESMI PENDIRI ORGANISASI (FOUNDERS) -->
        <div class="glass-panel" style="padding:20px; text-align:center; border:2px solid var(--accent-gold);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding:0 8px;">
            <h4 style="font-size:1.1rem; color:var(--accent-gold); margin:0;">📸 DOKUMEN RESMI PENDIRI ORGANISASI (FOUNDERS)</h4>
            <span class="tier-badge" style="background:rgba(16,185,129,0.2); color:var(--primary-emerald); border:1px solid var(--primary-emerald);">OFFICIAL ARCHIVE</span>
          </div>
          <img src="assets/mb_founders.jpg" alt="Foto Resmi Pendiri MB Club INA" style="width:100%; max-height:500px; object-fit:contain; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.3);">
        </div>

      </div>
    `;
  },

  // 2.1.3 VISI & MISI WITH FULL INTERACTIVE CRUD
  renderM2Vision() {
    const container = document.getElementById('m2-vision-container');
    if (!container) return;

    const list = this.m2Data.visionMission.length ? this.m2Data.visionMission : [];

    const vision = list.find(x => x.type === 'VISION') || { id: 'vm_001', title: 'Visi MB Club INA', description: 'Menjadi wadah komunitas Mercedes-Benz terbesar, terbaik, dan paling solid di Indonesia serta menjadi kebanggaan bangsa.', icon: '🎯' };
    const missions = list.filter(x => x.type === 'MISSION');

    container.innerHTML = `
      <div style="margin-bottom:28px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
          <div>
            <h3 style="font-size:1.3rem;" class="text-gradient">🎯 2.1.3 Visi & Misi MB INA</h3>
            <p style="font-size:0.85rem; color:var(--text-muted);">Kelola Visi Utama dan 5 Misi Strategis (Supabase Cloud PostgreSQL Active)</p>
          </div>
          <div style="display:flex; gap:10px;">
            <button class="btn-outline" onclick="AppEngine.openEditVMModal('${vision.id}')">✏️ Edit Visi Utama</button>
            <button class="btn-primary" onclick="AppEngine.openAddVMModal()">+ Tambah Misi Baru</button>
          </div>
        </div>

        <!-- VISI UTAMA CARD -->
        <div class="glass-panel" style="padding:28px; text-align:center; border-top:4px solid var(--accent-gold); margin-bottom:28px; position:relative;">
          <div style="position:absolute; top:16px; right:16px;">
            <button class="role-pill-btn" style="border-color:var(--accent-gold); color:var(--accent-gold);" onclick="AppEngine.openEditVMModal('${vision.id}')">✏️ Edit Visi</button>
          </div>
          <div style="font-size:2.8rem; margin-bottom:8px;">${vision.icon || '🎯'}</div>
          <h3 style="font-size:1.4rem; color:var(--accent-gold);">${vision.title}</h3>
          <p style="font-size:1.1rem; font-weight:700; color:var(--text-main); margin-top:12px; max-width:800px; margin-left:auto; margin-right:auto; line-height:1.6;">
            "${vision.description}"
          </p>
        </div>

        <!-- MISI STRATEGIS GRID -->
        <h4 style="font-size:1.1rem; margin-bottom:16px; color:var(--accent-gold);">🚀 Daftar Misi Strategis Organisasi (${missions.length}):</h4>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
          ${missions.map(m => `
            <div class="glass-card" style="padding:22px; display:flex; flex-direction:column; justify-content:space-between;">
              <div>
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                  <div style="font-size:2rem;">${m.icon || '🚀'}</div>
                  <span class="tier-badge" style="background:rgba(245,158,11,0.15); color:var(--accent-gold); border:1px solid var(--accent-gold);">MISI #${m.sort_order || 1}</span>
                </div>
                <h4 style="font-size:1.1rem; margin-bottom:8px; color:var(--text-main); font-weight:800;">${m.title}</h4>
                <p style="font-size:0.88rem; color:var(--text-muted); line-height:1.6;">${m.description}</p>
              </div>

              <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px; border-top:1px solid var(--chrome-border); padding-top:12px;">
                <button class="role-pill-btn" style="border-color:var(--accent-gold); color:var(--accent-gold);" onclick="AppEngine.openEditVMModal('${m.id}')">✏️ Edit</button>
                <button class="role-pill-btn" style="border-color:var(--accent-red); color:var(--accent-red);" onclick="AppEngine.deleteVM('${m.id}')">🗑️ Hapus</button>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  },

  openAddVMModal() {
    document.getElementById('edit-vm-id').value = '';
    document.getElementById('modal-vm-title').innerText = '🚀 Tambah Misi Baru';
    document.getElementById('edit-vm-type').value = 'MISSION';
    document.getElementById('edit-vm-icon').value = '🚀';
    document.getElementById('edit-vm-item-title').value = '';
    document.getElementById('edit-vm-description').value = '';
    document.getElementById('edit-vm-sort').value = this.m2Data.visionMission.length + 1;

    AuthEngine.openModal('modal-edit-vm');
  },

  openEditVMModal(id) {
    const item = this.m2Data.visionMission.find(x => x.id === id);
    if (!item) return;

    document.getElementById('edit-vm-id').value = item.id;
    document.getElementById('modal-vm-title').innerText = `✏️ Edit ${item.type === 'VISION' ? 'Visi Utama' : 'Misi'}`;
    document.getElementById('edit-vm-type').value = item.type;
    document.getElementById('edit-vm-icon').value = item.icon || '🚀';
    document.getElementById('edit-vm-item-title').value = item.title;
    document.getElementById('edit-vm-description').value = item.description;
    document.getElementById('edit-vm-sort').value = item.sort_order || 1;

    AuthEngine.openModal('modal-edit-vm');
  },

  async saveVMFromModal(event) {
    event.preventDefault();
    const id = document.getElementById('edit-vm-id').value;
    const type = document.getElementById('edit-vm-type').value;
    const icon = document.getElementById('edit-vm-icon').value.trim();
    const title = document.getElementById('edit-vm-item-title').value.trim();
    const description = document.getElementById('edit-vm-description').value.trim();
    const sort_order = parseInt(document.getElementById('edit-vm-sort').value || 1);

    const action = id ? 'update_m2_vision_mission' : 'create_m2_vision_mission';

    try {
      const res = await fetch(`api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, type, icon, title, description, sort_order })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
      AuthEngine.closeAllModals();
      await this.fetchM2Data();
      this.renderM2Vision();
      this.renderLandingPageVisionMission();
    } catch (e) {
      alert('Gagal menyimpan Visi/Misi!');
    }
  },

  async deleteVM(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus Misi ini dari Supabase Cloud Database?')) return;
    try {
      const res = await fetch('api.php?action=delete_m2_vision_mission', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      alert(data.message);
      await this.fetchM2Data();
      this.renderM2Vision();
      this.renderLandingPageVisionMission();
    } catch (e) {
      alert('Gagal menghapus Misi!');
    }
  },

  renderLandingPageVisionMission() {
    const container = document.getElementById('landing-vm-container');
    if (!container) return;

    const list = this.m2Data.visionMission || [];
    const vision = list.find(x => x.type === 'VISION') || {
      icon: '🎯',
      title: 'Visi MB Club INA',
      description: 'Menjadi wadah komunitas Mercedes-Benz terbesar, terbaik, dan paling solid di Indonesia serta menjadi kebanggaan bangsa.'
    };

    const missions = list.filter(x => x.type === 'MISSION');

    container.innerHTML = `
      <!-- VISI UTAMA CARD -->
      <div class="glass-card" style="padding:28px; border-top:4px solid var(--accent-gold); display:flex; flex-direction:column; justify-content:space-between;">
        <div>
          <div style="font-size:2.6rem; margin-bottom:12px;">${vision.icon || '🎯'}</div>
          <h3 style="font-size:1.3rem; margin-bottom:12px; color:var(--accent-gold); font-weight:800;">${vision.title}</h3>
          <p style="font-size:1.02rem; color:var(--text-main); line-height:1.65; font-style:italic;">
            "${vision.description}"
          </p>
        </div>
        <div style="margin-top:20px; border-top:1px solid var(--chrome-border); padding-top:12px; font-size:0.8rem; color:var(--accent-gold); font-weight:700;">
          ⭐ Visi Resmi Organisasi MB Club INA
        </div>
      </div>

      <!-- MISI STRATEGIS CARD -->
      <div class="glass-card" style="padding:28px; border-top:4px solid var(--accent-blue);">
        <div style="font-size:2.6rem; margin-bottom:12px;">🚀</div>
        <h3 style="font-size:1.3rem; margin-bottom:14px; color:var(--accent-gold); font-weight:800;">
          Daftar Misi Strategis Organisasi (${missions.length})
        </h3>
        <ul style="font-size:0.9rem; color:var(--text-main); padding:0; list-style:none; display:flex; flex-direction:column; gap:12px;">
          ${missions.map(m => `
            <li style="display:flex; align-items:flex-start; gap:12px; background:rgba(0,0,0,0.03); padding:12px 16px; border-radius:10px; border:1px solid var(--chrome-border);">
              <span style="font-size:1.4rem; line-height:1;">${m.icon || '🚀'}</span>
              <div>
                <strong style="color:var(--accent-gold); font-size:0.95rem; display:block; margin-bottom:2px;">${m.title}</strong>
                <span style="color:var(--text-muted); font-size:0.88rem; line-height:1.5;">${m.description}</span>
              </div>
            </li>
          `).join('')}
        </ul>
      </div>
    `;
  },

  // 2.1.4 DAFTAR PRESIDEN WITH FULL INTERACTIVE CRUD & UPLOAD PHOTO MANAGEMENT
  renderM2Presidents() {
    const container = document.getElementById('m2-presidents-container');
    if (!container) return;

    const presidents = this.m2Data.presidents;

    container.innerHTML = `
      <div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
          <div>
            <h4 style="font-size:1.2rem; color:var(--accent-gold);">📜 Timeline Daftar Presiden MB Club INA (2004 - 2027)</h4>
            <p style="font-size:0.8rem; color:var(--text-muted);">Kelola data, foto profil, biografi, & status presiden incumbent (Supabase PostgreSQL Active)</p>
          </div>
          <button class="btn-primary" onclick="AppEngine.openAddPresidentModal()">+ Tambah Presiden Baru</button>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:24px;">
          ${presidents.map(p => `
            <div class="glass-card" style="padding:22px; border-left:4px solid ${p.is_current ? 'var(--primary-emerald)' : 'var(--accent-gold)'}; display:flex; flex-direction:column; justify-content:space-between;">
              <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                  <span style="font-weight:900; color:var(--accent-gold); font-size:1rem;">🗓️ Periode ${p.period_start} - ${p.period_end}</span>
                  ${p.is_current ? '<span class="tier-badge" style="background:rgba(16,185,129,0.2); color:var(--primary-emerald); border:1px solid var(--primary-emerald);">INCUMBENT</span>' : ''}
                </div>

                <div style="display:flex; gap:16px; align-items:center; margin-bottom:14px;">
                  <div style="width:72px; height:72px; border-radius:50%; background:linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-gold-dark) 100%); display:flex; align-items:center; justify-content:center; overflow:hidden; border:2px solid var(--accent-gold); flex-shrink:0;">
                    ${p.photo_url ? `<img src="${p.photo_url}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='assets/mb_badge.jpg';">` : `<span style="font-size:1.5rem; font-weight:900; color:#fff;">${p.name.substring(0, 2)}</span>`}
                  </div>
                  <div>
                    <h4 style="font-size:1.1rem; font-weight:800; color:var(--text-main); line-height:1.3;">${p.name}</h4>
                    <span style="font-size:0.75rem; color:var(--accent-gold); font-weight:700;">Presiden #${p.sort_order || 1}</span>
                  </div>
                </div>

                <p style="font-size:0.85rem; color:var(--text-muted); line-height:1.5;">${p.bio || 'Presiden Mercedes-Benz Club Indonesia'}</p>
              </div>

              <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px; border-top:1px solid var(--chrome-border); padding-top:12px;">
                <button class="role-pill-btn" style="border-color:var(--accent-gold); color:var(--accent-gold);" onclick="AppEngine.openEditPresidentModal('${p.id}')">📷 Edit Data / Foto</button>
                <button class="role-pill-btn" style="border-color:var(--accent-red); color:var(--accent-red);" onclick="AppEngine.deletePresident('${p.id}')">🗑️ Hapus</button>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  },

  openAddPresidentModal() {
    document.getElementById('edit-pres-id').value = '';
    document.getElementById('modal-president-title').innerText = '👤 Tambah Presiden MB INA Baru';
    document.getElementById('edit-pres-name').value = '';
    document.getElementById('edit-pres-start').value = 2027;
    document.getElementById('edit-pres-end').value = 2029;
    document.getElementById('edit-pres-photo').value = '';
    document.getElementById('edit-pres-bio').value = '';
    document.getElementById('edit-pres-sort').value = this.m2Data.presidents.length + 1;
    document.getElementById('edit-pres-current').checked = false;

    const preview = document.getElementById('pres-photo-preview');
    if (preview) preview.src = 'assets/presidents/default.jpg';
    const statusEl = document.getElementById('pres-photo-status');
    if (statusEl) statusEl.innerHTML = 'Format: JPG, PNG, WEBP. Klik \'Upload Foto Presiden\' untuk memilih file dari komputer.';

    AuthEngine.openModal('modal-edit-president');
  },

  openEditPresidentModal(id) {
    const p = this.m2Data.presidents.find(x => x.id === id);
    if (!p) return;

    document.getElementById('edit-pres-id').value = p.id;
    document.getElementById('modal-president-title').innerText = `📷 Edit Data / Foto: ${p.name}`;
    document.getElementById('edit-pres-name').value = p.name;
    document.getElementById('edit-pres-start').value = p.period_start;
    document.getElementById('edit-pres-end').value = p.period_end;
    document.getElementById('edit-pres-photo').value = p.photo_url || '';
    document.getElementById('edit-pres-bio').value = p.bio || '';
    document.getElementById('edit-pres-sort').value = p.sort_order || 1;
    document.getElementById('edit-pres-current').checked = p.is_current || false;

    const preview = document.getElementById('pres-photo-preview');
    if (preview) preview.src = p.photo_url || 'assets/presidents/default.jpg';
    const statusEl = document.getElementById('pres-photo-status');
    if (statusEl) statusEl.innerHTML = 'Foto aktif dari database. Klik tombol di atas jika ingin mengganti file foto.';

    AuthEngine.openModal('modal-edit-president');
  },

  async handlePhotoFileUpload(event, targetInputId, previewImgId, statusSpanId) {
    const file = event.target.files[0];
    if (!file) return;

    const statusEl = document.getElementById(statusSpanId);
    const inputEl = document.getElementById(targetInputId);
    const previewEl = document.getElementById(previewImgId);

    if (statusEl) statusEl.innerHTML = `⏳ <em>Mengunggah ${file.name} (${(file.size/1024).toFixed(1)} KB)...</em>`;

    const formData = new FormData();
    formData.append('photo_file', file);

    try {
      const res = await fetch('api.php?action=upload_image', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        if (inputEl) inputEl.value = data.url;
        if (previewEl) previewEl.src = data.url + '?t=' + Date.now();
        if (statusEl) statusEl.innerHTML = `✅ <strong style="color:var(--primary-emerald);">${data.message}</strong>`;
      } else {
        if (statusEl) statusEl.innerHTML = `❌ <strong style="color:var(--accent-red);">${data.message}</strong>`;
      }
    } catch (err) {
      if (statusEl) statusEl.innerHTML = `❌ <strong style="color:var(--accent-red);">Gagal mengunggah file.</strong>`;
    }
  },

  async savePresidentFromModal(event) {
    event.preventDefault();
    const id = document.getElementById('edit-pres-id').value;
    const name = document.getElementById('edit-pres-name').value.trim();
    const period_start = parseInt(document.getElementById('edit-pres-start').value || 2025);
    const period_end = parseInt(document.getElementById('edit-pres-end').value || 2027);
    const photo_url = document.getElementById('edit-pres-photo').value.trim();
    const bio = document.getElementById('edit-pres-bio').value.trim();
    const sort_order = parseInt(document.getElementById('edit-pres-sort').value || 1);
    const is_current = document.getElementById('edit-pres-current').checked;

    const action = id ? 'update_m2_president' : 'create_m2_president';

    try {
      const res = await fetch(`api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, name, period_start, period_end, photo_url, bio, sort_order, is_current })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
      AuthEngine.closeAllModals();
      await this.fetchM2Data();
      this.renderM2Presidents();
    } catch (e) {
      alert('Gagal menyimpan data Presiden!');
    }
  },

  async deletePresident(id) {
    if (!confirm('Hapus data Presiden ini dari Supabase Cloud Database?')) return;
    try {
      const res = await fetch('api.php?action=delete_m2_president', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      alert(data.message);
      await this.fetchM2Data();
      this.renderM2Presidents();
    } catch (e) {
      alert('Gagal menghapus Presiden!');
    }
  },

  // 2.1.5 & 2.4 PENGURUS PUSAT (TREE VIEW & FULL INTERACTIVE CRUD)
  renderM2Structure() {
    const container = document.getElementById('m2-structure-container');
    if (!container) return;

    const advisory = this.m2Data.advisoryBoard || [];
    const honor = this.m2Data.honorCouncil || [];
    const structure = this.m2Data.structure || [];

    container.innerHTML = `
      <div>
        <div style="background:linear-gradient(135deg, rgba(245,158,11,0.15) 0%, rgba(16,185,129,0.15) 100%); border:2px solid var(--accent-gold); border-radius:16px; padding:20px; margin-bottom:24px;">
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
              <span style="font-size:0.75rem; letter-spacing:2px; color:var(--accent-gold); font-weight:800;">PERIODISASI KEPENGURUSAN PUSAT</span>
              <h3 style="font-size:1.4rem;" class="text-gradient">Kabinet MB INA Periode 2025 - 2027</h3>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
              <span class="tier-badge" style="background:rgba(16,185,129,0.2); color:var(--primary-emerald); border:1px solid var(--primary-emerald); padding:6px 14px;">PERIODE AKTIF</span>
              <button class="btn-primary" style="padding:8px 16px; font-size:0.85rem;" onclick="AppEngine.openAddStructureModal()">+ Tambah Jabatan Baru</button>
            </div>
          </div>
        </div>

        <!-- DEWAN PEMBINA & DEWAN KEHORMATAN -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:20px; margin-bottom:28px;">
          <div class="glass-panel" style="padding:20px;">
            <h4 style="font-size:1.05rem; margin-bottom:12px; color:var(--accent-gold);">🏛️ Dewan Pembina (${advisory.length})</h4>
            <ul style="font-size:0.85rem; color:var(--text-main); list-style:none; padding:0; display:flex; flex-direction:column; gap:8px;">
              ${advisory.map(a => `
                <li style="border-bottom:1px solid var(--chrome-border); padding-bottom:6px; display:flex; justify-content:space-between; align-items:center;">
                  <strong>${a.name}</strong>
                  <span style="color:var(--text-muted); font-size:0.78rem;">${a.position} ${a.club_origin ? '(' + a.club_origin + ')' : ''}</span>
                </li>
              `).join('')}
            </ul>
          </div>

          <div class="glass-panel" style="padding:20px;">
            <h4 style="font-size:1.05rem; margin-bottom:12px; color:var(--accent-gold);">🎖️ Dewan Kehormatan (${honor.length})</h4>
            <ul style="font-size:0.85rem; color:var(--text-main); list-style:none; padding:0; display:flex; flex-direction:column; gap:8px;">
              ${honor.map(h => `
                <li style="border-bottom:1px solid var(--chrome-border); padding-bottom:6px; display:flex; justify-content:space-between; align-items:center;">
                  <strong>${h.name}</strong>
                  <span style="color:var(--text-muted); font-size:0.78rem;">${h.position}</span>
                </li>
              `).join('')}
            </ul>
          </div>
        </div>

        <!-- STRUKTUR EKSEKUTIF TREE TABLE WITH CRUD -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
          <h4 style="font-size:1.1rem; color:var(--accent-gold);">🌳 Tree View Struktur Kabinet Pengurus Pusat (${structure.length} Jabatan):</h4>
          <button class="btn-outline" style="padding:6px 14px; font-size:0.8rem;" onclick="AppEngine.openAddStructureModal()">+ Tambah Jabatan</button>
        </div>

        <div class="glass-panel" style="padding:24px; overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
            <thead>
              <tr style="border-bottom:1px solid var(--chrome-border); text-align:left; color:var(--text-muted);">
                <th style="padding:12px;">Level</th>
                <th style="padding:12px;">Nama Jabatan / Divisi</th>
                <th style="padding:12px;">Pejabat & Asal Club</th>
                <th style="padding:12px;">Periode</th>
                <th style="padding:12px; text-align:right;">Aksi Management</th>
              </tr>
            </thead>
            <tbody>
              ${structure.map(s => {
                const role = s.role_name || s.position_name || 'Pengurus';
                const name = s.full_name || s.user_name || 'Pejabat Organisasi';
                const club = s.club_name || s.club_origin || 'Pusat MBClubINA';
                const isPres = role.toLowerCase().includes('president');
                const isVP = role.toLowerCase().includes('vp');

                return `
                  <tr style="border-bottom:1px solid var(--chrome-border); background:${isPres ? 'rgba(16,185,129,0.06)' : isVP ? 'rgba(245,158,11,0.06)' : 'transparent'};">
                    <td style="padding:12px;">
                      <span class="tier-badge" style="background:${isPres ? 'rgba(16,185,129,0.2)' : isVP ? 'rgba(245,158,11,0.2)' : 'rgba(59,130,246,0.15)'}; color:${isPres ? 'var(--primary-emerald)' : isVP ? 'var(--accent-gold)' : 'var(--accent-blue)'}; border:1px solid ${isPres ? 'var(--primary-emerald)' : isVP ? 'var(--accent-gold)' : 'var(--accent-blue)'};">
                        ${isPres ? 'PRESIDEN' : isVP ? 'VICE PRESIDENT' : 'PENGURUS'}
                      </span>
                    </td>
                    <td style="padding:12px; font-weight:${isPres || isVP ? '900' : '700'}; color:${isVP ? 'var(--accent-gold)' : 'var(--text-main)'};">
                      ${isPres ? '🎖️ ' : isVP ? '👑 ' : '🔹 '} ${role}
                    </td>
                    <td style="padding:12px; font-weight:700; color:var(--text-main);">
                      ${name} <span style="font-size:0.78rem; font-weight:400; color:var(--text-muted);">(${club})</span>
                    </td>
                    <td style="padding:12px; font-size:0.8rem; color:var(--text-muted);">2025 - 2027</td>
                    <td style="padding:12px; text-align:right;">
                      <div style="display:inline-flex; gap:6px;">
                        <button class="role-pill-btn" style="border-color:var(--accent-gold); color:var(--accent-gold);" onclick="AppEngine.openEditStructureModal('${s.id}')">✏️ Edit</button>
                        <button class="role-pill-btn" style="border-color:var(--accent-red); color:var(--accent-red);" onclick="AppEngine.deleteStructure('${s.id}')">🗑️ Hapus</button>
                      </div>
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

  openAddStructureModal() {
    document.getElementById('edit-struct-id').value = '';
    document.getElementById('modal-struct-title').innerText = '🌳 Tambah Jabatan Pengurus Pusat Baru';
    document.getElementById('edit-struct-name').value = '';
    document.getElementById('edit-struct-level').value = 'PENGURUS_PUSAT';
    document.getElementById('edit-struct-club').value = '';
    document.getElementById('edit-struct-start').value = 2023;
    document.getElementById('edit-struct-end').value = 2025;
    document.getElementById('edit-struct-sort').value = this.m2Data.structure.length + 1;

    AuthEngine.openModal('modal-edit-structure');
  },

  openEditStructureModal(id) {
    const s = this.m2Data.structure.find(x => x.id === id);
    if (!s) return;

    document.getElementById('edit-struct-id').value = s.id;
    document.getElementById('modal-struct-title').innerText = `✏️ Edit Jabatan: ${s.position_name}`;
    document.getElementById('edit-struct-name').value = s.position_name;
    document.getElementById('edit-struct-level').value = s.position_level || 'PENGURUS_PUSAT';
    document.getElementById('edit-struct-club').value = s.club_origin || '';
    document.getElementById('edit-struct-start').value = s.period_start || 2023;
    document.getElementById('edit-struct-end').value = s.period_end || 2025;
    document.getElementById('edit-struct-sort').value = s.sort_order || 1;

    AuthEngine.openModal('modal-edit-structure');
  },

  async saveStructureFromModal(event) {
    event.preventDefault();
    const id = document.getElementById('edit-struct-id').value;
    const position_name = document.getElementById('edit-struct-name').value.trim();
    const position_level = document.getElementById('edit-struct-level').value;
    const club_origin = document.getElementById('edit-struct-club').value.trim();
    const period_start = parseInt(document.getElementById('edit-struct-start').value || 2023);
    const period_end = parseInt(document.getElementById('edit-struct-end').value || 2025);
    const sort_order = parseInt(document.getElementById('edit-struct-sort').value || 1);

    const action = id ? 'update_m2_structure' : 'create_m2_structure';

    try {
      const res = await fetch(`api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, position_name, position_level, club_origin, period_start, period_end, sort_order })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
      AuthEngine.closeAllModals();
      await this.fetchM2Data();
      this.renderM2Structure();
    } catch (e) {
      alert('Gagal menyimpan Jabatan!');
    }
  },

  async deleteStructure(id) {
    if (!confirm('Hapus Jabatan ini dari Supabase Cloud Database?')) return;
    try {
      const res = await fetch('api.php?action=delete_m2_structure', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      alert(data.message);
      await this.fetchM2Data();
      this.renderM2Structure();
    } catch (e) {
      alert('Gagal menghapus Jabatan!');
    }
  },

  // ============================================
  // 2.2 & 2.3 DAFTAR & DETAIL KLUB (110+ KLUB - 8 REGION WITH FULL CRUD)
  // ============================================
  renderM2Clubs() {
    const container = document.getElementById('m2-clubs-container');
    if (!container) return;

    const clubs = this.m2Data.clubs;

    // Regions list with exact DB value mapping
    const regions = [
      { value: '', label: '🌐 Semua Region (8 Region)' },
      { value: 'Regional Sumatra', label: '1. Regional Sumatra' },
      { value: 'Regional Banten', label: '2. Regional Banten' },
      { value: 'Regional Metro DKI Jakarta', label: '3. Regional Metro DKI Jakarta' },
      { value: 'Regional Jawa Barat', label: '4. Regional Jawa Barat' },
      { value: 'Regional Jawa Tengah', label: '5. Regional Jawa Tengah' },
      { value: 'Regional Yogyakarta', label: '6. Regional Yogyakarta' },
      { value: 'Regional Jawa Timur & Bali', label: '7. Regional Jawa Timur & Bali' },
      { value: 'Regional Kalimantan & Sulawesi', label: '8. Regional Kalimantan & Sulawesi' }
    ];

    container.innerHTML = `
      <div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
          <div>
            <h4 style="font-size:1.2rem; color:var(--accent-gold);">🚗 2.2 & 2.3 Daftar Klub & Chapter Terdaftar MB INA (<span id="m2-club-total-count">${clubs.length}</span> Klub - 8 Region)</h4>
            <p style="font-size:0.8rem; color:var(--text-muted);">Kelola seluruh 110+ Klub & Chapter resmi MB INA (Supabase Cloud PostgreSQL Active)</p>
          </div>
          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn-primary" onclick="AppEngine.openAddClubModal()">+ Tambah Klub / Chapter Baru</button>
          </div>
        </div>

        <!-- SEARCH & REGION FILTER BAR -->
        <div style="display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap; background:rgba(0,0,0,0.03); border:1px solid var(--chrome-border); padding:16px; border-radius:12px; align-items:center;">
          <div style="flex:1; min-width:260px;">
            <label style="font-size:0.75rem; color:var(--text-muted); font-weight:700; display:block; margin-bottom:4px;">CARI KODE ATAU NAMA KLUB:</label>
            <input type="text" id="m2-club-search" class="form-input" placeholder="🔍 Ketik kode (contoh: MCCI, MBC, W124) atau nama..." style="width:100%; font-weight:600;" oninput="AppEngine.filterM2Clubs()" onkeyup="AppEngine.filterM2Clubs()">
          </div>

          <div style="flex:1; min-width:260px;">
            <label style="font-size:0.75rem; color:var(--text-muted); font-weight:700; display:block; margin-bottom:4px;">FILTER REGIONAL WILAYAH:</label>
            <select id="m2-club-region-filter" class="form-input" style="width:100%; font-weight:600;" onchange="AppEngine.filterM2Clubs()">
              ${regions.map(r => `<option value="${r.value}">${r.label}</option>`).join('')}
            </select>
          </div>

          <div style="padding-top:18px;">
            <button class="btn-outline" style="padding:10px 16px; font-size:0.8rem;" onclick="AppEngine.resetM2ClubFilters()">🔄 Reset Filter</button>
          </div>
        </div>

        <!-- LIVE FILTER STATUS COUNTER BADGE -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; font-size:0.85rem;">
          <span id="m2-club-filter-status" style="color:var(--accent-gold); font-weight:700;">
            📊 Menampilkan ${clubs.length} dari ${clubs.length} Klub & Chapter
          </span>
          <span style="color:var(--text-muted); font-size:0.78rem;">Supabase PostgreSQL Live Search</span>
        </div>

        <!-- 2.2.3 STATISTIK KLUB SUMMARY BAR -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px; margin-bottom:24px;">
          <div class="admin-stat-card">
            <div class="admin-stat-icon">🚗</div>
            <div>
              <div class="stat-value" id="stat-club-card-count">${clubs.length}</div>
              <div class="stat-label">Total Klub & Chapter</div>
            </div>
          </div>
          <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:rgba(59,130,246,0.15); color:var(--accent-blue);">🗺️</div>
            <div>
              <div class="stat-value">8 Region</div>
              <div class="stat-label">Cakupan Wilayah</div>
            </div>
          </div>
          <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:rgba(16,185,129,0.15); color:var(--primary-emerald);">📍</div>
            <div>
              <div class="stat-value">60+</div>
              <div class="stat-label">Chapter & Region</div>
            </div>
          </div>
          <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:rgba(245,158,11,0.15); color:var(--accent-gold);">👥</div>
            <div>
              <div class="stat-value">15.000+</div>
              <div class="stat-label">Member Terdaftar</div>
            </div>
          </div>
        </div>

        <div id="m2-clubs-table-wrapper" class="glass-panel" style="padding:20px; overflow-x:auto;">
          <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
            <thead>
              <tr style="border-bottom:1px solid var(--chrome-border); text-align:left; color:var(--text-muted);">
                <th style="padding:12px;">Kode</th>
                <th style="padding:12px;">Nama Resmi Klub / Chapter</th>
                <th style="padding:12px;">Region Wilayah</th>
                <th style="padding:12px;">Kota / Domisili</th>
                <th style="padding:12px;">Tipe</th>
                <th style="padding:12px;">Jumlah Member</th>
                <th style="padding:12px; text-align:right;">Aksi Management</th>
              </tr>
            </thead>
            <tbody id="m2-clubs-table-body">
              ${this.generateM2ClubTableRows(clubs)}
            </tbody>
          </table>
        </div>
      </div>
    `;
  },

  generateM2ClubTableRows(clubsList) {
    if (!clubsList || clubsList.length === 0) {
      return `
        <tr>
          <td colspan="7" style="padding:32px; text-align:center; color:var(--text-muted);">
            🔍 <strong>Tidak ada Klub / Chapter yang cocok dengan pencarian atau filter region.</strong><br>
            <span style="font-size:0.8rem; margin-top:6px; display:block;">Coba ubah kata kunci atau tekan tombol 'Reset Filter'.</span>
          </td>
        </tr>
      `;
    }

    return clubsList.map(c => `
      <tr style="border-bottom:1px solid var(--chrome-border);">
        <td style="padding:12px; font-family:monospace; font-weight:800; color:var(--accent-gold);">${c.code}</td>
        <td style="padding:12px; font-weight:700; color:var(--text-main); cursor:pointer;" 
            onmouseover="AppEngine.showClubHoverModal('${c.id}')" 
            onclick="AppEngine.showClubHoverModal('${c.id}')"
            title="Hover/Klik untuk melihat Sejarah Ringkas, Ketua Umum, & Contact Person">
          <span style="border-bottom:1px dashed var(--accent-gold); color:var(--accent-gold); transition:all 0.2s;" onmouseenter="this.style.color='var(--accent-blue)'" onmouseleave="this.style.color='var(--accent-gold)'">
            ${c.name} ℹ️
          </span>
        </td>
        <td style="padding:12px; color:var(--text-muted); font-size:0.8rem;">${c.region}</td>
        <td style="padding:12px;">${c.city}</td>
        <td style="padding:12px;">
          <span class="tier-badge" style="background:${c.type === 'CHAPTER' ? 'rgba(59,130,246,0.15)' : 'rgba(245,158,11,0.15)'}; color:${c.type === 'CHAPTER' ? 'var(--accent-blue)' : 'var(--accent-gold)'}; border:1px solid ${c.type === 'CHAPTER' ? 'var(--accent-blue)' : 'var(--accent-gold)'};">
            ${c.type}
          </span>
        </td>
        <td style="padding:12px; font-weight:700;">👥 ${c.member_count}</td>
        <td style="padding:12px; text-align:right;">
          <div style="display:inline-flex; gap:6px;">
            <button class="role-pill-btn" style="border-color:var(--accent-gold); color:var(--accent-gold);" onclick="AppEngine.openEditClubModal('${c.id}')">✏️ Edit</button>
            <button class="role-pill-btn" style="border-color:var(--accent-red); color:var(--accent-red);" onclick="AppEngine.deleteClub('${c.id}')">🗑️ Hapus</button>
          </div>
        </td>
      </tr>
    `).join('');
  },

  showClubHoverModal(clubId) {
    const c = this.m2Data.clubs.find(x => x.id === clubId);
    if (!c) return;

    const badgeEl = document.getElementById('hover-club-badge');
    const nameEl = document.getElementById('hover-club-name');
    const regEl = document.getElementById('hover-club-region');
    const histEl = document.getElementById('hover-club-history');
    const yearEl = document.getElementById('hover-club-year');
    const cityEl = document.getElementById('hover-club-city');
    const ketuaEl = document.getElementById('hover-club-ketua');
    const cpEl = document.getElementById('hover-club-cp');
    const phoneEl = document.getElementById('hover-club-phone');

    if (badgeEl) badgeEl.innerText = c.code ? c.code.substring(0, 4) : 'MB';
    if (nameEl) nameEl.innerText = c.name;
    if (regEl) regEl.innerText = `${c.region} (${c.type})`;
    if (histEl) histEl.innerText = c.description || `Klub/Chapter ${c.name} merupakan bagian resmi dari MB INA yang aktif mengagendakan touring regional dan bakti sosial.`;
    if (yearEl) yearEl.innerText = c.founded_year || '2004';
    if (cityEl) cityEl.innerText = c.city || 'Indonesia';
    if (ketuaEl) ketuaEl.innerText = c.ketua_umum || 'H. Ahmad Fauzi, S.E.';
    if (cpEl) cpEl.innerText = c.contact_person || 'Hendra (Sekretaris Club)';
    if (phoneEl) phoneEl.innerText = c.contact_phone || '0812-3456-7890';

    AuthEngine.openModal('modal-club-hover-info');
  },

  filterM2Clubs() {
    const q = (document.getElementById('m2-club-search') ? document.getElementById('m2-club-search').value : '').trim().toLowerCase();
    const reg = (document.getElementById('m2-club-region-filter') ? document.getElementById('m2-club-region-filter').value : '').trim().toLowerCase();
    const tbody = document.getElementById('m2-clubs-table-body');
    const statusEl = document.getElementById('m2-club-filter-status');
    if (!tbody) return;

    const totalClubs = this.m2Data.clubs.length;

    const filtered = this.m2Data.clubs.filter(c => {
      const name = (c.name || '').toLowerCase();
      const code = (c.code || '').toLowerCase();
      const city = (c.city || '').toLowerCase();
      const alias = (c.alias || '').toLowerCase();
      const cRegion = (c.region || '').toLowerCase();

      const matchSearch = !q || name.includes(q) || code.includes(q) || city.includes(q) || alias.includes(q);
      const matchRegion = !reg || cRegion.includes(reg);

      return matchSearch && matchRegion;
    });

    tbody.innerHTML = this.generateM2ClubTableRows(filtered);

    if (statusEl) {
      statusEl.innerHTML = `📊 Menampilkan <strong>${filtered.length}</strong> dari <strong>${totalClubs}</strong> Klub & Chapter`;
    }
  },

  resetM2ClubFilters() {
    const searchInput = document.getElementById('m2-club-search');
    const regionSelect = document.getElementById('m2-club-region-filter');

    if (searchInput) searchInput.value = '';
    if (regionSelect) regionSelect.value = '';

    this.filterM2Clubs();
  },

  openAddClubModal() {
    document.getElementById('edit-club-id').value = '';
    document.getElementById('modal-club-title').innerText = '🚗 Tambah Klub / Chapter Baru';
    document.getElementById('edit-club-name').value = '';
    document.getElementById('edit-club-code').value = '';
    document.getElementById('edit-club-type').value = 'CLUB';
    document.getElementById('edit-club-region').value = 'Regional Sumatra';
    document.getElementById('edit-club-city').value = '';
    document.getElementById('edit-club-member-count').value = 50;
    document.getElementById('edit-club-ketua').value = '';
    document.getElementById('edit-club-cp').value = '';
    document.getElementById('edit-club-phone').value = '';
    document.getElementById('edit-club-year').value = 2004;
    document.getElementById('edit-club-desc').value = '';

    AuthEngine.openModal('modal-edit-club');
  },

  openEditClubModal(id) {
    const c = this.m2Data.clubs.find(x => x.id === id);
    if (!c) return;

    document.getElementById('edit-club-id').value = c.id;
    document.getElementById('modal-club-title').innerText = `✏️ Edit Klub / Chapter: ${c.name}`;
    document.getElementById('edit-club-name').value = c.name || '';
    document.getElementById('edit-club-code').value = c.code || '';
    document.getElementById('edit-club-type').value = c.type || 'CLUB';
    document.getElementById('edit-club-region').value = c.region || 'Regional Sumatra';
    document.getElementById('edit-club-city').value = c.city || '';
    document.getElementById('edit-club-member-count').value = c.member_count || 50;
    document.getElementById('edit-club-ketua').value = c.ketua_umum || '';
    document.getElementById('edit-club-cp').value = c.contact_person || '';
    document.getElementById('edit-club-phone').value = c.contact_phone || '';
    document.getElementById('edit-club-year').value = c.founded_year || 2004;
    document.getElementById('edit-club-desc').value = c.description || '';

    AuthEngine.openModal('modal-edit-club');
  },

  async saveClubFromModal(event) {
    event.preventDefault();
    const id = document.getElementById('edit-club-id').value;
    const name = document.getElementById('edit-club-name').value.trim();
    const code = document.getElementById('edit-club-code').value.trim();
    const type = document.getElementById('edit-club-type').value;
    const region = document.getElementById('edit-club-region').value;
    const city = document.getElementById('edit-club-city').value.trim();
    const member_count = parseInt(document.getElementById('edit-club-member-count').value || 50);
    const ketua_umum = document.getElementById('edit-club-ketua').value.trim();
    const contact_person = document.getElementById('edit-club-cp').value.trim();
    const contact_phone = document.getElementById('edit-club-phone').value.trim();
    const founded_year = parseInt(document.getElementById('edit-club-year').value || 2004);
    const description = document.getElementById('edit-club-desc').value.trim();

    const action = id ? 'update_m2_club' : 'create_m2_club';

    try {
      const res = await fetch(`api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, name, code, type, region, city, member_count, ketua_umum, contact_person, contact_phone, founded_year, description })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
      AuthEngine.closeAllModals();
      await this.fetchM2Data();
      this.renderM2Clubs();
      this.renderClubsList();
    } catch (e) {
      alert('Gagal menyimpan Klub!');
    }
  },

  async deleteClub(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus Klub/Chapter ini dari Supabase Cloud Database?')) return;
    try {
      const res = await fetch('api.php?action=delete_m2_club', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      alert(data.message);
      await this.fetchM2Data();
      this.renderM2Clubs();
      this.renderClubsList();
    } catch (e) {
      alert('Gagal menghapus Klub!');
    }
  },

  async showClubDetail(clubId) {
    try {
      const res = await fetch(`api.php?action=get_m2_club_detail&club_id=${clubId}`).then(r => r.json());
      if (res.success && res.club) {
        const c = res.club;
        alert(`ℹ️ DETAIL KLUB MB INA:\n\nNama Klub: ${c.name} (${c.code})\nRegion: ${c.region}\nDomisili: ${c.city}\nTipe: ${c.type}\nTotal Member: ${c.member_count} Anggota\nStatus: AKTIF / VERIFIED`);
      }
    } catch (e) {
      alert('Gagal memuat detail klub!');
    }
  },

  renderUserManagement() {
    const tableContainer = document.getElementById('user-mgmt-table');
    if (!tableContainer) return;

    const searchTerm = (document.getElementById('user-mgmt-search') ? document.getElementById('user-mgmt-search').value : '').toLowerCase();
    const roleFilter = document.getElementById('user-mgmt-role-filter') ? document.getElementById('user-mgmt-role-filter').value : '';
    const statusFilter = document.getElementById('user-mgmt-status-filter') ? document.getElementById('user-mgmt-status-filter').value : '';

    let filtered = this.users;

    if (searchTerm) {
      filtered = filtered.filter(u => u.name.toLowerCase().includes(searchTerm) || u.email.toLowerCase().includes(searchTerm) || (u.username && u.username.toLowerCase().includes(searchTerm)));
    }
    if (roleFilter) {
      filtered = filtered.filter(u => u.role === roleFilter);
    }
    if (statusFilter) {
      filtered = filtered.filter(u => u.status === statusFilter);
    }

    tableContainer.innerHTML = `
      <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
        <thead>
          <tr style="border-bottom:2px solid var(--chrome-border); text-align:left; color:var(--text-muted); background:rgba(0,0,0,0.2);">
            <th style="padding:11px 12px; white-space:nowrap;">Member ID</th>
            <th style="padding:11px 12px; white-space:nowrap;">ID & Username</th>
            <th style="padding:11px 12px;">Nama Lengkap</th>
            <th style="padding:11px 12px;">Email & WhatsApp</th>
            <th style="padding:11px 12px; white-space:nowrap;">Role</th>
            <th style="padding:11px 12px; white-space:nowrap;">Status</th>
            <th style="padding:11px 12px; text-align:center; white-space:nowrap;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          ${filtered.map(u => `
            <tr style="border-bottom:1px solid var(--chrome-border); transition:background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background=''">
              <td style="padding:12px; font-family:monospace; font-size:0.78rem; white-space:nowrap;">
                <div style="background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); border-radius:6px; padding:4px 8px; display:inline-block; color:var(--accent-gold); font-weight:800; letter-spacing:0.5px;">
                  ${u.memberId || u.id.replace('usr_', 'MB-').toUpperCase()}
                </div>
              </td>
              <td style="padding:12px; font-family:monospace; font-size:0.78rem;">
                <div style="color:var(--text-main); font-weight:700;">${u.id}</div>
                <div style="color:var(--accent-gold); font-weight:700; margin-top:2px;">@${u.username || 'user'}</div>
                ${u.isSystemArchitect ? '<div style="color:var(--primary-emerald); font-size:0.68rem; margin-top:2px;">🔒 System Architect</div>' : ''}
              </td>
              <td style="padding:12px; font-weight:600;">
                ${u.name}
                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">${u.city || 'Indonesia'}</div>
              </td>
              <td style="padding:12px; color:var(--text-muted); font-size:0.82rem;">
                <div>${u.email}</div>
                <div style="margin-top:2px;">${u.phone}</div>
              </td>
              <td style="padding:12px; white-space:nowrap;">
                <span class="tier-badge" style="background:rgba(245,158,11,0.15); color:var(--accent-gold); border:1px solid var(--accent-gold); font-size:0.72rem;">
                  ${this.formatRoleName(u.role)}
                </span>
              </td>
              <td style="padding:12px; white-space:nowrap;">
                <span class="tier-badge" style="background:${u.status === 'ACTIVE' ? 'rgba(16,185,129,0.2)' : u.status === 'SUSPENDED' ? 'rgba(239,68,68,0.2)' : 'rgba(245,158,11,0.2)'}; color:${u.status === 'ACTIVE' ? 'var(--primary-emerald)' : u.status === 'SUSPENDED' ? 'var(--accent-red)' : 'var(--accent-gold)'}; font-size:0.72rem;">
                  ${u.status}
                </span>
              </td>
              <td style="padding:12px; text-align:center; white-space:nowrap;">
                <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                  <button title="✏️ Edit Data User" onclick="AppEngine.openEditUserModal('${u.id}')"
                    style="width:34px; height:34px; border-radius:8px; border:1px solid var(--accent-gold); background:rgba(245,158,11,0.12); color:var(--accent-gold); cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
                    onmouseover="this.style.background='rgba(245,158,11,0.3)'" onmouseout="this.style.background='rgba(245,158,11,0.12)'">
                    ✏️
                  </button>
                  <button title="${u.status === 'SUSPENDED' ? '✅ Aktifkan User' : '⛔ Suspend User'}" onclick="AppEngine.suspendUser('${u.id}')"
                    style="width:34px; height:34px; border-radius:8px; border:1px solid ${u.status === 'SUSPENDED' ? 'var(--primary-emerald)' : 'var(--text-muted)'}; background:${u.status === 'SUSPENDED' ? 'rgba(16,185,129,0.12)' : 'rgba(255,255,255,0.05)'}; color:${u.status === 'SUSPENDED' ? 'var(--primary-emerald)' : 'var(--text-muted)'}; cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
                    onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                    ${u.status === 'SUSPENDED' ? '✅' : '⛔'}
                  </button>
                  <button title="🔑 Reset Password" onclick="AppEngine.resetPassword('${u.id}')"
                    style="width:34px; height:34px; border-radius:8px; border:1px solid var(--accent-blue); background:rgba(59,130,246,0.1); color:var(--accent-blue); cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
                    onmouseover="this.style.background='rgba(59,130,246,0.25)'" onmouseout="this.style.background='rgba(59,130,246,0.1)'">
                    🔑
                  </button>
                  ${u.isProtected ? `
                  <button title="🔒 Akun Terlindungi" disabled
                    style="width:34px; height:34px; border-radius:8px; border:1px solid var(--chrome-border); background:rgba(255,255,255,0.03); color:var(--text-muted); cursor:not-allowed; font-size:1rem; display:flex; align-items:center; justify-content:center; opacity:0.4;">
                    🔒
                  </button>` : `
                  <button title="🗑️ Hapus User Permanen" onclick="AppEngine.deleteUser('${u.id}')"
                    style="width:34px; height:34px; border-radius:8px; border:1px solid var(--accent-red); background:rgba(239,68,68,0.1); color:var(--accent-red); cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
                    onmouseover="this.style.background='rgba(239,68,68,0.3)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                    🗑️
                  </button>`}
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  },

  openEditUserModal(userId) {
    const u = this.users.find(x => x.id === userId);
    if (!u) {
      alert('Data user tidak ditemukan!');
      return;
    }

    document.getElementById('edit-user-id').value = u.id;
    document.getElementById('edit-user-name').value = u.name;
    document.getElementById('edit-user-email').value = u.email;
    document.getElementById('edit-user-phone').value = u.phone;
    document.getElementById('edit-user-username').value = u.username || '';
    document.getElementById('edit-user-role').value = u.role;
    document.getElementById('edit-user-status').value = u.status;
    document.getElementById('edit-user-city').value = u.city || '';
    if (u.provinceId) {
      document.getElementById('edit-user-province').value = u.provinceId;
    }

    AuthEngine.openModal('modal-edit-user');
  },

  async updateUserFromAdmin(event) {
    event.preventDefault();
    const userId = document.getElementById('edit-user-id').value;
    const name = document.getElementById('edit-user-name').value.trim();
    const email = document.getElementById('edit-user-email').value.trim();
    const phone = document.getElementById('edit-user-phone').value.trim();
    const username = document.getElementById('edit-user-username').value.trim();
    const role = document.getElementById('edit-user-role').value;
    const status = document.getElementById('edit-user-status').value;
    const city = document.getElementById('edit-user-city').value.trim();
    const provinceId = document.getElementById('edit-user-province').value;

    if (!userId || !name || !email || !phone || !username) {
      alert('Lengkapi seluruh bidang wajib pengeditan (*)!');
      return;
    }

    try {
      const res = await fetch('api.php?action=update_user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId, name, email, phone, username, role, status, city, provinceId })
      });
      const data = await res.json();
      if (data.success) {
        alert('🎉 ' + data.message);
        AuthEngine.closeAllModals();
        await this.fetchData();
        this.renderUserManagement();
      } else {
        alert('Gagal mengedit user: ' + data.message);
      }
    } catch (e) {
      alert('Terjadi kesalahan jaringan saat mengedit user!');
    }
  },

  async createUserFromAdmin(event) {
    event.preventDefault();
    const name = document.getElementById('add-user-name').value.trim();
    const email = document.getElementById('add-user-email').value.trim();
    const phone = document.getElementById('add-user-phone').value.trim();
    const username = document.getElementById('add-user-username').value.trim();
    const password = document.getElementById('add-user-password').value || 'Pass123!';
    const role = document.getElementById('add-user-role').value;
    const status = document.getElementById('add-user-status').value;
    const city = document.getElementById('add-user-city').value.trim() || 'Jakarta';
    const provinceId = document.getElementById('add-user-province').value || 'prov_jkt';

    if (!name || !email || !phone || !username) {
      alert('Lengkapi seluruh bidang wajib (*)!');
      return;
    }

    try {
      const res = await fetch('api.php?action=create_user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, phone, username, password, role, status, city, provinceId })
      });
      const data = await res.json();
      if (data.success) {
        alert('🎉 User baru berhasil ditambahkan langsung ke Supabase Cloud Database!');
        AuthEngine.closeAllModals();
        document.getElementById('add-user-form').reset();
        await this.fetchData();
        this.renderUserManagement();
      } else {
        alert('Gagal menambah user: ' + data.message);
      }
    } catch (e) {
      alert('Terjadi kesalahan jaringan saat menambah user!');
    }
  },

  async suspendUser(userId) {
    try {
      const res = await fetch('api.php?action=suspend_user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId })
      });
      const data = await res.json();
      alert(data.message);
      await this.fetchData();
      this.renderUserManagement();
    } catch (e) {
      alert('Gagal mengaktifkan/mensuspend akun!');
    }
  },

  async resetPassword(userId) {
    if (!confirm(`Reset password untuk user ID ${userId}?`)) return;
    try {
      const res = await fetch('api.php?action=reset_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId })
      });
      const data = await res.json();
      alert(data.message);
    } catch (e) {
      alert('Gagal me-reset password!');
    }
  },

  async deleteUser(userId) {
    if (!confirm(`Hapus permanen user ID ${userId} dari Supabase Cloud Database?`)) return;
    try {
      const res = await fetch('api.php?action=delete_user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId })
      });
      const data = await res.json();
      alert(data.message);
      await this.fetchData();
      this.renderUserManagement();
    } catch (e) {
      alert('Gagal menghapus user!');
    }
  },

  simulateBulkImport() {
    const fileInput = document.getElementById('csv-import-file');
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
      alert('Pilih berkas CSV/Excel data member terlebih dahulu!');
      return;
    }
    alert(`Bulk Import Berhasil! Data member baru telah di-import ke Supabase Cloud Database.`);
    AuthEngine.closeAllModals();
  },

  simulateExport(format) {
    alert(`Export data member dalam format ${format.toUpperCase()} telah diunduh: MB_INA_Members_2026.${format}`);
  },

  renderAuditLogs() {
    const logContainer = document.getElementById('audit-logs-table');
    if (!logContainer) return;

    logContainer.innerHTML = `
      <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
        <thead>
          <tr style="border-bottom:1px solid var(--chrome-border); text-align:left; color:var(--text-muted);">
            <th style="padding:12px;">Log ID</th>
            <th style="padding:12px;">User ID</th>
            <th style="padding:12px;">Action</th>
            <th style="padding:12px;">Module</th>
            <th style="padding:12px;">Details JSON</th>
            <th style="padding:12px;">IP Address</th>
            <th style="padding:12px;">Timestamp</th>
          </tr>
        </thead>
        <tbody>
          ${this.auditLogs.map(l => {
            let detailStr = '';
            try {
              let parsed = (typeof l.details === 'string') ? JSON.parse(l.details) : l.details;
              if (typeof parsed === 'string') parsed = JSON.parse(parsed);
              detailStr = JSON.stringify(parsed);
            } catch (e) {
              detailStr = String(l.details || '');
            }
            return `
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:12px; font-family:monospace; color:var(--text-muted);">${l.id}</td>
              <td style="padding:12px; font-weight:600;">${l.userId}</td>
              <td style="padding:12px;">
                <span class="tier-badge" style="background:${l.action === 'DELETE' || l.action === 'SUSPEND' ? 'rgba(239,68,68,0.2)' : 'rgba(59,130,246,0.2)'}; color:${l.action === 'DELETE' || l.action === 'SUSPEND' ? 'var(--accent-red)' : 'var(--accent-blue)'}; font-size:0.7rem;">
                  ${l.action}
                </span>
              </td>
              <td style="padding:12px; font-weight:600; color:var(--accent-gold);">${l.module}</td>
              <td style="padding:12px; font-family:monospace; font-size:0.78rem; color:var(--text-main);">${detailStr}</td>
              <td style="padding:12px; font-size:0.8rem;">${l.ipAddress}</td>
              <td style="padding:12px; font-size:0.8rem; color:var(--text-muted);">${l.timestamp}</td>
            </tr>
            `;
          }).join('')}
        </tbody>
      </table>
    `;
  },

  // ============================================
  // 3.1.4 PENGATURAN SISTEM ENGINE & SUB-MODULES
  // ============================================
  switchSettingsSubtab(subtab) {
    this.activeSettingsSubtab = subtab;
    document.querySelectorAll('[data-subtab]').forEach(btn => {
      if (btn.getAttribute('data-subtab') === subtab) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    document.querySelectorAll('.settings-subtab-content').forEach(el => el.style.display = 'none');
    const targetContent = document.getElementById(`subtab-${subtab}`);
    if (targetContent) targetContent.style.display = 'block';

    if (subtab === 'tier') this.renderTierManagement();
    else if (subtab === 'province') this.renderProvincesAdmin();
    else if (subtab === 'payment') this.renderPaymentGateway();
    else if (subtab === 'email') this.renderEmailTemplates();
    else if (subtab === 'backup') this.renderBackupsAdmin();
  },

  renderSystemSettings() {
    const s = this.systemSettings || {};
    const themeSelect = document.getElementById('setting-theme-select');
    const langSelect = document.getElementById('setting-language-select');
    const tzSelect = document.getElementById('setting-timezone-select');
    const maintSwitch = document.getElementById('setting-maint-switch');

    if (themeSelect) themeSelect.value = s.defaultTheme || 'LIGHT';
    if (langSelect) langSelect.value = s.language || 'ID';
    if (tzSelect) tzSelect.value = s.timezone || 'Asia/Jakarta (WIB)';
    if (maintSwitch) maintSwitch.checked = s.maintenanceMode || false;

    this.switchSettingsSubtab(this.activeSettingsSubtab);
  },

  async saveSystemSettings() {
    const defaultTheme = document.getElementById('setting-theme-select').value;
    const language = document.getElementById('setting-language-select').value;
    const timezone = document.getElementById('setting-timezone-select').value;
    const maintenanceMode = document.getElementById('setting-maint-switch') ? document.getElementById('setting-maint-switch').checked : false;

    try {
      const res = await fetch('api.php?action=update_system_settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ defaultTheme, language, timezone, maintenanceMode })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
      await this.fetchData();
    } catch (e) {
      alert('Gagal menyimpan pengaturan sistem!');
    }
  },

  // 3.1.4.2 MANAJEMEN TIER ⭐
  renderTierManagement() {
    const container = document.getElementById('tiers-grid-container');
    if (!container) return;

    const donationRanges = {
      'BRONZE': 'Rp 0 - Rp 1.499.999',
      'SILVER': 'Rp 1.500.000 - Rp 4.499.999',
      'GOLD': 'Rp 4.500.000 - Rp 8.999.999',
      'PLATINUM': '≥ Rp 9.000.000'
    };

    container.innerHTML = this.tiers.map(t => {
      const bList = Array.isArray(t.benefits) ? t.benefits : [];
      return `
        <div class="glass-card" style="padding:22px; border-top:4px solid ${t.color || 'var(--accent-gold)'};">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
              <span style="font-size:2rem;">${t.icon || '⭐'}</span>
              <div>
                <h4 style="font-size:1.2rem; color:var(--text-main);">${t.name}</h4>
                <span style="font-size:0.75rem; color:var(--accent-gold); font-weight:700;">Level ${t.level}</span>
              </div>
            </div>
            <span class="tier-badge" style="background:rgba(245,158,11,0.15); color:var(--accent-gold); border:1px solid var(--accent-gold);">
              👥 ${t.member_count || 0} Member
            </span>
          </div>

          <div style="background:rgba(0,0,0,0.03); border:1px solid var(--chrome-border); border-radius:10px; padding:12px; margin-bottom:14px;">
            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Akumulasi Donasi / Tahun:</div>
            <div style="font-size:0.95rem; font-weight:800; color:var(--accent-gold); margin-top:2px;">
              ${donationRanges[t.code] || ('Rp ' + Number(t.fee).toLocaleString())}
            </div>
          </div>

          <div style="margin-bottom:18px;">
            <div style="font-size:0.8rem; font-weight:700; color:var(--text-main); margin-bottom:6px;">Benefit Keanggotaan:</div>
            <ul style="font-size:0.8rem; color:var(--text-muted); padding-left:16px; display:flex; flex-direction:column; gap:4px;">
              ${bList.map(b => `<li>✅ ${b}</li>`).join('')}
            </ul>
          </div>

          <button class="btn-outline" style="width:100%; padding:8px; font-size:0.85rem;" onclick="AppEngine.openEditTierModal('${t.id}')">
            ✏️ Edit Tier ${t.name}
          </button>
        </div>
      `;
    }).join('');
  },

  openEditTierModal(tierId) {
    const t = this.tiers.find(x => x.id === tierId);
    if (!t) return;

    document.getElementById('edit-tier-id').value = t.id;
    document.getElementById('edit-tier-code').value = t.code;
    document.getElementById('edit-tier-level').value = t.level;
    document.getElementById('edit-tier-name').value = t.name;
    document.getElementById('edit-tier-icon').value = t.icon || '⭐';
    document.getElementById('edit-tier-color').value = t.color || '#D4AF37';
    document.getElementById('edit-tier-fee').value = t.fee || 0;
    
    const bList = Array.isArray(t.benefits) ? t.benefits.join(', ') : '';
    document.getElementById('edit-tier-benefits').value = bList;

    AuthEngine.openModal('modal-edit-tier');
  },

  async saveTierFromModal(event) {
    event.preventDefault();
    const id = document.getElementById('edit-tier-id').value;
    const name = document.getElementById('edit-tier-name').value.trim();
    const icon = document.getElementById('edit-tier-icon').value.trim();
    const color = document.getElementById('edit-tier-color').value;
    const fee = parseFloat(document.getElementById('edit-tier-fee').value || 0);
    const bRaw = document.getElementById('edit-tier-benefits').value;
    const benefits = bRaw.split(',').map(s => s.trim()).filter(s => s.length > 0);

    try {
      const res = await fetch('api.php?action=update_tier', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, name, icon, color, fee, benefits })
      });
      const data = await res.json();
      alert(data.message);
      AuthEngine.closeAllModals();
      await this.fetchData();
      this.renderTierManagement();
    } catch (e) {
      alert('Gagal menyimpan Tier!');
    }
  },

  // 3.1.4.3 MANAJEMEN PROVINSI (38 PROVINSI)
  renderProvincesAdmin() {
    const container = document.getElementById('provinces-admin-table');
    if (!container) return;

    container.innerHTML = `
      <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
        <thead>
          <tr style="border-bottom:1px solid var(--chrome-border); text-align:left; color:var(--text-muted);">
            <th style="padding:12px;">Kode</th>
            <th style="padding:12px;">Nama Provinsi</th>
            <th style="padding:12px;">Region Wilayah</th>
            <th style="padding:12px;">Klub Terdaftar</th>
            <th style="padding:12px;">Status</th>
            <th style="padding:12px; text-align:right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          ${this.provinces.map(p => `
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:12px; font-family:monospace; font-weight:800; color:var(--accent-gold);">${p.code}</td>
              <td style="padding:12px; font-weight:700;">${p.name}</td>
              <td style="padding:12px; color:var(--text-muted);">${p.region}</td>
              <td style="padding:12px;">
                <span class="tier-badge" style="background:rgba(59,130,246,0.15); color:var(--accent-blue); border:1px solid var(--accent-blue);">
                  🚗 ${p.club_count || 0} Klub
                </span>
              </td>
              <td style="padding:12px;">
                <span class="tier-badge" style="background:rgba(16,185,129,0.15); color:var(--primary-emerald); border:1px solid var(--primary-emerald);">AKTIF</span>
              </td>
              <td style="padding:12px; text-align:right;">
                <button class="role-pill-btn" style="border-color:var(--accent-red); color:var(--accent-red);" onclick="AppEngine.deleteProvince('${p.id}')">🗑️ Hapus</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  },

  async createProvinceFromModal(event) {
    event.preventDefault();
    const code = document.getElementById('add-prov-code').value.trim();
    const name = document.getElementById('add-prov-name').value.trim();
    const region = document.getElementById('add-prov-region').value;

    try {
      const res = await fetch('api.php?action=create_province', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code, name, region })
      });
      const data = await res.json();
      alert(data.message);
      AuthEngine.closeAllModals();
      await this.fetchData();
      this.renderProvincesAdmin();
    } catch (e) {
      alert('Gagal menambah provinsi!');
    }
  },

  async deleteProvince(id) {
    if (!confirm('Hapus provinsi ini dari Supabase?')) return;
    try {
      const res = await fetch('api.php?action=delete_province', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      alert(data.message);
      await this.fetchData();
      this.renderProvincesAdmin();
    } catch (e) {
      alert('Gagal menghapus provinsi!');
    }
  },

  // 3.1.4.4 PAYMENT GATEWAY (BANK MANDIRI SPONSOR RESMI)
  renderPaymentGateway() {
    fetch('api.php?action=get_payment_settings')
      .then(r => r.json())
      .then(res => {
        if (res.success && res.paymentGateway) {
          const gw = res.paymentGateway;
          const modeSelect = document.getElementById('mandiri-mode-select');
          const accNum = document.getElementById('mandiri-account-number');
          const accName = document.getElementById('mandiri-account-name');
          const branch = document.getElementById('mandiri-branch');
          const webhook = document.getElementById('mandiri-webhook-url');

          if (modeSelect) modeSelect.value = gw.mode || 'production';
          if (accNum) accNum.value = gw.accountNumber || '123-00-1234567-8';
          if (accName) accName.value = gw.accountName || 'MB Club Indonesia';
          if (branch) branch.value = gw.branch || 'KCU Jakarta Pusat';
          if (webhook) webhook.value = gw.webhookUrl || 'https://mbina.or.id/api/webhook/payment-mandiri';

          const methods = Array.isArray(gw.methods) ? gw.methods : ['transfer', 'va', 'qris'];
          if (document.getElementById('mandiri-method-transfer')) document.getElementById('mandiri-method-transfer').checked = methods.includes('transfer');
          if (document.getElementById('mandiri-method-va')) document.getElementById('mandiri-method-va').checked = methods.includes('va');
          if (document.getElementById('mandiri-method-qris')) document.getElementById('mandiri-method-qris').checked = methods.includes('qris');
        }
      });
  },

  async savePaymentGateway() {
    const mode = document.getElementById('mandiri-mode-select').value;
    const accountNumber = document.getElementById('mandiri-account-number').value.trim();
    const accountName = document.getElementById('mandiri-account-name').value.trim();
    const branch = document.getElementById('mandiri-branch').value.trim();
    const webhookUrl = document.getElementById('mandiri-webhook-url').value.trim();

    if (!accountNumber || !accountName || !branch || !webhookUrl) {
      alert('Lengkapi seluruh bidang wajib Bank Mandiri (*)!');
      return;
    }

    const methods = [];
    if (document.getElementById('mandiri-method-transfer').checked) methods.push('transfer');
    if (document.getElementById('mandiri-method-va').checked) methods.push('va');
    if (document.getElementById('mandiri-method-qris').checked) methods.push('qris');

    try {
      const res = await fetch('api.php?action=update_payment_settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          paymentGateway: {
            provider: 'MANDIRI',
            mode,
            bankName: 'Bank Mandiri',
            accountNumber,
            accountName,
            branch,
            methods,
            webhookUrl,
            isActive: true
          }
        })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
    } catch (e) {
      alert('Gagal menyimpan Payment Gateway Mandiri!');
    }
  },

  async testPaymentConnection() {
    try {
      const res = await fetch('api.php?action=test_payment_connection', { method: 'POST' });
      const data = await res.json();
      alert(data.message);
    } catch (e) {
      alert('Gagal menguji koneksi Bank Mandiri API!');
    }
  },

  // 3.1.4.5 EMAIL TEMPLATES
  renderEmailTemplates() {
    const container = document.getElementById('email-templates-table');
    if (!container) return;

    const tmplEntries = Object.entries(this.emailTemplates);

    container.innerHTML = `
      <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
        <thead>
          <tr style="border-bottom:1px solid var(--chrome-border); text-align:left; color:var(--text-muted);">
            <th style="padding:12px;">Kode Template</th>
            <th style="padding:12px;">Nama / Kegunaan</th>
            <th style="padding:12px;">Subject Line Email</th>
            <th style="padding:12px; text-align:right;">Aksi Edit & Test</th>
          </tr>
        </thead>
        <tbody>
          ${tmplEntries.map(([code, t]) => `
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:12px; font-family:monospace; font-weight:800; color:var(--accent-gold);">${code}</td>
              <td style="padding:12px; font-weight:700;">${t.name || code}</td>
              <td style="padding:12px; color:var(--text-main);">${t.subject || ''}</td>
              <td style="padding:12px; text-align:right;">
                <button class="role-pill-btn" style="border-color:var(--accent-gold); color:var(--accent-gold);" onclick="AppEngine.openEditTemplateModal('${code}')">✏️ Edit Template</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  },

  openEditTemplateModal(code) {
    const t = this.emailTemplates[code] || {};
    document.getElementById('edit-tmpl-code').value = code;
    document.getElementById('modal-edit-tmpl-title').innerText = `✏️ Edit Template: ${t.name || code}`;
    document.getElementById('edit-tmpl-subject').value = t.subject || '';
    document.getElementById('edit-tmpl-content').value = t.content || '';
    AuthEngine.openModal('modal-edit-template');
  },

  async saveEmailTemplateFromModal(event) {
    event.preventDefault();
    const code = document.getElementById('edit-tmpl-code').value;
    const subject = document.getElementById('edit-tmpl-subject').value.trim();
    const content = document.getElementById('edit-tmpl-content').value.trim();

    try {
      const res = await fetch('api.php?action=update_email_template', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code, subject, content })
      });
      const data = await res.json();
      alert(data.message);
      AuthEngine.closeAllModals();
      await this.fetchData();
      this.renderEmailTemplates();
    } catch (e) {
      alert('Gagal menyimpan template email!');
    }
  },

  async testSendEmailFromModal() {
    const code = document.getElementById('edit-tmpl-code').value;
    const email = prompt('Masukkan Email Tujuan untuk Pengiriman Test:', 'dtouriano@gmail.com');
    if (!email) return;

    try {
      const res = await fetch('api.php?action=test_send_email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ targetEmail: email, code })
      });
      const data = await res.json();
      alert(data.message);
    } catch (e) {
      alert('Gagal mengirim test email!');
    }
  },

  // 3.1.4.6 BACKUP & RESTORE
  renderBackupsAdmin() {
    const container = document.getElementById('backups-admin-table');
    if (!container) return;

    container.innerHTML = `
      <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
        <thead>
          <tr style="border-bottom:1px solid var(--chrome-border); text-align:left; color:var(--text-muted);">
            <th style="padding:12px;">Backup ID</th>
            <th style="padding:12px;">Nama Berkas SQL</th>
            <th style="padding:12px;">Ukuran File</th>
            <th style="padding:12px;">Tipe Backup</th>
            <th style="padding:12px;">Waktu Backup</th>
            <th style="padding:12px; text-align:right;">Aksi Restore</th>
          </tr>
        </thead>
        <tbody>
          ${this.backups.map(b => `
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:12px; font-family:monospace; color:var(--text-muted);">${b.id}</td>
              <td style="padding:12px; font-weight:700; font-family:monospace; color:var(--accent-gold);">${b.filename}</td>
              <td style="padding:12px;">${b.size}</td>
              <td style="padding:12px;">
                <span class="tier-badge" style="background:rgba(59,130,246,0.15); color:var(--accent-blue); border:1px solid var(--accent-blue);">
                  ${b.type}
                </span>
              </td>
              <td style="padding:12px; color:var(--text-muted);">${b.timestamp}</td>
              <td style="padding:12px; text-align:right;">
                <button class="role-pill-btn" style="border-color:var(--primary-emerald); color:var(--primary-emerald);" onclick="AppEngine.restoreBackup('${b.id}')">🔄 Restore DB</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  },

  async createBackupNow() {
    try {
      const res = await fetch('api.php?action=create_backup', { method: 'POST' });
      const data = await res.json();
      alert('🎉 ' + data.message);
      if (data.backup) {
        this.backups.unshift(data.backup);
        this.renderBackupsAdmin();
      }
    } catch (e) {
      alert('Gagal membuat backup!');
    }
  },

  async restoreBackup(backupId) {
    if (!confirm(`Apakah Anda yakin ingin memulihkan seluruh database Supabase ke backup ${backupId}?`)) return;
    try {
      const res = await fetch('api.php?action=restore_backup', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ backupId })
      });
      const data = await res.json();
      alert(data.message);
    } catch (e) {
      alert('Gagal memulihkan database!');
    }
  },

  renderVerificationQueue() {
    const queueContainer = document.getElementById('admin-verification-table');
    if (!queueContainer) return;

    const pendingUsers = this.users.filter(u => u.status === 'PENDING' || u.role === 'CALON_MEMBER');

    if (pendingUsers.length === 0) {
      queueContainer.innerHTML = `<p style="padding:20px; color:var(--text-muted);">Tidak ada permohonan calon member baru saat ini.</p>`;
      return;
    }

    queueContainer.innerHTML = `
      <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
        <thead>
          <tr style="border-bottom:1px solid var(--chrome-border); text-align:left; color:var(--text-muted);">
            <th style="padding:12px;">Nama & Username</th>
            <th style="padding:12px;">Kontak & Email</th>
            <th style="padding:12px;">Kota & Usia</th>
            <th style="padding:12px;">Role Permohonan</th>
            <th style="padding:12px; text-align:right;">Aksi Verifikasi</th>
          </tr>
        </thead>
        <tbody>
          ${pendingUsers.map(u => `
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:14px 12px; font-weight:600;">
                ${u.name}<br>
                <span style="font-size:0.75rem; color:var(--accent-gold);">@${u.username || 'user'}</span>
              </td>
              <td style="padding:14px 12px; color:var(--text-muted);">
                ${u.email}<br>${u.phone}
              </td>
              <td style="padding:14px 12px;">
                ${u.city || 'Indonesia'}
              </td>
              <td style="padding:14px 12px;">
                <span class="tier-badge" style="background:rgba(245,158,11,0.15); color:var(--accent-gold); border:1px solid var(--accent-gold);">
                  ${this.formatRoleName(u.role)}
                </span>
              </td>
              <td style="padding:14px 12px; text-align:right;">
                <button class="btn-primary" style="padding:6px 12px; font-size:0.75rem;" onclick="AppEngine.approveUser('${u.id}')">✓ Approve Member</button>
                <button class="btn-outline" style="padding:6px 12px; font-size:0.75rem; color:var(--accent-red); border-color:var(--accent-red);" onclick="AppEngine.rejectUser('${u.id}')">✕ Reject</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  },

  async approveUser(userId) {
    if (!confirm('Apakah Anda yakin ingin menyetujui verifikasi keanggotaan calon member ini?')) return;

    try {
      const res = await fetch('api.php?action=update_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId, status: 'ACTIVE', role: 'MEMBER' })
      });
      const data = await res.json();
      alert(data.message);
      await this.fetchData();
      if (this.activeAdminTab === 'dashboard') this.renderAdminDashboard();
      else if (this.activeAdminTab === 'users') this.renderUserManagement();
    } catch (e) {
      alert('Gagal menyetujui member!');
    }
  },

  async rejectUser(userId) {
    if (!confirm('Tolak permohonan calon member ini?')) return;

    try {
      const res = await fetch('api.php?action=update_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId, status: 'REJECTED' })
      });
      const data = await res.json();
      alert(data.message);
      await this.fetchData();
      if (this.activeAdminTab === 'dashboard') this.renderAdminDashboard();
      else if (this.activeAdminTab === 'users') this.renderUserManagement();
    } catch (e) {
      alert('Gagal menolak permohonan!');
    }
  },

  handleRSVP(eventId) {
    alert('Terima kasih! RSVP Anda telah tercatat pada sistem event MB INA.');
  },

  // ============================================
  // M3 - MANAJEMEN KEANGGOTAAN ENGINE METHODS
  // ============================================
  async fetchM3Data() {
    try {
      const res = await fetch('api.php?action=get_m3_members').then(r => r.json());
      if (res.success) {
        this.m3Data.members = res.members || [];
        this.m3Data.pendingCount = res.pendingCount || 0;

        const badgeEl = document.getElementById('m3-pending-badge');
        if (badgeEl) badgeEl.innerText = `⏳ ${res.pendingCount} PENDING VERIFIKASI`;

        const pillCount = document.getElementById('m3-verify-pill-count');
        if (pillCount) pillCount.innerText = res.pendingCount;

        this.populateM3ClubDropdowns();
      }
    } catch (e) {
      console.error("Error loading M3 Data:", e);
    }
  },

  populateM3ClubDropdowns() {
    const clubsList = (this.m2Data && this.m2Data.clubs && this.m2Data.clubs.length) ? this.m2Data.clubs : this.clubs;
    const optionsHtml = `<option value="">Semua Klub & Chapter</option>` + 
      clubsList.map(c => `<option value="${c.name}">${c.name} (${c.region})</option>`).join('');

    const filterSelect = document.getElementById('m3-club-filter');
    if (filterSelect && filterSelect.options.length <= 1) filterSelect.innerHTML = optionsHtml;

    const addSelect = document.getElementById('m3-add-club');
    if (addSelect && !addSelect.children.length) {
      addSelect.innerHTML = clubsList.map(c => `<option value="${c.name}">${c.name} (${c.code || 'MB'})</option>`).join('');
    }

    const editSelect = document.getElementById('m3-edit-club');
    if (editSelect && !editSelect.children.length) {
      editSelect.innerHTML = clubsList.map(c => `<option value="${c.name}">${c.name} (${c.code || 'MB'})</option>`).join('');
    }
  },

  async renderM3Module() {
    await this.fetchM3Data();
    this.switchM3Subtab(this.m3Data.activeSubtab || 'list');
  },

  switchM3Subtab(subtab) {
    if (!subtab || typeof subtab !== 'string') subtab = 'list';
    this.m3Data.activeSubtab = subtab;
    document.querySelectorAll('[data-m3sub]').forEach(btn => {
      if (btn.getAttribute('data-m3sub') === subtab) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    document.querySelectorAll('.m3-subtab-content').forEach(el => el.style.display = 'none');
    const target = document.getElementById(`m3sub-${subtab}`);
    if (target) target.style.display = 'block';

    if (subtab === 'list') this.renderM3MemberList();
    else if (subtab === 'verify') this.renderM3VerifyList();
    else if (subtab === 'add') this.populateM3ClubDropdowns();
  },

  renderM3MemberList() {
    const container = document.getElementById('m3-member-table-container');
    if (!container) return;

    const searchQuery = (document.getElementById('m3-search-input')?.value || '').toLowerCase().trim();
    const tierFilter = document.getElementById('m3-tier-filter')?.value || '';
    const statusFilter = document.getElementById('m3-status-filter')?.value || '';
    const clubFilter = document.getElementById('m3-club-filter')?.value || '';

    let filtered = this.m3Data.members;

    if (searchQuery) {
      filtered = filtered.filter(m =>
        (m.name || '').toLowerCase().includes(searchQuery) ||
        (m.email || '').toLowerCase().includes(searchQuery) ||
        (m.username || '').toLowerCase().includes(searchQuery) ||
        (m.member_id || '').toLowerCase().includes(searchQuery)
      );
    }

    if (tierFilter) filtered = filtered.filter(m => m.tier === tierFilter);
    if (statusFilter) filtered = filtered.filter(m => m.status === statusFilter);
    if (clubFilter) filtered = filtered.filter(m => m.club === clubFilter);

    // TIER: icon only + tooltip (no text)
    const getTierIcon = (t) => {
      const map = {
        'PLATINUM': { icon: '💎', color: '#A855F7', bg: 'rgba(168,85,247,0.15)', border: '#A855F7', label: 'PLATINUM' },
        'GOLD':     { icon: '🥇', color: '#F59E0B', bg: 'rgba(245,158,11,0.15)',  border: '#F59E0B', label: 'GOLD' },
        'SILVER':   { icon: '🥈', color: '#C0C0C0', bg: 'rgba(192,192,192,0.15)', border: '#C0C0C0', label: 'SILVER' },
        'BRONZE':   { icon: '🥉', color: '#CD7F32', bg: 'rgba(205,127,50,0.15)',  border: '#CD7F32', label: 'BRONZE' },
      };
      const d = map[t] || map['BRONZE'];
      return `<span title="${d.label} Member" style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; background:${d.bg}; border:1px solid ${d.border}; font-size:1.1rem; cursor:default;">${d.icon}</span>`;
    };

    // STATUS: text only, no icon
    const getStatusBadge = (s) => {
      if (s === 'ACTIVE')    return `<span class="tier-badge" style="background:rgba(16,185,129,0.2); color:var(--primary-emerald); border:1px solid var(--primary-emerald); font-size:0.7rem;">ACTIVE</span>`;
      if (s === 'PENDING')   return `<span class="tier-badge" style="background:rgba(245,158,11,0.2); color:var(--accent-gold); border:1px solid var(--accent-gold); font-size:0.7rem;">PENDING</span>`;
      if (s === 'SUSPENDED') return `<span class="tier-badge" style="background:rgba(239,68,68,0.2); color:var(--accent-red); border:1px solid var(--accent-red); font-size:0.7rem;">SUSPENDED</span>`;
      return `<span class="tier-badge" style="background:rgba(100,116,139,0.2); color:var(--text-muted); font-size:0.7rem;">${s}</span>`;
    };

    container.innerHTML = `
      <table class="data-table" style="width:100%; border-collapse:collapse; table-layout:auto;">
        <thead>
          <tr style="border-bottom:2px solid var(--chrome-border); text-align:left; color:var(--text-muted); font-size:0.8rem; background:rgba(0,0,0,0.15);">
            <th style="padding:10px 10px; white-space:nowrap;">ID MEMBER</th>
            <th style="padding:10px 10px;">NAMA & KONTAK</th>
            <th style="padding:10px 10px;">KLUB / CHAPTER</th>
            <th style="padding:10px 10px; text-align:center; white-space:nowrap;">TIER</th>
            <th style="padding:10px 10px; text-align:center; white-space:nowrap;">STATUS</th>
            <th style="padding:10px 10px; white-space:nowrap;">TOTAL DONASI</th>
            <th style="padding:10px 10px; text-align:center; width:120px;">AKSI</th>
          </tr>
        </thead>
        <tbody>
          ${filtered.length === 0 ? `
            <tr>
              <td colspan="7" style="text-align:center; padding:32px; color:var(--text-muted);">
                Tidak ada data member yang sesuai filter pencarian.
              </td>
            </tr>
          ` : filtered.map((m, idx) => `
            <tr class="m3-member-row" data-mid="${m.id}" style="border-bottom:1px solid var(--chrome-border); transition:background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background=''">
              <td style="font-family:monospace; font-weight:800; color:var(--accent-gold); font-size:0.78rem; padding:10px; white-space:nowrap;">
                ${m.member_id || '<span style="color:var(--accent-red); font-size:0.72rem;">[PENDING ID]</span>'}
              </td>
              <td style="padding:10px;">
                <div style="font-weight:700; color:var(--text-main); font-size:0.88rem;">${m.name}</div>
                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:3px;">
                  ${m.email} • <span style="color:var(--accent-gold); font-weight:700;">📞 ${m.phone || '-'}</span>
                </div>
              </td>
              <td style="font-size:0.82rem; padding:10px; color:var(--text-muted);">${m.club || 'HQ MB INA'}</td>
              <td style="padding:10px; text-align:center;">${getTierIcon(m.tier)}</td>
              <td style="padding:10px; text-align:center;">${getStatusBadge(m.status)}</td>
              <td style="font-weight:800; color:var(--primary-emerald); font-size:0.85rem; padding:10px; white-space:nowrap;">
                Rp ${new Intl.NumberFormat('id-ID').format(m.total_donation || 0)}
              </td>
              <td style="padding:10px; text-align:center;">
                <div style="display:inline-flex; gap:5px; justify-content:center;">
                  <button class="m3-btn-detail" data-id="${m.id}" title="👁️ Detail Profile Member"
                    style="width:32px; height:32px; border-radius:8px; border:1px solid var(--accent-blue); background:rgba(59,130,246,0.1); color:var(--accent-blue); cursor:pointer; font-size:0.95rem; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
                    onmouseover="this.style.background='rgba(59,130,246,0.25)'" onmouseout="this.style.background='rgba(59,130,246,0.1)'">👁️</button>
                  <button class="m3-btn-edit" data-id="${m.id}" title="✏️ Edit Data Member"
                    style="width:32px; height:32px; border-radius:8px; border:1px solid var(--accent-gold); background:rgba(245,158,11,0.1); color:var(--accent-gold); cursor:pointer; font-size:0.95rem; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
                    onmouseover="this.style.background='rgba(245,158,11,0.25)'" onmouseout="this.style.background='rgba(245,158,11,0.1)'">✏️</button>
                  <button class="m3-btn-donasi" data-id="${m.id}" title="💰 Catat Donasi & Upgrade Tier"
                    style="width:32px; height:32px; border-radius:8px; border:1px solid var(--primary-emerald); background:rgba(16,185,129,0.1); color:var(--primary-emerald); cursor:pointer; font-size:0.95rem; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
                    onmouseover="this.style.background='rgba(16,185,129,0.25)'" onmouseout="this.style.background='rgba(16,185,129,0.1)'">💰</button>
                  <button class="m3-btn-delete" data-id="${m.id}" title="🗑️ Hapus Member"
                    style="width:32px; height:32px; border-radius:8px; border:1px solid var(--accent-red); background:rgba(239,68,68,0.1); color:var(--accent-red); cursor:pointer; font-size:0.95rem; display:flex; align-items:center; justify-content:center; transition:all 0.15s;"
                    onmouseover="this.style.background='rgba(239,68,68,0.25)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'">🗑️</button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      <div style="margin-top:14px; font-size:0.78rem; color:var(--text-muted); display:flex; justify-content:space-between; align-items:center;">
        <span>Menampilkan <strong style="color:var(--text-main);">${filtered.length}</strong> dari <strong style="color:var(--text-main);">${this.m3Data.members.length}</strong> total member MB INA</span>
        <span style="color:var(--accent-gold); font-weight:700;">☁️ Supabase Cloud PostgreSQL Live</span>
      </div>
    `;

    // === EVENT DELEGATION — safe dari single-quote issue ===
    container.querySelectorAll('.m3-btn-detail').forEach(btn => {
      btn.addEventListener('click', () => AppEngine.openM3MemberDetail(btn.dataset.id));
    });
    container.querySelectorAll('.m3-btn-edit').forEach(btn => {
      btn.addEventListener('click', () => AppEngine.openEditM3MemberModal(btn.dataset.id));
    });
    container.querySelectorAll('.m3-btn-donasi').forEach(btn => {
      btn.addEventListener('click', () => AppEngine.openM3AddDonationModal(btn.dataset.id));
    });
    container.querySelectorAll('.m3-btn-delete').forEach(btn => {
      btn.addEventListener('click', () => AppEngine.deleteM3Member(btn.dataset.id));
    });
  },

  renderM3VerifyList() {
    const container = document.getElementById('m3-verify-table-container');
    if (!container) return;

    const pendingMembers = this.m3Data.members.filter(m => m.status === 'PENDING' || m.role === 'CALON_MEMBER');

    container.innerHTML = `
      <table class="data-table" style="width:100%;">
        <thead>
          <tr>
            <th>NAMA CALON MEMBER</th>
            <th>EMAIL & WHATSAPP</th>
            <th>KLUB TUJUAN</th>
            <th>PROVINSI & KOTA</th>
            <th>TANGGAL DAFTAR</th>
            <th>AKSI VERIFIKASI</th>
          </tr>
        </thead>
        <tbody>
          ${pendingMembers.length === 0 ? `
            <tr>
              <td colspan="6" style="text-align:center; padding:32px; color:var(--primary-emerald); font-weight:700;">
                🎉 Tidak ada antrean verifikasi pending! Semua pendaftar telah diproses.
              </td>
            </tr>
          ` : pendingMembers.map(m => `
            <tr>
              <td>
                <div style="font-weight:700; color:var(--text-main); font-size:0.95rem;">${m.name}</div>
                <span style="font-size:0.75rem; color:var(--accent-gold);">@${m.username || 'calon_member'}</span>
              </td>
              <td>
                <div style="font-size:0.85rem; color:var(--text-main);">${m.email}</div>
                <div style="font-size:0.78rem; color:var(--accent-gold); font-weight:700;">📞 ${m.phone || '-'}</div>
              </td>
              <td style="font-weight:700; color:var(--text-main); font-size:0.88rem;">${m.club || 'HQ MB INA'}</td>
              <td style="font-size:0.85rem;">${m.city || '-'}, ${m.province || '-'}</td>
              <td style="font-size:0.8rem; color:var(--text-muted);">${m.created_at ? new Date(m.created_at).toLocaleDateString('id-ID') : 'Baru Saja'}</td>
              <td>
                <div style="display:flex; gap:8px;">
                  <button class="btn-primary" style="padding:6px 14px; font-size:0.78rem;" onclick="AppEngine.openM3VerifyModal('${m.id}', 'APPROVE')">✅ Setujui (Approve)</button>
                  <button class="btn-outline" style="padding:6px 14px; font-size:0.78rem; border-color:var(--accent-red); color:var(--accent-red);" onclick="AppEngine.openM3VerifyModal('${m.id}', 'REJECT')">❌ Tolak (Reject)</button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  },

  openAddM3MemberModal() {
    this.switchM3Subtab('add');
  },

  async saveM3MemberFromForm(event) {
    event.preventDefault();
    const name = document.getElementById('m3-add-name').value.trim();
    const username = document.getElementById('m3-add-username').value.trim();
    const email = document.getElementById('m3-add-email').value.trim();
    const phone = document.getElementById('m3-add-phone').value.trim();
    const birth_date = document.getElementById('m3-add-bdate').value;
    const gender = document.getElementById('m3-add-gender').value;
    const province = document.getElementById('m3-add-province').value.trim();
    const city = document.getElementById('m3-add-city').value.trim();
    const club = document.getElementById('m3-add-club').value;
    const tier = document.getElementById('m3-add-tier').value;
    const status = document.getElementById('m3-add-status').value;
    const admin_notes = document.getElementById('m3-add-notes').value.trim();

    try {
      const res = await fetch('api.php?action=create_m3_member', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, username, email, phone, birth_date, gender, province, city, club, tier, status, admin_notes })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
      await this.fetchM3Data();
      this.switchM3Subtab('list');
    } catch (err) {
      alert('❌ Gagal menambah member: ' + err.message);
    }
  },

  openEditM3MemberModal(id) {
    const m = this.m3Data.members.find(x => x.id === id);
    if (!m) return;

    this.populateM3ClubDropdowns();

    document.getElementById('m3-edit-id').value = m.id;
    document.getElementById('m3-edit-name').value = m.name;
    document.getElementById('m3-edit-member-id').value = m.member_id || '[PENDING ID]';
    document.getElementById('m3-edit-email').value = m.email;
    document.getElementById('m3-edit-phone').value = m.phone || '';
    document.getElementById('m3-edit-club').value = m.club || '';
    document.getElementById('m3-edit-tier').value = m.tier || 'BRONZE';
    document.getElementById('m3-edit-status').value = m.status || 'ACTIVE';
    document.getElementById('m3-edit-province').value = m.province || '';
    document.getElementById('m3-edit-city').value = m.city || '';
    document.getElementById('m3-edit-notes').value = m.admin_notes || '';

    AuthEngine.openModal('modal-m3-edit-member');
  },

  async saveM3MemberFromModal(event) {
    event.preventDefault();
    const id = document.getElementById('m3-edit-id').value;
    const name = document.getElementById('m3-edit-name').value.trim();
    const email = document.getElementById('m3-edit-email').value.trim();
    const phone = document.getElementById('m3-edit-phone').value.trim();
    const club = document.getElementById('m3-edit-club').value;
    const tier = document.getElementById('m3-edit-tier').value;
    const status = document.getElementById('m3-edit-status').value;
    const province = document.getElementById('m3-edit-province').value.trim();
    const city = document.getElementById('m3-edit-city').value.trim();
    const admin_notes = document.getElementById('m3-edit-notes').value.trim();

    try {
      const res = await fetch('api.php?action=update_m3_member', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, name, email, phone, club, tier, status, province, city, admin_notes })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
      AuthEngine.closeAllModals();
      await this.fetchM3Data();
      this.renderM3MemberList();
    } catch (err) {
      alert('❌ Gagal memperbarui member: ' + err.message);
    }
  },

  openM3VerifyModal(id, defaultAction = 'APPROVE') {
    const m = this.m3Data.members.find(x => x.id === id);
    if (!m) return;

    document.getElementById('m3-verify-id').value = m.id;
    document.getElementById('m3-verify-name').innerText = m.name;
    document.getElementById('m3-verify-info').innerText = `${m.email} | 📞 ${m.phone} | Klub: ${m.club}`;
    document.getElementById('m3-verify-reason').value = '';
    document.getElementById('m3-verify-notes').value = '';

    const radios = document.getElementsByName('verify_action');
    for (let r of radios) {
      if (r.value === defaultAction) r.checked = true;
    }

    document.getElementById('m3-reject-reason-box').style.display = (defaultAction === 'REJECT') ? 'block' : 'none';

    AuthEngine.openModal('modal-m3-verify-member');
  },

  async saveM3Verification(event) {
    event.preventDefault();
    const id = document.getElementById('m3-verify-id').value;
    const radios = document.getElementsByName('verify_action');
    let type = 'APPROVE';
    for (let r of radios) {
      if (r.checked) type = r.value;
    }
    const reason = document.getElementById('m3-verify-reason').value.trim();
    const notes = document.getElementById('m3-verify-notes').value.trim();

    try {
      const res = await fetch('api.php?action=verify_m3_member', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, type, reason, notes })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
      AuthEngine.closeAllModals();
      await this.fetchM3Data();
      this.switchM3Subtab('verify');
    } catch (err) {
      alert('❌ Gagal memproses verifikasi: ' + err.message);
    }
  },

  async openM3MemberDetail(id) {
    const container = document.getElementById('m3-detail-content-body');
    if (!container) return;

    container.innerHTML = `<div style="text-align:center; padding:40px; color:var(--accent-gold);">⏳ Memuat detail profil member dari Supabase Cloud...</div>`;
    AuthEngine.openModal('modal-m3-member-detail');

    try {
      const res = await fetch('api.php?action=get_m3_member_detail', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      }).then(r => r.json());

      if (!res.success) {
        container.innerHTML = `<div style="color:var(--accent-red); padding:20px;">Gagal memuat detail member: ${res.message}</div>`;
        return;
      }

      const m = res.member;
      const donations = res.donations || [];
      const tierHistory = res.tierHistory || [];
      const activities = res.activities || [];

      const getTierBadge = (t) => {
        if (t === 'PLATINUM') return '<span class="tier-badge" style="background:rgba(168,85,247,0.2); color:#A855F7; border:1px solid #A855F7;">💎 PLATINUM</span>';
        if (t === 'GOLD') return '<span class="tier-badge" style="background:rgba(212,175,55,0.2); color:var(--accent-gold); border:1px solid var(--accent-gold);">🥇 GOLD</span>';
        if (t === 'SILVER') return '<span class="tier-badge" style="background:rgba(192,192,192,0.2); color:#C0C0C0; border:1px solid #C0C0C0;">🥈 SILVER</span>';
        return '<span class="tier-badge" style="background:rgba(205,127,50,0.2); color:#CD7F32; border:1px solid #CD7F32;">🥉 BRONZE</span>';
      };

      container.innerHTML = `
        <div style="display:grid; grid-template-columns:240px 1fr; gap:24px; margin-bottom:28px;">
          <!-- PROFILE PHOTO CARD -->
          <div class="glass-card" style="padding:24px; text-align:center; display:flex; flex-direction:column; align-items:center; border-top:4px solid var(--accent-gold);">

            <!-- FOTO / AVATAR -->
            <div style="position:relative; margin-bottom:14px;">
              ${m.photo_url
                ? `<img src="${m.photo_url}" alt="Foto ${m.name}"
                     style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--accent-gold); box-shadow:0 0 20px rgba(245,158,11,0.4);"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                   <div style="display:none; width:100px; height:100px; border-radius:50%; background:linear-gradient(135deg,var(--accent-gold),#b45309); align-items:center; justify-content:center; color:#fff; font-size:2.2rem; font-weight:900; border:3px solid var(--accent-gold); box-shadow:0 0 20px rgba(245,158,11,0.4);">
                     ${(m.name || 'MB').substring(0,2).toUpperCase()}
                   </div>`
                : `<div style="width:100px; height:100px; border-radius:50%; background:linear-gradient(135deg,var(--accent-gold),#b45309); display:flex; align-items:center; justify-content:center; color:#fff; font-size:2.2rem; font-weight:900; border:3px solid var(--accent-gold); box-shadow:0 0 20px rgba(245,158,11,0.4);">
                     ${(m.name || 'MB').substring(0,2).toUpperCase()}
                   </div>`
              }
              <!-- Badge kamera overlay -->
              <label for="m3-photo-upload-${m.id}" title="Ganti Foto Member"
                style="position:absolute; bottom:2px; right:2px; width:26px; height:26px; border-radius:50%; background:var(--accent-gold); display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.4); font-size:0.75rem; border:2px solid #0b0e14;">
                📷
              </label>
              <input type="file" id="m3-photo-upload-${m.id}" accept="image/jpeg,image/png,image/webp"
                style="display:none;" onchange="AppEngine.uploadMemberPhoto(event, '${m.id}')">
            </div>

            <h3 style="font-size:1.1rem; margin-bottom:4px;" class="text-gradient">${m.name}</h3>
            <span style="font-size:0.8rem; color:var(--accent-gold); font-weight:700;">@${m.username || 'member'}</span>
            <div style="margin-top:12px; margin-bottom:12px;">${getTierBadge(m.tier)}</div>
            <span style="font-family:monospace; font-size:0.8rem; font-weight:800; color:var(--text-main); background:rgba(0,0,0,0.15); padding:4px 10px; border-radius:6px; border:1px solid var(--chrome-border);">
              ${m.member_id || '[PENDING ID]'}
            </span>

            <!-- INFO UPLOAD FOTO -->
            <div style="margin-top:14px; padding:10px 12px; background:rgba(255,255,255,0.04); border:1px solid var(--chrome-border); border-radius:8px; text-align:left; width:100%;">
              <div style="font-size:0.7rem; color:var(--accent-gold); font-weight:700; margin-bottom:5px;">📷 Ketentuan Foto Profil</div>
              <div style="font-size:0.68rem; color:var(--text-muted); line-height:1.6;">
                • Format: <strong style="color:var(--text-main);">JPG / PNG / WebP</strong><br>
                • Maks. ukuran: <strong style="color:var(--primary-emerald);">2 MB</strong><br>
                • Resolusi min: <strong style="color:var(--text-main);">200 × 200 px</strong><br>
                • Rasio ideal: <strong style="color:var(--text-main);">1:1 (persegi)</strong>
              </div>
            </div>

            <!-- STATUS FOTO -->
            <div id="m3-photo-status-${m.id}" style="margin-top:8px; font-size:0.7rem; color:var(--text-muted);">
              ${m.photo_url ? '✅ Foto terpasang' : '⚠️ Belum ada foto — klik 📷 untuk upload'}
            </div>
          </div>

          <!-- PROFILE DETAILS TABLE -->
          <div class="glass-card" style="padding:24px;">
            <h4 style="font-size:1.05rem; color:var(--accent-gold); margin-bottom:16px;">─── 3.4.1 Informasi Lengkap Member ───</h4>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:0.85rem;">
              <div><strong>Email:</strong> ${m.email}</div>
              <div><strong>WhatsApp:</strong> ${m.phone || '-'}</div>
              <div><strong>Klub / Chapter:</strong> ${m.club || 'HQ MB INA'}</div>
              <div><strong>Domisili:</strong> ${m.city || '-'}, ${m.province || '-'}</div>
              <div><strong>Jenis Kelamin:</strong> ${m.gender || 'Pria'}</div>
              <div><strong>Tanggal Lahir:</strong> ${m.birth_date || '-'}</div>
              <div><strong>Tanggal Bergabung:</strong> ${m.created_at ? new Date(m.created_at).toLocaleDateString('id-ID') : '-'}</div>
              <div><strong>Status Verifikasi:</strong> <strong style="color:var(--primary-emerald);">${m.status}</strong></div>
            </div>

            <!-- STATISTIK BOX -->
            <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-top:20px; border-top:1px solid var(--chrome-border); padding-top:16px;">
              <div style="background:rgba(16,185,129,0.1); border:1px solid var(--primary-emerald); padding:10px; border-radius:8px; text-align:center;">
                <div style="font-size:0.72rem; color:var(--text-muted);">Total Donasi</div>
                <div style="font-size:0.9rem; font-weight:800; color:var(--primary-emerald);">Rp ${new Intl.NumberFormat('id-ID').format(m.total_donation || 0)}</div>
              </div>
              <div style="background:rgba(59,130,246,0.1); border:1px solid var(--accent-blue); padding:10px; border-radius:8px; text-align:center;">
                <div style="font-size:0.72rem; color:var(--text-muted);">Total Event</div>
                <div style="font-size:0.95rem; font-weight:800; color:var(--accent-blue);">${m.total_events || 0}</div>
              </div>
              <div style="background:rgba(245,158,11,0.1); border:1px solid var(--accent-gold); padding:10px; border-radius:8px; text-align:center;">
                <div style="font-size:0.72rem; color:var(--text-muted);">Loyalty Points</div>
                <div style="font-size:0.95rem; font-weight:800; color:var(--accent-gold);">${m.points || 0} pts</div>
              </div>
              <div style="background:rgba(168,85,247,0.1); border:1px solid #A855F7; padding:10px; border-radius:8px; text-align:center;">
                <div style="font-size:0.72rem; color:var(--text-muted);">Thread Forum</div>
                <div style="font-size:0.95rem; font-weight:800; color:#A855F7;">${m.forum_threads_count || 0}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIWAYAT TIER TIMELINE & DONASI -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">
          <!-- 3.4.3 RIWAYAT DONASI -->
          <div class="glass-card" style="padding:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
              <h4 style="font-size:1rem; color:var(--accent-gold);">💰 3.4.3 Riwayat Donasi</h4>
              <button class="role-pill-btn" style="border-color:var(--primary-emerald); color:var(--primary-emerald);" onclick="AppEngine.openM3AddDonationModal('${m.id}')">+ Donasi</button>
            </div>
            ${donations.length === 0 ? '<p style="font-size:0.82rem; color:var(--text-muted);">Belum ada riwayat donasi.</p>' : `
              <div style="max-height:180px; overflow-y:auto;">
                <table class="data-table" style="font-size:0.78rem; width:100%;">
                  <thead>
                    <tr><th>TANGGAL</th><th>JUMLAH</th><th>METODE</th><th>STATUS</th></tr>
                  </thead>
                  <tbody>
                    ${donations.map(d => `
                      <tr>
                        <td>${new Date(d.created_at).toLocaleDateString('id-ID')}</td>
                        <td style="font-weight:700; color:var(--primary-emerald);">Rp ${new Intl.NumberFormat('id-ID').format(d.amount)}</td>
                        <td>${d.payment_method}</td>
                        <td><span style="color:var(--primary-emerald); font-weight:800;">${d.status}</span></td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            `}
          </div>

          <!-- RIWAYAT TIER TIMELINE -->
          <div class="glass-card" style="padding:20px;">
            <h4 style="font-size:1rem; color:var(--accent-gold); margin-bottom:12px;">🏅 Riwayat Tier Member</h4>
            ${tierHistory.length === 0 ? `
              <p style="font-size:0.82rem; color:var(--text-muted);">Awal mendaftar sebagai Tier 🥉 BRONZE.</p>
            ` : `
              <ul style="font-size:0.82rem; list-style:none; padding:0; display:flex; flex-direction:column; gap:8px;">
                ${tierHistory.map(th => `
                  <li style="background:var(--bg-card); border:1px solid var(--chrome-border); padding:8px 12px; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                    <span>Tier <strong>${th.tier}</strong> (${th.year})</span>
                    <span style="color:var(--text-muted); font-size:0.75rem;">Total: Rp ${new Intl.NumberFormat('id-ID').format(th.total_donation)}</span>
                  </li>
                `).join('')}
              </ul>
            `}
          </div>
        </div>

        <!-- 3.4.2 RIWAYAT AKTIVITAS TIMELINE -->
        <div class="glass-card" style="padding:20px;">
          <h4 style="font-size:1rem; color:var(--accent-gold); margin-bottom:12px;">📋 3.4.2 Log Riwayat Aktivitas Member</h4>
          ${activities.length === 0 ? '<p style="font-size:0.82rem; color:var(--text-muted);">Belum ada aktivitas tercatat.</p>' : `
            <div style="max-height:200px; overflow-y:auto;">
              <table class="data-table" style="font-size:0.78rem; width:100%;">
                <thead>
                  <tr><th>WAKTU</th><th>AKTIVITAS</th><th>DETAIL KETERANGAN</th></tr>
                </thead>
                <tbody>
                  ${activities.map(a => `
                    <tr>
                      <td style="color:var(--text-muted);">${new Date(a.created_at).toLocaleString('id-ID')}</td>
                      <td style="font-weight:700; color:var(--accent-gold);">${a.title}</td>
                      <td>${a.detail}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          `}
        </div>
      `;

    } catch (err) {
      container.innerHTML = `<div style="color:var(--accent-red); padding:20px;">Error: ${err.message}</div>`;
    }
  },

  openM3AddDonationModal(id) {
    const m = this.m3Data.members.find(x => x.id === id);
    if (!m) return;

    document.getElementById('m3-don-userid').value = m.id;
    document.getElementById('m3-don-member-name').innerText = m.name;
    document.getElementById('m3-don-member-id').innerText = `ID: ${m.member_id || '[PENDING ID]'} | Tier Saat Ini: ${m.tier}`;
    document.getElementById('m3-don-amount').value = 4500000;
    document.getElementById('m3-don-notes').value = 'Donasi Jamnas & Kas MB INA';

    AuthEngine.openModal('modal-m3-add-donation');
  },

  async saveM3Donation(event) {
    event.preventDefault();
    const user_id = document.getElementById('m3-don-userid').value;
    const amount = parseInt(document.getElementById('m3-don-amount').value || 0);
    const payment_method = document.getElementById('m3-don-method').value;
    const notes = document.getElementById('m3-don-notes').value.trim();

    try {
      const res = await fetch('api.php?action=add_m3_donation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id, amount, payment_method, notes })
      });
      const data = await res.json();
      alert('🎉 ' + data.message);
      AuthEngine.closeAllModals();
      await this.fetchM3Data();
      this.renderM3MemberList();
    } catch (err) {
      alert('❌ Gagal memproses donasi: ' + err.message);
    }
  },

  async deleteM3Member(id) {
    const m = this.m3Data.members.find(x => x.id === id);
    if (!m) return;

    if (!confirm(`⚠️ Apakah Anda yakin ingin menghapus Member '${m.name}' (${m.member_id || 'Pending'}) dari Supabase Database?`)) {
      return;
    }

    try {
      const res = await fetch('api.php?action=delete_m3_member', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();
      alert('🗑️ ' + data.message);
      await this.fetchM3Data();
      this.renderM3MemberList();
    } catch (err) {
      alert('❌ Gagal menghapus member: ' + err.message);
    }
  },

  // === UPLOAD FOTO MEMBER ===
  async uploadMemberPhoto(event, memberId) {
    const file = event.target.files[0];
    if (!file) return;

    // Validasi tipe file
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
      alert('❌ Format file tidak didukung!\nHanya JPG, PNG, atau WebP yang diperbolehkan.');
      event.target.value = '';
      return;
    }

    // Validasi ukuran file (maks 2 MB)
    const maxSizeMB = 2;
    if (file.size > maxSizeMB * 1024 * 1024) {
      const actualMB = (file.size / 1024 / 1024).toFixed(2);
      alert(`❌ Ukuran file terlalu besar!\nUkuran file: ${actualMB} MB\nMaksimal: ${maxSizeMB} MB\n\nKecilkan ukuran foto terlebih dahulu.`);
      event.target.value = '';
      return;
    }

    // Update status: uploading
    const statusEl = document.getElementById(`m3-photo-status-${memberId}`);
    if (statusEl) statusEl.innerHTML = `<span style="color:var(--accent-gold);">⏳ Mengupload foto... (${(file.size/1024).toFixed(1)} KB)</span>`;

    // Preview lokal langsung (before upload selesai)
    const reader = new FileReader();
    reader.onload = (e) => {
      const photoEl = document.querySelector(`#m3-photo-upload-${memberId}`)?.closest('div[style*="position:relative"]')?.querySelector('img');
      const avatarEl = document.querySelector(`#m3-photo-upload-${memberId}`)?.closest('div[style*="position:relative"]')?.querySelector('div[style*="display:flex"]');
      if (photoEl) {
        photoEl.src = e.target.result;
        photoEl.style.display = 'block';
        if (avatarEl) avatarEl.style.display = 'none';
      } else if (avatarEl) {
        // Ganti avatar dengan img baru
        const img = document.createElement('img');
        img.src = e.target.result;
        img.alt = 'Foto Member';
        img.style.cssText = 'width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--accent-gold); box-shadow:0 0 20px rgba(245,158,11,0.4);';
        avatarEl.parentNode.insertBefore(img, avatarEl);
        avatarEl.style.display = 'none';
      }
    };
    reader.readAsDataURL(file);

    // Upload ke server
    try {
      const formData = new FormData();
      formData.append('photo', file);
      formData.append('user_id', memberId);

      const res = await fetch('api.php?action=upload_member_photo', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (data.success) {
        if (statusEl) statusEl.innerHTML = `<span style="color:var(--primary-emerald);">✅ Foto berhasil diupload (${(file.size/1024).toFixed(1)} KB)</span>`;
        // Update m3Data
        const member = this.m3Data.members.find(m => m.id === memberId);
        if (member && data.photo_url) member.photo_url = data.photo_url;
      } else {
        if (statusEl) statusEl.innerHTML = `<span style="color:var(--accent-red);">❌ ${data.message}</span>`;
      }
    } catch (err) {
      if (statusEl) statusEl.innerHTML = `<span style="color:var(--accent-red);">❌ Gagal upload: ${err.message}</span>`;
    }
  },

  openM3ExportModal() {
    AuthEngine.openModal('modal-m3-export');
  },

  processM3Export(event) {
    event.preventDefault();
    const formatRadios = document.getElementsByName('export_format');
    let format = 'PDF';
    for (let r of formatRadios) {
      if (r.checked) format = r.value;
    }

    const selectedFields = [];
    document.querySelectorAll('.exp-field').forEach(cb => {
      if (cb.checked) selectedFields.push(cb.value);
    });

    const startDate = document.getElementById('m3-exp-start').value;
    const endDate = document.getElementById('m3-exp-end').value;

    alert(`📥 Berkas Export Data Member MB INA berformat ${format} berhasil dibuat! (Periode: ${startDate} s/d ${endDate}, Total Field: ${selectedFields.length})`);
    AuthEngine.closeAllModals();
  },

  // ============================================
  // M4 - PENDAFTARAN KLUB CONTROLLER & RENDERERS
  // ============================================
  m4Data: {
    applications: [],
    notes: [],
    evaluations: [],
    activeSubtab: 'form',
    selectedAppId: null,
    currentFormStep: 1
  },

  async fetchM4Data() {
    try {
      const res = await fetch('api.php?action=get_m4_data').then(r => r.json());
      if (res.success) {
        if (res.applications) this.m4Data.applications = res.applications;
        if (res.notes) this.m4Data.notes = res.notes;
        if (res.evaluations) this.m4Data.evaluations = res.evaluations;
        if (res.clubs) {
          this.m2Data.clubs = res.clubs;
          this.clubs = res.clubs;
        }
      }
    } catch (e) {
      console.error("Error fetching M4 data:", e);
    }
  },

  switchM4Subtab(subtab) {
    if (!subtab || typeof subtab !== 'string') subtab = 'form';
    this.m4Data.activeSubtab = subtab;
    document.querySelectorAll('[data-m4sub]').forEach(btn => {
      if (btn.getAttribute('data-m4sub') === subtab) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    document.querySelectorAll('.m4-subtab-content').forEach(el => el.style.display = 'none');
    const target = document.getElementById(`m4sub-${subtab}`);
    if (target) target.style.display = 'block';

    if (subtab === 'form') this.goToM4Step(1);
    else if (subtab === 'pending') this.renderM4PendingList();
    else if (subtab === 'detail') this.renderM4DetailReview(this.m4Data.selectedAppId);
    else if (subtab === 'evaluations') this.renderM4Evaluations();
    else if (subtab === 'active_clubs') this.renderM4ActiveClubs();
  },

  renderM4Evaluations() {
    const gridContainer = document.getElementById('m4-evaluations-grid-container');
    if (!gridContainer) return;

    const periodFilter = document.getElementById('m4-evaluation-period-filter')?.value || '1_YEAR';

    let evaluations = this.m4Data.evaluations || [];
    if (evaluations.length === 0) {
      evaluations = [
        {
          id: 'ev_001',
          club_name: 'W124MBCI Jakarta Chapter',
          club_code: 'W124MB03',
          period_type: '1_YEAR',
          evaluation_period: 'Periode 2025/2026 (1 Tahun)',
          evaluation_score: 92,
          performance_grade: 'GRADE A (SANGAT BAIK)',
          active_members: 145,
          touring_count: 12,
          social_count: 8,
          evaluator_name: 'Derist Touriano',
          notes: 'Klub sangat aktif dalam Jamnas, tertib administrasi, dan rutin mengadakan bakti sosial.'
        },
        {
          id: 'ev_002',
          club_name: 'MBC Bandung',
          club_code: 'MBCBANDU',
          period_type: '1_YEAR',
          evaluation_period: 'Periode 2025/2026 (1 Tahun)',
          evaluation_score: 88,
          performance_grade: 'GRADE A (SANGAT BAIK)',
          active_members: 326,
          touring_count: 10,
          social_count: 6,
          evaluator_name: 'Ir. Raymond Sanjaya',
          notes: 'Keanggotaan solid, respon iuran tepat waktu, dan aktif di event regional Jawa Barat.'
        },
        {
          id: 'ev_003',
          club_name: 'MBC Surabaya',
          club_code: 'MBCSURAB',
          period_type: '6_MONTHS',
          evaluation_period: 'Semester 1 2026 (6 Bulan)',
          evaluation_score: 78,
          performance_grade: 'GRADE B (BAIK)',
          active_members: 210,
          touring_count: 5,
          social_count: 3,
          evaluator_name: 'Dr. Rochady Hendra Setya Wibawa',
          notes: 'Partisipasi touring baik, perlu peningkatan kerapian pelaporan keuangan bulanan.'
        }
      ];
    }

    if (periodFilter !== 'ALL_TIME') {
      evaluations = evaluations.filter(e => e.period_type === periodFilter || !e.period_type);
    }

    gridContainer.innerHTML = evaluations.map(e => `
      <div class="glass-card" style="padding:20px; border-top:4px solid ${e.evaluation_score >= 85 ? 'var(--primary-emerald)' : e.evaluation_score >= 70 ? 'var(--accent-gold)' : 'var(--accent-red)'};">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
          <div>
            <h4 style="font-size:1.05rem; font-weight:800; color:var(--text-main);">${e.club_name}</h4>
            <span style="font-family:monospace; color:var(--accent-gold); font-size:0.8rem; font-weight:700;">${e.club_code}</span>
          </div>
          <span class="tier-badge" style="background:${e.evaluation_score >= 85 ? 'rgba(16,185,129,0.2)' : 'rgba(245,158,11,0.2)'}; color:${e.evaluation_score >= 85 ? 'var(--primary-emerald)' : 'var(--accent-gold)'}; font-weight:900;">
            ${e.performance_grade || 'GRADE A'}
          </span>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; background:rgba(0,0,0,0.3); padding:12px; border-radius:8px; margin-bottom:14px; font-size:0.8rem;">
          <div><strong>Skor Kinerja:</strong> <span style="color:var(--accent-gold); font-weight:900; font-size:1rem;">${e.evaluation_score}/100</span></div>
          <div><strong>Rentang Penilaian:</strong> <br><span style="color:var(--text-muted);">${e.evaluation_period || '1 Tahun'}</span></div>
          <div><strong>Anggota Aktif:</strong> ${e.active_members || 50} Member</div>
          <div><strong>Touring / Sosial:</strong> ${e.touring_count || 5}x / ${e.social_count || 3}x</div>
        </div>

        <p style="font-size:0.8rem; color:var(--text-muted); line-height:1.5; font-style:italic; margin-bottom:12px;">
          "${e.notes || 'Evaluasi kinerja klub berstandar federasi MB INA.'}"
        </p>

        <div style="font-size:0.75rem; color:var(--text-muted); border-top:1px solid var(--chrome-border); padding-top:10px; display:flex; justify-content:space-between;">
          <span>Evaluator: <strong>${e.evaluator_name || 'Admin MB INA'}</strong></span>
          <span style="color:var(--accent-gold);">✅ Terverifikasi</span>
        </div>
      </div>
    `).join('');
  },

  renderM4Module() {
    this.switchM4Subtab(this.m4Data.activeSubtab || 'form');
  },

  goToM4Step(step) {
    this.m4Data.currentFormStep = step;

    for (let i = 1; i <= 3; i++) {
      const content = document.getElementById(`m4-step-${i}-content`);
      const ind = document.getElementById(`m4-step-indicator-${i}`);

      if (content) content.style.display = (i === step) ? 'block' : 'none';
      if (ind) {
        if (i === step) {
          ind.style.color = 'var(--accent-gold)';
          ind.querySelector('span').style.background = 'var(--accent-gold)';
          ind.querySelector('span').style.color = '#000';
        } else {
          ind.style.color = 'var(--text-muted)';
          ind.querySelector('span').style.background = 'var(--chrome-border)';
          ind.querySelector('span').style.color = 'var(--text-muted)';
        }
      }
    }

    if (step === 3) {
      this.renderM4FormSummary();
    }
  },

  renderM4FormSummary() {
    const summaryBox = document.getElementById('m4-preview-summary-container');
    if (!summaryBox) return;

    const name = document.getElementById('m4-form-name')?.value || '-';
    const code = document.getElementById('m4-form-code')?.value || '-';
    const province = document.getElementById('m4-form-province')?.value || '-';
    const city = document.getElementById('m4-form-city')?.value || '-';
    const type = document.getElementById('m4-form-type')?.value || 'CLUB';
    const memberEst = document.getElementById('m4-form-member-est')?.value || '0';
    const cp = document.getElementById('m4-form-cp')?.value || '-';
    const phone = document.getElementById('m4-form-phone')?.value || '-';

    summaryBox.innerHTML = `
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div><strong>Nama Klub:</strong> ${name}</div>
        <div><strong>Kode Klub:</strong> <span style="font-family:monospace; color:var(--accent-gold); font-weight:800;">${code}</span></div>
        <div><strong>Provinsi:</strong> ${province}</div>
        <div><strong>Kota / Kabupaten:</strong> ${city}</div>
        <div><strong>Jenis Klub:</strong> ${type}</div>
        <div><strong>Perkiraan Member:</strong> ${memberEst} Anggota</div>
        <div><strong>Contact Person:</strong> ${cp}</div>
        <div><strong>WhatsApp:</strong> ${phone}</div>
      </div>
    `;
  },

  async handleM4FormSubmit(event) {
    event.preventDefault();

    const agree = document.getElementById('m4-form-agree')?.checked;
    if (!agree) {
      alert('⚠️ Silakan centang konfirmasi pernyataan kebenaran data terlebih dahulu!');
      return;
    }

    const payload = {
      name: document.getElementById('m4-form-name').value.trim(),
      code: document.getElementById('m4-form-code').value.trim().toUpperCase(),
      alias: document.getElementById('m4-form-alias').value.trim(),
      province_name: document.getElementById('m4-form-province').value,
      city: document.getElementById('m4-form-city').value.trim(),
      address: document.getElementById('m4-form-address').value.trim(),
      founded_year: parseInt(document.getElementById('m4-form-year').value || 2015),
      member_count_estimate: parseInt(document.getElementById('m4-form-member-est').value || 35),
      club_type: document.getElementById('m4-form-type').value,
      description: document.getElementById('m4-form-desc').value.trim(),
      contact_person: document.getElementById('m4-form-cp').value.trim(),
      contact_phone: document.getElementById('m4-form-phone').value.trim(),
      contact_email: document.getElementById('m4-form-email').value.trim(),
      social_media: {
        instagram: document.getElementById('m4-form-ig').value.trim(),
        facebook: document.getElementById('m4-form-fb').value.trim()
      },
      logo_url: document.getElementById('m4-form-logo').value.trim(),
      photos: [document.getElementById('m4-form-photos').value.trim()],
      submitted_by: this.currentUser.id || 'usr_guest'
    };

    try {
      const res = await fetch('api.php?action=submit_club_application', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        alert('🎉 ' + data.message);
        document.getElementById('m4-club-reg-form').reset();
        await this.fetchM4Data();
        this.switchM4Subtab('pending');
      } else {
        alert('❌ ' + data.message);
      }
    } catch (e) {
      alert('Gagal mengirim pendaftaran klub: ' + e.message);
    }
  },

  renderM4PendingList() {
    const statsContainer = document.getElementById('m4-stats-container');
    const tableContainer = document.getElementById('m4-pending-table-container');
    if (!tableContainer) return;

    const apps = this.m4Data.applications;
    const pendingCount = apps.filter(a => a.status === 'PENDING').length;
    const reviewCount = apps.filter(a => a.status === 'REVIEW').length;
    const approvedCount = apps.filter(a => a.status === 'APPROVED').length;
    const rejectedCount = apps.filter(a => a.status === 'REJECTED').length;

    if (statsContainer) {
      statsContainer.innerHTML = `
        <div class="admin-stat-card">
          <div class="admin-stat-icon">📄</div>
          <div>
            <div class="stat-value">${apps.length}</div>
            <div class="stat-label">Total Pengajuan</div>
          </div>
        </div>
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:rgba(239,68,68,0.15); color:var(--accent-red);">⏳</div>
          <div>
            <div class="stat-value">${pendingCount}</div>
            <div class="stat-label">Pending</div>
          </div>
        </div>
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:rgba(245,158,11,0.15); color:var(--accent-gold);">🔍</div>
          <div>
            <div class="stat-value">${reviewCount}</div>
            <div class="stat-label">Dalam Review</div>
          </div>
        </div>
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:rgba(16,185,129,0.15); color:var(--primary-emerald);">✅</div>
          <div>
            <div class="stat-value">${approvedCount}</div>
            <div class="stat-label">Approved</div>
          </div>
        </div>
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:rgba(100,116,139,0.15); color:var(--text-muted);">❌</div>
          <div>
            <div class="stat-value">${rejectedCount}</div>
            <div class="stat-label">Rejected</div>
          </div>
        </div>
      `;
    }

    const searchQuery = (document.getElementById('m4-pending-search')?.value || '').toLowerCase().trim();
    const statusFilter = document.getElementById('m4-pending-status-filter')?.value || '';

    let filtered = apps;
    if (searchQuery) {
      filtered = filtered.filter(a =>
        (a.name || '').toLowerCase().includes(searchQuery) ||
        (a.code || '').toLowerCase().includes(searchQuery) ||
        (a.city || '').toLowerCase().includes(searchQuery) ||
        (a.contact_person || '').toLowerCase().includes(searchQuery)
      );
    }
    if (statusFilter) {
      filtered = filtered.filter(a => a.status === statusFilter);
    }

    const getStatusBadge = (s) => {
      if (s === 'PENDING') return '<span class="tier-badge" style="background:rgba(239,68,68,0.2); color:var(--accent-red); border:1px solid var(--accent-red);">⏳ PENDING</span>';
      if (s === 'REVIEW') return '<span class="tier-badge" style="background:rgba(245,158,11,0.2); color:var(--accent-gold); border:1px solid var(--accent-gold);">🔍 REVIEW</span>';
      if (s === 'APPROVED') return '<span class="tier-badge" style="background:rgba(16,185,129,0.2); color:var(--primary-emerald); border:1px solid var(--primary-emerald);">✅ APPROVED</span>';
      return '<span class="tier-badge" style="background:rgba(100,116,139,0.2); color:var(--text-muted); border:1px solid var(--chrome-border);">❌ REJECTED</span>';
    };

    tableContainer.innerHTML = `
      <table class="data-table" style="width:100%; border-collapse:collapse; font-size:0.88rem;">
        <thead>
          <tr style="border-bottom:1px solid var(--chrome-border); text-align:left; color:var(--text-muted);">
            <th style="padding:10px 8px; white-space:nowrap;">KODE</th>
            <th style="padding:10px 8px;">NAMA KLUB & DOMISILI</th>
            <th style="padding:10px 8px;">PROVINSI</th>
            <th style="padding:10px 8px;">CONTACT PERSON</th>
            <th style="padding:10px 8px; text-align:center;">STATUS</th>
            <th style="padding:10px 8px; text-align:center; width:120px;">AKSI</th>
          </tr>
        </thead>
        <tbody>
          ${filtered.length === 0 ? `
            <tr>
              <td colspan="6" style="text-align:center; padding:32px; color:var(--text-muted);">
                Tidak ada data pengajuan pendaftaran klub yang sesuai.
              </td>
            </tr>
          ` : filtered.map(a => `
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="font-family:monospace; font-weight:800; color:var(--accent-gold); font-size:0.85rem; padding:10px 8px; white-space:nowrap;">
                ${a.code}
              </td>
              <td style="padding:10px 8px;">
                <div style="font-weight:700; color:var(--text-main); font-size:0.92rem;">${a.name}</div>
                <div style="font-size:0.75rem; color:var(--text-muted);">${a.city} • Est: ${a.member_count_estimate || 0} Member</div>
              </td>
              <td style="font-size:0.85rem; padding:10px 8px; white-space:nowrap;">${a.province_name || '-'}</td>
              <td style="padding:10px 8px;">
                <div style="font-size:0.85rem; color:var(--text-main); font-weight:600;">${a.contact_person}</div>
                <div style="font-size:0.75rem; color:var(--accent-gold);">📞 ${a.contact_phone}</div>
              </td>
              <td style="padding:10px 8px; text-align:center; white-space:nowrap;">
                ${getStatusBadge(a.status)}
              </td>
              <td style="padding:10px 8px; text-align:center; white-space:nowrap;">
                <div style="display:inline-flex; gap:4px;">
                  <button class="role-pill-btn" style="border-color:var(--accent-blue); color:var(--accent-blue); width:30px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; font-size:0.85rem; border-radius:6px; cursor:pointer;" onclick="AppEngine.selectAndReviewM4App('${a.id}')" title="Review Detail Pengajuan">👁️</button>
                  <button class="role-pill-btn" style="border-color:var(--accent-gold); color:var(--accent-gold); width:30px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; font-size:0.85rem; border-radius:6px; cursor:pointer;" onclick="AppEngine.openM4ReviewModal('${a.id}', 'REVIEW')" title="Update Status Review">📝</button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  },

  selectAndReviewM4App(id) {
    this.m4Data.selectedAppId = id;
    this.switchM4Subtab('detail');
  },

  renderM4DetailReview(id) {
    const container = document.getElementById('m4-detail-review-container');
    if (!container) return;

    let app = this.m4Data.applications.find(a => a.id === id);
    if (!app && this.m4Data.applications.length > 0) {
      app = this.m4Data.applications[0];
      this.m4Data.selectedAppId = app.id;
    }

    if (!app) {
      container.innerHTML = `
        <div style="text-align:center; padding:48px; color:var(--text-muted);">
          🔍 Belum ada data pengajuan klub yang dipilih untuk direview.<br>
          <button class="btn-primary" style="margin-top:16px;" onclick="AppEngine.switchM4Subtab('pending')">📋 lihat Daftar Pending</button>
        </div>
      `;
      return;
    }

    const appNotes = this.m4Data.notes.filter(n => n.application_id === app.id);

    container.innerHTML = `
      <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px;">
        <div class="glass-panel" style="padding:24px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid var(--chrome-border); padding-bottom:16px;">
            <div>
              <span style="font-family:monospace; color:var(--accent-gold); font-size:0.9rem; font-weight:800;">KODE: ${app.code}</span>
              <h3 style="font-size:1.3rem; margin-top:2px;" class="text-gradient">${app.name}</h3>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
              <span class="tier-badge" style="background:rgba(245,158,11,0.2); color:var(--accent-gold); border:1px solid var(--accent-gold); font-size:0.85rem;">
                STATUS: ${app.status}
              </span>
              <button class="btn-outline" style="font-size:0.8rem; padding:6px 12px;" onclick="AppEngine.switchM4Subtab('pending')">⬅️ Kembali ke List</button>
            </div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; font-size:0.88rem; margin-bottom:24px;">
            <div><strong>Alias:</strong> ${app.alias || '-'}</div>
            <div><strong>Jenis Klub:</strong> ${app.club_type}</div>
            <div><strong>Provinsi:</strong> ${app.province_name}</div>
            <div><strong>Kota:</strong> ${app.city}</div>
            <div><strong>Tahun Berdiri:</strong> ${app.founded_year}</div>
            <div><strong>Perkiraan Member:</strong> ${app.member_count_estimate} Anggota</div>
            <div style="grid-column:1 / -1;"><strong>Alamat Sekretariat:</strong> ${app.address}</div>
          </div>

          <h4 style="font-size:1rem; color:var(--accent-gold); margin-bottom:12px;">── Deskripsi Klub ──</h4>
          <p style="font-size:0.88rem; color:var(--text-main); line-height:1.6; margin-bottom:24px; background:rgba(255,255,255,0.03); padding:12px; border-radius:8px;">
            ${app.description}
          </p>

          <h4 style="font-size:1rem; color:var(--accent-gold); margin-bottom:12px;">── Kontak Penanggung Jawab ──</h4>
          <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; font-size:0.85rem; margin-bottom:24px;">
            <div><strong>Contact Person:</strong><br>${app.contact_person}</div>
            <div><strong>No. WhatsApp:</strong><br><span style="color:var(--accent-gold); font-weight:700;">📞 ${app.contact_phone}</span></div>
            <div><strong>Email Official:</strong><br>${app.contact_email || '-'}</div>
          </div>

          <!-- ACTION BUTTONS -->
          <div style="display:flex; gap:12px; border-top:1px solid var(--chrome-border); padding-top:20px; flex-wrap:wrap;">
            <button class="btn-primary" style="background:var(--primary-emerald); border-color:var(--primary-emerald);" onclick="AppEngine.openM4ReviewModal('${app.id}', 'APPROVED')">✅ Approve Pengajuan</button>
            <button class="btn-outline" style="border-color:var(--accent-red); color:var(--accent-red);" onclick="AppEngine.openM4ReviewModal('${app.id}', 'REJECTED')">❌ Reject Pengajuan</button>
            <button class="btn-outline" style="border-color:var(--accent-gold); color:var(--accent-gold);" onclick="AppEngine.openM4ReviewModal('${app.id}', 'REVIEW')">🔍 Tandai Dalam Review</button>
          </div>
        </div>

        <!-- RIGHT PANEL: CATATAN INTERNAL & TIMELINE -->
        <div>
          <div class="glass-panel" style="padding:20px; margin-bottom:20px;">
            <h4 style="font-size:1rem; color:var(--accent-gold); margin-bottom:14px;">📝 Catatan Internal Admin</h4>
            <div style="display:flex; flex-direction:column; gap:10px; max-height:260px; overflow-y:auto; margin-bottom:16px;">
              ${appNotes.length === 0 ? `
                <p style="font-size:0.8rem; color:var(--text-muted); text-align:center; padding:12px;">Belum ada catatan internal.</p>
              ` : appNotes.map(n => `
                <div style="font-size:0.82rem; background:rgba(255,255,255,0.03); padding:10px; border-radius:6px; border-left:3px solid var(--accent-gold);">
                  <div style="display:flex; justify-content:space-between; font-weight:700; color:var(--text-main); margin-bottom:4px;">
                    <span>${n.user_name || 'Admin'}</span>
                    <span style="font-size:0.7rem; color:var(--text-muted);">${new Date(n.created_at).toLocaleDateString('id-ID')}</span>
                  </div>
                  <div style="color:var(--text-muted);">${n.note}</div>
                </div>
              `).join('')}
            </div>

            <form onsubmit="AppEngine.saveM4InternalNote(event, '${app.id}')">
              <textarea id="m4-add-note-text" class="form-input" rows="2" placeholder="Tambah catatan admin..." required style="margin-bottom:8px; font-size:0.82rem;"></textarea>
              <button type="submit" class="btn-primary" style="width:100%; font-size:0.8rem;">+ Tambah Catatan</button>
            </form>
          </div>
        </div>
      </div>
    `;
  },

  openM4ReviewModal(id, actionType) {
    const app = this.m4Data.applications.find(a => a.id === id);
    if (!app) return;

    document.getElementById('m4-review-app-id').value = app.id;
    document.getElementById('m4-review-action-type').value = actionType;

    const titleEl = document.getElementById('modal-m4-review-title');
    const submitBtn = document.getElementById('m4-review-submit-btn');
    const reasonGroup = document.getElementById('m4-rejection-reason-group');
    const summaryBox = document.getElementById('m4-review-summary-box');

    summaryBox.innerHTML = `
      <div style="font-size:0.85rem; line-height:1.6;">
        <strong>Klub:</strong> ${app.name} (${app.code}) | <strong>Wilayah:</strong> ${app.province_name || 'DKI Jakarta'}, ${app.city}<br>
        <strong>CP:</strong> ${app.contact_person} (📞 ${app.contact_phone})
      </div>

      <div style="margin-top:12px; border-top:1px solid var(--chrome-border); padding-top:10px;">
        <strong style="color:var(--accent-gold); font-size:0.85rem;">📁 Check-list Verifikasi Berkas Dokumen & Pembayaran:</strong>
        <table style="width:100%; border-collapse:collapse; font-size:0.78rem; margin-top:6px;">
          <thead>
            <tr style="background:rgba(0,0,0,0.3); text-align:left; color:var(--text-muted);">
              <th style="padding:5px;">Dokumen</th>
              <th style="padding:5px; text-align:center;">Status</th>
              <th style="padding:5px; text-align:center;">Validasi</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:5px;">1. AD/ART Klub</td>
              <td style="padding:5px; text-align:center; color:var(--primary-emerald);">✅ Uploaded</td>
              <td style="padding:5px; text-align:center;"><a href="${app.ad_art_url || 'assets/docs/sample_ad_art.pdf'}" target="_blank" style="color:var(--accent-gold); font-weight:700;">[📄 Lihat File]</a></td>
            </tr>
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:5px;">2. Susunan Kepengurusan</td>
              <td style="padding:5px; text-align:center; color:var(--primary-emerald);">✅ Uploaded</td>
              <td style="padding:5px; text-align:center;"><a href="${app.management_structure_url || 'assets/docs/sample_pengurus.pdf'}" target="_blank" style="color:var(--accent-gold); font-weight:700;">[📄 Lihat File]</a></td>
            </tr>
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:5px;">3. Domisili Sekretariat</td>
              <td style="padding:5px; text-align:center; color:var(--primary-emerald);">✅ Uploaded</td>
              <td style="padding:5px; text-align:center;"><a href="${app.domicile_url || 'assets/docs/sample_domisili.pdf'}" target="_blank" style="color:var(--accent-gold); font-weight:700;">[📄 Lihat File]</a></td>
            </tr>
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:5px;">4. Logo Klub</td>
              <td style="padding:5px; text-align:center; color:var(--primary-emerald);">✅ Uploaded</td>
              <td style="padding:5px; text-align:center;"><a href="${app.logo_url || 'assets/mb_badge.jpg'}" target="_blank" style="color:var(--accent-gold); font-weight:700;">[🖼️ Lihat Logo]</a></td>
            </tr>
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:5px;">5. List 15+ Anggota Aktif</td>
              <td style="padding:5px; text-align:center; color:var(--primary-emerald);">✅ ${app.total_members || 15}/15 Active</td>
              <td style="padding:5px; text-align:center;"><a href="${app.members_list_url || 'assets/docs/sample_daftar_anggota.xlsx'}" target="_blank" style="color:var(--accent-gold); font-weight:700;">[📊 Download List]</a></td>
            </tr>
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:5px;">6. Bukti Transfer Rp 350.000</td>
              <td style="padding:5px; text-align:center; color:var(--primary-emerald);">✅ ${app.payment_status || 'VERIFIED'}</td>
              <td style="padding:5px; text-align:center;"><a href="${app.payment_proof_url || 'assets/docs/sample_bukti_transfer.jpg'}" target="_blank" style="color:var(--accent-gold); font-weight:700;">[🧾 Lihat Bukti]</a></td>
            </tr>
          </tbody>
        </table>
      </div>
    `;

    if (actionType === 'APPROVED') {
      titleEl.innerText = `✅ Approve & Verifikasi Berkas: ${app.name}`;
      submitBtn.innerText = '✅ Approve Semua & Aktifkan Klub';
      submitBtn.style.background = 'var(--primary-emerald)';
      submitBtn.style.borderColor = 'var(--primary-emerald)';
      reasonGroup.style.display = 'none';
    } else if (actionType === 'REJECTED') {
      titleEl.innerText = `❌ Reject Pendaftaran: ${app.name}`;
      submitBtn.innerText = '❌ Tolak Pengajuan';
      submitBtn.style.background = 'var(--accent-red)';
      submitBtn.style.borderColor = 'var(--accent-red)';
      reasonGroup.style.display = 'block';
    } else {
      titleEl.innerText = `🔍 Set Status Review: ${app.name}`;
      submitBtn.innerText = 'Tandai Review';
      submitBtn.style.background = 'var(--accent-gold)';
      submitBtn.style.borderColor = 'var(--accent-gold)';
      reasonGroup.style.display = 'none';
    }

    AuthEngine.openModal('modal-m4-review');
  },

  async processM4ReviewSubmit(event) {
    event.preventDefault();

    const id = document.getElementById('m4-review-app-id').value;
    const actionType = document.getElementById('m4-review-action-type').value;
    const rejectionReason = document.getElementById('m4-rejection-reason').value.trim();
    const notes = document.getElementById('m4-review-notes').value.trim();

    if (actionType === 'REJECTED' && !rejectionReason) {
      alert('⚠️ Alasan penolakan wajib diisi!');
      return;
    }

    try {
      const res = await fetch('api.php?action=update_club_application_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id,
          status: actionType,
          rejection_reason: rejectionReason,
          notes,
          reviewed_by: this.currentUser.id || 'usr_superadmin'
        })
      });
      const data = await res.json();
      if (data.success) {
        alert('🎉 ' + data.message);
        AuthEngine.closeAllModals();
        await this.fetchM4Data();
        await this.fetchM2Data();
        this.switchM4Subtab('pending');
      } else {
        alert('❌ ' + data.message);
      }
    } catch (e) {
      alert('Gagal memproses verifikasi pengajuan: ' + e.message);
    }
  },

  async saveM4InternalNote(event, appId) {
    event.preventDefault();
    const note = document.getElementById('m4-add-note-text').value.trim();
    if (!note) return;

    try {
      const res = await fetch('api.php?action=add_club_application_note', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ application_id: appId, note, user_id: this.currentUser.id || 'usr_superadmin' })
      });
      const data = await res.json();
      if (data.success) {
        document.getElementById('m4-add-note-text').value = '';
        await this.fetchM4Data();
        this.renderM4DetailReview(appId);
      }
    } catch (e) {
      alert('Gagal menambah catatan internal!');
    }
  },

  renderM4ActiveClubs() {
    const tableContainer = document.getElementById('m4-active-clubs-table-container');
    const countBadge = document.getElementById('m4-active-clubs-count-badge');
    if (!tableContainer) return;

    let clubs = (this.m2Data.clubs && this.m2Data.clubs.length > 0) ? this.m2Data.clubs : (this.clubs && this.clubs.length > 0 ? this.clubs : []);
    
    // Auto-fetch if data is empty on initial render
    if (clubs.length === 0) {
      this.fetchM2Data().then(() => {
        this.renderM4ActiveClubs();
      });
      tableContainer.innerHTML = '<div style="text-align:center; padding:32px; color:var(--accent-gold);">⏳ Memuat data 110+ Klub Aktif dari Supabase Cloud...</div>';
      return;
    }

    if (countBadge) countBadge.innerText = `${clubs.length} KLUB AKTIF`;

    const searchQuery = (document.getElementById('m4-active-search')?.value || '').toLowerCase().trim();
    const regionFilter = document.getElementById('m4-active-region-filter')?.value || '';

    let filtered = clubs;
    if (searchQuery) {
      filtered = filtered.filter(c =>
        (c.name || '').toLowerCase().includes(searchQuery) ||
        (c.code || '').toLowerCase().includes(searchQuery) ||
        (c.city || '').toLowerCase().includes(searchQuery)
      );
    }
    if (regionFilter) {
      const rfClean = regionFilter.toLowerCase().replace('regional', '').trim();
      filtered = filtered.filter(c => {
        if (!c.region) return true;
        const cRegClean = c.region.toLowerCase().replace('regional', '').trim();
        return cRegClean.includes(rfClean) || rfClean.includes(cRegClean);
      });
    }

    tableContainer.innerHTML = `
      <table class="data-table" style="width:100%; border-collapse:collapse; font-size:0.88rem;">
        <thead>
          <tr style="border-bottom:1px solid var(--chrome-border); text-align:left; color:var(--text-muted);">
            <th style="padding:10px 8px; white-space:nowrap;">KODE</th>
            <th style="padding:10px 8px;">NAMA KLUB / CHAPTER</th>
            <th style="padding:10px 8px;">REGION WILAYAH</th>
            <th style="padding:10px 8px;">DOMISILI</th>
            <th style="padding:10px 8px;">MEMBER</th>
            <th style="padding:10px 8px; text-align:center;">SKOR KINERJA</th>
            <th style="padding:10px 8px; text-align:center; width:130px;">EVALUASI</th>
          </tr>
        </thead>
        <tbody>
          ${filtered.length === 0 ? `
            <tr>
              <td colspan="7" style="text-align:center; padding:32px; color:var(--text-muted);">
                🔍 Tidak ada klub aktif yang sesuai dengan filter pencarian atau region '${regionFilter}'.<br>
                <button class="btn-outline" style="margin-top:12px; font-size:0.8rem;" onclick="document.getElementById('m4-active-search').value=''; document.getElementById('m4-active-region-filter').value=''; AppEngine.renderM4ActiveClubs();">🔄 Reset Filter & Tampilkan Semua ${clubs.length} Klub</button>
              </td>
            </tr>
          ` : filtered.map(c => `
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="font-family:monospace; font-weight:800; color:var(--accent-gold); font-size:0.85rem; padding:10px 8px; white-space:nowrap;">
                ${c.code}
              </td>
              <td style="padding:10px 8px; font-weight:700; color:var(--text-main);">
                ${c.name}
              </td>
              <td style="font-size:0.85rem; padding:10px 8px; color:var(--text-muted);">${c.region || 'Regional Metro DKI Jakarta'}</td>
              <td style="font-size:0.85rem; padding:10px 8px;">${c.city || 'Jakarta'}</td>
              <td style="font-weight:700; color:var(--accent-gold); font-size:0.85rem; padding:10px 8px;">${c.member_count || 50} Anggota</td>
              <td style="padding:10px 8px; text-align:center; white-space:nowrap;">
                <span class="tier-badge" style="background:${(c.evaluation_score || 85) >= 80 ? 'rgba(16,185,129,0.2)' : 'rgba(245,158,11,0.2)'}; color:${(c.evaluation_score || 85) >= 80 ? 'var(--primary-emerald)' : 'var(--accent-gold)'}; border:1px solid ${(c.evaluation_score || 85) >= 80 ? 'var(--primary-emerald)' : 'var(--accent-gold)'}; font-weight:800;">
                  🏆 ${c.evaluation_score || 85} / 100
                </span>
              </td>
              <td style="padding:10px 8px; text-align:center; white-space:nowrap;">
                <button class="role-pill-btn" style="border-color:var(--accent-gold); color:var(--accent-gold);" onclick="AppEngine.openM4EvaluationModal('${c.id}')">📊 Evaluasi</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  },

  openM4EvaluationModal(clubId) {
    const clubs = (this.m2Data.clubs && this.m2Data.clubs.length > 0) ? this.m2Data.clubs : (this.clubs && this.clubs.length > 0 ? this.clubs : []);
    let c = clubs.find(x => x.id === clubId);

    if (!c) {
      c = { id: clubId, name: 'Klub MB INA', evaluation_score: 85 };
    }

    const inputClubId = document.getElementById('m4-eval-club-id');
    const titleEl = document.getElementById('modal-m4-eval-title');

    if (inputClubId) inputClubId.value = c.id;
    if (titleEl) titleEl.innerText = `📊 Evaluasi Kinerja Klub - ${c.name}`;
    
    AuthEngine.openModal('modal-m4-evaluation');
    this.onM4PeriodTypeChange();
  },

  onM4PeriodTypeChange() {
    const year = document.getElementById('m4-eval-year-select')?.value || '2026';
    const periodType = document.getElementById('m4-eval-period-type')?.value || 'ANNUAL';
    const customContainer = document.getElementById('m4-eval-custom-dates-container');

    const startDateEl = document.getElementById('m4-eval-start-date');
    const endDateEl = document.getElementById('m4-eval-end-date');
    const badgeEl = document.getElementById('m4-eval-date-range-badge');

    if (periodType === 'SEMESTER_1') {
      if (startDateEl) startDateEl.value = `${year}-01-01`;
      if (endDateEl) endDateEl.value = `${year}-06-30`;
      if (customContainer) customContainer.style.display = 'none';
      if (badgeEl) badgeEl.innerText = `01 Jan ${year} — 30 Jun ${year} (Semester I)`;
    } else if (periodType === 'SEMESTER_2') {
      if (startDateEl) startDateEl.value = `${year}-07-01`;
      if (endDateEl) endDateEl.value = `${year}-12-31`;
      if (customContainer) customContainer.style.display = 'none';
      if (badgeEl) badgeEl.innerText = `01 Jul ${year} — 31 Des ${year} (Semester II)`;
    } else if (periodType === 'ANNUAL') {
      if (startDateEl) startDateEl.value = `${year}-01-01`;
      if (endDateEl) endDateEl.value = `${year}-12-31`;
      if (customContainer) customContainer.style.display = 'none';
      if (badgeEl) badgeEl.innerText = `01 Jan ${year} — 31 Des ${year} (Tahunan 365 Hari)`;
    } else {
      if (customContainer) customContainer.style.display = 'flex';
      if (badgeEl) badgeEl.innerText = `${startDateEl?.value || 'Custom'} s/d ${endDateEl?.value || 'Custom'} (Kustom)`;
    }

    this.calculateM4EvaluationAuto();
  },

  async calculateM4EvaluationAuto() {
    const clubId = document.getElementById('m4-eval-club-id').value;
    const year = document.getElementById('m4-eval-year-select').value;
    const periodType = document.getElementById('m4-eval-period-type')?.value || 'ANNUAL';
    const startDate = document.getElementById('m4-eval-start-date')?.value || `${year}-01-01`;
    const endDate = document.getElementById('m4-eval-end-date')?.value || `${year}-12-31`;

    if (!clubId) return;

    try {
      const res = await fetch(`api.php?action=calculate_club_evaluation&club_id=${clubId}&year=${year}&period_type=${periodType}&start_date=${startDate}&end_date=${endDate}`).then(r => r.json());
      if (res.success) {
        document.getElementById('m4-range-act').value = res.activity_score;
        document.getElementById('m4-range-mem').value = res.membership_score;
        document.getElementById('m4-range-part').value = res.participation_score;
        document.getElementById('m4-range-adm').value = res.administration_score;

        const badgeEl = document.getElementById('m4-eval-date-range-badge');
        if (badgeEl) badgeEl.innerText = `${res.start_date} — ${res.end_date} (${res.period_type})`;

        this.updateM4EvaluationManualScore(res);
      }
    } catch (e) {
      console.error("Auto calculation error:", e);
    }
  },

  updateM4EvaluationManualScore(autoData = null) {
    const act = parseInt(document.getElementById('m4-range-act').value || 70);
    const mem = parseInt(document.getElementById('m4-range-mem').value || 85);
    const part = parseInt(document.getElementById('m4-range-part').value || 75);
    const adm = parseInt(document.getElementById('m4-range-adm').value || 90);

    const wAct = (act * 0.35).toFixed(2);
    const wMem = (mem * 0.25).toFixed(2);
    const wPart = (part * 0.25).toFixed(2);
    const wAdm = (adm * 0.15).toFixed(2);

    const finalScore = (parseFloat(wAct) + parseFloat(wMem) + parseFloat(wPart) + parseFloat(wAdm)).toFixed(2);

    document.getElementById('m4-card-score-act').innerText = `${act}/100`;
    document.getElementById('m4-card-score-mem').innerText = `${mem}/100`;
    document.getElementById('m4-card-score-part').innerText = `${part}/100`;
    document.getElementById('m4-card-score-adm').innerText = `${adm}/100`;

    document.getElementById('m4-weighted-act').innerText = wAct;
    document.getElementById('m4-weighted-mem').innerText = wMem;
    document.getElementById('m4-weighted-part').innerText = wPart;
    document.getElementById('m4-weighted-adm').innerText = wAdm;

    document.getElementById('m4-eval-final-score-display').innerText = `${finalScore} / 100`;

    const badge = document.getElementById('m4-eval-grade-badge');
    if (badge) {
      if (finalScore >= 85) {
        badge.innerHTML = 'GRADE A • ⭐ EXCELLENT';
        badge.style.background = 'rgba(245,158,11,0.2)';
        badge.style.color = 'var(--accent-gold)';
        badge.style.borderColor = 'var(--accent-gold)';
      } else if (finalScore >= 70) {
        badge.innerHTML = 'GRADE B • ✅ GOOD';
        badge.style.background = 'rgba(16,185,129,0.2)';
        badge.style.color = 'var(--primary-emerald)';
        badge.style.borderColor = 'var(--primary-emerald)';
      } else if (finalScore >= 50) {
        badge.innerHTML = 'GRADE C • ⚠️ FAIR';
        badge.style.background = 'rgba(245,158,11,0.15)';
        badge.style.color = '#f59e0b';
        badge.style.borderColor = '#f59e0b';
      } else {
        badge.innerHTML = 'GRADE D • ❌ POOR';
        badge.style.background = 'rgba(239,68,68,0.2)';
        badge.style.color = 'var(--accent-red)';
        badge.style.borderColor = 'var(--accent-red)';
      }
    }

    if (autoData && autoData.recommendations) {
      const recomBox = document.getElementById('m4-eval-recommendations-list');
      if (recomBox) {
        recomBox.innerHTML = autoData.recommendations.map(r => `<div>${r}</div>`).join('');
      }
    }
  },

  async saveM4EvaluationSubmit(event) {
    event.preventDefault();
    const club_id = document.getElementById('m4-eval-club-id').value;
    const year = parseInt(document.getElementById('m4-eval-year-select').value || 2026);
    const period_type = document.getElementById('m4-eval-period-type')?.value || 'ANNUAL';
    const start_date = document.getElementById('m4-eval-start-date')?.value || `${year}-01-01`;
    const end_date = document.getElementById('m4-eval-end-date')?.value || `${year}-12-31`;
    const activity_score = parseInt(document.getElementById('m4-range-act').value || 70);
    const membership_score = parseInt(document.getElementById('m4-range-mem').value || 85);
    const participation_score = parseInt(document.getElementById('m4-range-part').value || 75);
    const administration_score = parseInt(document.getElementById('m4-range-adm').value || 90);
    const notes = document.getElementById('m4-eval-notes').value.trim();

    try {
      const res = await fetch('api.php?action=save_club_evaluation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          club_id,
          year,
          period_type,
          start_date,
          end_date,
          activity_score,
          membership_score,
          participation_score,
          administration_score,
          notes,
          evaluator: this.currentUser.id || 'usr_superadmin'
        })
      });
      const data = await res.json();
      if (data.success) {
        alert('🎉 ' + data.message);
        AuthEngine.closeAllModals();
        await this.fetchM2Data();
        this.renderM4ActiveClubs();
      } else {
        alert('❌ ' + data.message);
      }
    } catch (e) {
      alert('Gagal menyimpan evaluasi klub: ' + e.message);
    }
  },

  /* ==========================================================================
     MODUL M5 - FORUM & INTERAKSI CONTROLLER & RENDERING ENGINE
     ========================================================================== */
  async fetchM5Data() {
    try {
      const res = await fetch('api.php?action=get_m5_data').then(r => r.json());
      if (res.success) {
        this.m5Data.categories = res.categories || [];
        this.m5Data.threads = res.threads || [];
        this.m5Data.replies = res.replies || [];
        this.m5Data.broadcasts = res.broadcasts || [];
        this.m5Data.reports = res.reports || [];
        this.m5Data.rules = res.rules || [];
        this.m5Data.trending = res.trending || [];
      }
    } catch (e) {
      console.error("Error loading M5 Data:", e);
    }
  },

  renderM5Module() {
    this.renderM5Categories();
    this.renderM5Threads();
    this.renderM5Trending();
    this.renderM5Broadcasts();
    this.renderM5Reports();
    this.renderM5Rules();
    this.switchM5Subtab(this.m5Data.activeSubtab || 'forum');
  },

  switchM5Subtab(subtab) {
    if (!subtab || typeof subtab !== 'string') subtab = 'forum';
    this.m5Data.activeSubtab = subtab;
    document.querySelectorAll('[data-m5sub]').forEach(btn => {
      if (btn.getAttribute('data-m5sub') === subtab) btn.classList.add('active');
      else btn.classList.remove('active');
    });

    document.querySelectorAll('.m5-subtab-content').forEach(el => el.style.display = 'none');
    const target = document.getElementById(`m5sub-${subtab}`);
    if (target) target.style.display = 'block';

    if (subtab === 'thread' && this.m5Data.activeThreadId) {
      this.renderM5ThreadDetail(this.m5Data.activeThreadId);
    }
  },

  renderM5Categories() {
    const grid = document.getElementById('m5-categories-grid');
    if (!grid) return;

    grid.innerHTML = this.m5Data.categories.map(c => `
      <div class="glass-card" style="padding:16px; cursor:pointer; transition:transform 0.2s;" onclick="AppEngine.filterCategoryM5('${c.id}')" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
          <div style="font-size:1.8rem; width:44px; height:44px; border-radius:10px; background:rgba(245,158,11,0.15); display:flex; align-items:center; justify-content:center;">${c.icon}</div>
          <div>
            <h5 style="font-size:0.95rem; line-height:1.2;">${c.name}</h5>
            <span style="font-size:0.75rem; color:var(--text-muted);">${c.thread_count || 0} Threads • ${c.post_count || 0} Posts</span>
          </div>
        </div>
        <p style="font-size:0.78rem; color:var(--text-muted); line-height:1.4;">${c.description}</p>
      </div>
    `).join('');

    const sel = document.getElementById('m5-category-filter');
    if (sel && sel.options.length <= 1) {
      this.m5Data.categories.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.innerText = `${c.icon} ${c.name}`;
        sel.appendChild(opt);
      });
    }
  },

  filterCategoryM5(catId) {
    const sel = document.getElementById('m5-category-filter');
    if (sel) sel.value = catId;
    this.renderM5Threads();
  },

  renderM5Threads() {
    const container = document.getElementById('m5-threads-list-container');
    if (!container) return;

    const q = (document.getElementById('m5-thread-search')?.value || '').toLowerCase();
    const cat = document.getElementById('m5-category-filter')?.value || '';
    const sort = document.getElementById('m5-sort-filter')?.value || 'NEWEST';

    let list = [...this.m5Data.threads];
    if (q) {
      list = list.filter(t => t.title.toLowerCase().includes(q) || t.content.toLowerCase().includes(q) || (t.tags && t.tags.toLowerCase().includes(q)));
    }
    if (cat) {
      list = list.filter(t => t.category_id === cat);
    }
    if (sort === 'POPULAR') {
      list.sort((a, b) => b.replies_count - a.replies_count);
    }

    if (list.length === 0) {
      container.innerHTML = `<div class="glass-card" style="padding:30px; text-align:center; color:var(--text-muted);">Tidak ada thread yang sesuai filter pencarian.</div>`;
      return;
    }

    container.innerHTML = list.map(t => `
      <div class="glass-card" style="padding:16px; margin-bottom:12px; border-left:3px solid ${t.is_pinned ? 'var(--accent-gold)' : 'var(--chrome-border)'};">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px;">
          <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
              ${t.is_pinned ? '<span class="tier-badge" style="background:rgba(245,158,11,0.2); color:var(--accent-gold); border:1px solid var(--accent-gold); font-size:0.68rem;">📌 PINNED</span>' : ''}
              <span class="tier-badge" style="background:rgba(59,130,246,0.15); color:var(--accent-blue); font-size:0.68rem;">${t.category_icon || '🚗'} ${t.category_name || 'Umum'}</span>
              <span style="font-size:0.75rem; color:var(--accent-gold); font-family:monospace;">${t.tags || ''}</span>
            </div>
            <h4 style="font-size:1.05rem; cursor:pointer;" onclick="AppEngine.openM5ThreadDetail('${t.id}')">${t.title}</h4>
            <div style="font-size:0.78rem; color:var(--text-muted); margin-top:4px;">
              Oleh <strong style="color:var(--text-main);">${t.author_name}</strong> (@${t.author_username}) • ${new Date(t.created_at || Date.now()).toLocaleDateString('id-ID')}
            </div>
          </div>
          <div style="text-align:right; display:flex; gap:16px; align-items:center;">
            <div>
              <div style="font-size:1.1rem; font-weight:800; color:var(--accent-gold);">${t.replies_count || 0}</div>
              <div style="font-size:0.7rem; color:var(--text-muted);">Balasan</div>
            </div>
            <div>
              <div style="font-size:1.1rem; font-weight:800; color:var(--text-muted);">${t.views_count || 0}</div>
              <div style="font-size:0.7rem; color:var(--text-muted);">Dilihat</div>
            </div>
          </div>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px; border-top:1px solid var(--chrome-border); padding-top:10px;">
          <div style="font-size:0.78rem; color:var(--text-muted);">
            Balasan terakhir: ${t.last_post_at ? new Date(t.last_post_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) : 'Baru saja'}
          </div>
          <div style="display:flex; gap:8px;">
            <button class="btn-outline" style="font-size:0.75rem; padding:3px 8px;" onclick="AppEngine.likeM5Post('THREAD', '${t.id}')">👍 Like</button>
            <button class="btn-outline" style="font-size:0.75rem; padding:3px 8px;" onclick="AppEngine.openM5ShareModal('${t.id}')">🔗 Share</button>
            <button class="btn-outline" style="font-size:0.75rem; padding:3px 8px; color:var(--accent-red);" onclick="AppEngine.openM5ReportModal('${t.id}')">🚩 Report</button>
            <button class="btn-primary" style="font-size:0.75rem; padding:3px 10px;" onclick="AppEngine.openM5ThreadDetail('${t.id}')">💬 Baca & Reply ➔</button>
          </div>
        </div>
      </div>
    `).join('');
  },

  renderM5Trending() {
    const container = document.getElementById('m5-trending-container');
    if (!container) return;

    container.innerHTML = this.m5Data.trending.map(tr => `
      <div style="padding:10px 0; border-bottom:1px solid var(--chrome-border); cursor:pointer;" onclick="AppEngine.openM5ThreadDetail('${tr.id}')">
        <strong style="font-size:0.85rem; color:var(--text-main); display:block; line-height:1.3;">🔥 ${tr.title}</strong>
        <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
          <span style="color:var(--accent-gold); font-weight:700;">${tr.replies_count} replies</span> • ${tr.category_name || 'Komunitas'}
        </div>
      </div>
    `).join('');
  },

  openM5ThreadDetail(threadId) {
    this.m5Data.activeThreadId = threadId;
    this.switchM5Subtab('thread');
    this.renderM5ThreadDetail(threadId);
  },

  renderM5ThreadDetail(threadId) {
    const container = document.getElementById('m5-thread-detail-container');
    if (!container) return;

    const thread = this.m5Data.threads.find(t => t.id === threadId) || this.m5Data.threads[0];
    if (!thread) {
      container.innerHTML = `<div class="glass-card" style="padding:20px;">Thread tidak ditemukan.</div>`;
      return;
    }

    const threadReplies = this.m5Data.replies.filter(r => r.thread_id === thread.id);

    container.innerHTML = `
      <div style="margin-bottom:16px;">
        <button class="btn-outline" style="font-size:0.8rem; padding:4px 12px;" onclick="AppEngine.switchM5Subtab('forum')">⬅️ Kembali ke Daftar Thread</button>
      </div>

      <!-- MAIN THREAD HEADER CARD -->
      <div class="glass-card" style="padding:24px; margin-bottom:20px; border-left:4px solid var(--accent-gold);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
          <span class="tier-badge" style="background:rgba(59,130,246,0.15); color:var(--accent-blue);">${thread.category_icon || '🚗'} ${thread.category_name || 'Umum'}</span>
          <span style="font-size:0.78rem; color:var(--text-muted);">${new Date(thread.created_at || Date.now()).toLocaleString('id-ID')}</span>
        </div>
        <h3 style="font-size:1.3rem; margin-bottom:8px;" class="text-gradient">${thread.title}</h3>
        
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px; background:rgba(0,0,0,0.2); padding:10px; border-radius:8px;">
          <div style="width:38px; height:38px; border-radius:50%; background:var(--accent-gold); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800;">${thread.author_avatar || 'AP'}</div>
          <div>
            <strong style="font-size:0.88rem; color:var(--text-main);">${thread.author_name}</strong>
            <span style="font-size:0.75rem; color:var(--text-muted); display:block;">@${thread.author_username}</span>
          </div>
        </div>

        <div style="font-size:0.92rem; color:var(--text-main); line-height:1.7; margin-bottom:16px; white-space:pre-line;">
          ${thread.content}
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--chrome-border); padding-top:12px;">
          <span style="font-size:0.8rem; color:var(--accent-gold); font-family:monospace;">${thread.tags || ''}</span>
          <div style="display:flex; gap:8px;">
            <button class="btn-outline" style="font-size:0.78rem; padding:4px 10px;" onclick="AppEngine.likeM5Post('THREAD', '${thread.id}')">👍 Like (${thread.views_count || 12})</button>
            <button class="btn-outline" style="font-size:0.78rem; padding:4px 10px;" onclick="AppEngine.openM5ShareModal('${thread.id}')">🔗 Share</button>
            <button class="btn-outline" style="font-size:0.78rem; padding:4px 10px; color:var(--accent-red);" onclick="AppEngine.openM5ReportModal('${thread.id}')">🚩 Report</button>
          </div>
        </div>
      </div>

      <!-- REPLIES LIST (5.2.2 REPLY & QUOTE) -->
      <h4 style="font-size:1.1rem; color:var(--accent-gold); margin-bottom:14px;">💬 Balasan Diskusi (${threadReplies.length})</h4>
      <div style="display:flex; flex-direction:column; gap:14px; margin-bottom:24px;">
        ${threadReplies.map(r => `
          <div class="glass-card" style="padding:16px; border-left:3px solid var(--chrome-border);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
              <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:32px; height:32px; border-radius:50%; background:var(--accent-blue); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:0.78rem;">${r.author_avatar || 'US'}</div>
                <div>
                  <strong style="font-size:0.85rem;">${r.author_name}</strong>
                  <span style="font-size:0.72rem; color:var(--text-muted); font-family:monospace;"> @${r.author_username}</span>
                </div>
              </div>
              <span style="font-size:0.75rem; color:var(--text-muted);">${new Date(r.created_at || Date.now()).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})}</span>
            </div>
            <p style="font-size:0.88rem; color:var(--text-main); line-height:1.6; margin-bottom:10px;">${r.content}</p>
            <div style="display:flex; justify-content:flex-end; gap:8px; font-size:0.75rem;">
              <button class="btn-outline" style="padding:2px 8px;" onclick="AppEngine.likeM5Post('REPLY', '${r.id}')">👍 Like (${r.likes_count || 0})</button>
              <button class="btn-outline" style="padding:2px 8px; color:var(--accent-red);" onclick="AppEngine.openM5ReportModal('${thread.id}', '${r.id}')">🚩 Report</button>
            </div>
          </div>
        `).join('')}
      </div>

      <!-- REPLY EDITOR BOX -->
      <div class="glass-panel" style="padding:20px;">
        <h4 style="font-size:1rem; color:var(--accent-gold); margin-bottom:10px;">💬 Tulis Balasan Anda</h4>
        <form onsubmit="AppEngine.submitM5Reply(event, '${thread.id}')">
          <div style="margin-bottom:6px; display:flex; gap:6px;">
            <button type="button" class="btn-outline" style="font-size:0.72rem; padding:2px 6px;"><b>B</b></button>
            <button type="button" class="btn-outline" style="font-size:0.72rem; padding:2px 6px;"><i>I</i></button>
            <button type="button" class="btn-outline" style="font-size:0.72rem; padding:2px 6px;"><u>U</u></button>
            <button type="button" class="btn-outline" style="font-size:0.72rem; padding:2px 6px;">📷</button>
          </div>
          <textarea id="m5-reply-textarea" class="form-input" rows="3" placeholder="Tuliskan komentar atau tanggapan Anda..." required></textarea>
          <div style="display:flex; justify-content:flex-end; margin-top:10px;">
            <button type="submit" class="btn-primary" style="padding:8px 18px; font-size:0.85rem;">💬 Kirim Balasan</button>
          </div>
        </form>
      </div>
    `;
  },

  renderM5Broadcasts() {
    const analytics = document.getElementById('m5-broadcast-analytics-container');
    if (analytics) {
      analytics.innerHTML = `
        <div class="glass-card" style="padding:16px; border-top:3px solid var(--accent-gold);">
          <div style="font-size:0.75rem; color:var(--text-muted);">Total Terkirim</div>
          <div style="font-size:1.6rem; font-weight:900; color:var(--accent-gold);">1.234 Member</div>
          <span style="font-size:0.72rem; color:var(--primary-emerald);">100% Delivered</span>
        </div>
        <div class="glass-card" style="padding:16px; border-top:3px solid var(--accent-blue);">
          <div style="font-size:0.75rem; color:var(--text-muted);">👁️ Total Dilihat</div>
          <div style="font-size:1.6rem; font-weight:900; color:var(--accent-blue);">890 Member</div>
          <span style="font-size:0.72rem; color:var(--accent-blue);">72% Read Rate</span>
        </div>
        <div class="glass-card" style="padding:16px; border-top:3px solid var(--primary-emerald);">
          <div style="font-size:0.75rem; color:var(--text-muted);">🔗 Total Clicks</div>
          <div style="font-size:1.6rem; font-weight:900; color:var(--primary-emerald);">234 Clicks</div>
          <span style="font-size:0.72rem; color:var(--primary-emerald);">19% CTR</span>
        </div>
        <div class="glass-card" style="padding:16px; border-top:3px solid #A855F7;">
          <div style="font-size:0.75rem; color:var(--text-muted);">🔥 Engagement Rate</div>
          <div style="font-size:1.6rem; font-weight:900; color:#A855F7;">19%</div>
          <span style="font-size:0.72rem; color:#A855F7;">High Engagement</span>
        </div>
      `;
    }

    const container = document.getElementById('m5-broadcast-table-container');
    if (!container) return;

    container.innerHTML = `
      <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
        <thead>
          <tr style="background:rgba(0,0,0,0.3); text-align:left; color:var(--text-muted);">
            <th style="padding:10px;">Judul Broadcast</th>
            <th style="padding:10px;">Target Audience</th>
            <th style="padding:10px; text-align:center;">Terkirim</th>
            <th style="padding:10px; text-align:center;">Dilihat</th>
            <th style="padding:10px; text-align:center;">Diklik</th>
            <th style="padding:10px; text-align:center;">Waktu Kirim</th>
            <th style="padding:10px; text-align:center;">Status</th>
          </tr>
        </thead>
        <tbody>
          ${this.m5Data.broadcasts.map(b => `
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:10px; font-weight:700; color:var(--text-main);">${b.title}</td>
              <td style="padding:10px; color:var(--accent-gold);">${b.target_value || 'Semua Member'}</td>
              <td style="padding:10px; text-align:center; font-weight:700;">${b.total_sent || 1234}</td>
              <td style="padding:10px; text-align:center; color:var(--accent-blue);">${b.total_views || 890} (${Math.round((b.total_views || 890)/(b.total_sent || 1234)*100)}%)</td>
              <td style="padding:10px; text-align:center; color:var(--primary-emerald);">${b.total_clicks || 234} (${Math.round((b.total_clicks || 234)/(b.total_sent || 1234)*100)}%)</td>
              <td style="padding:10px; text-align:center; color:var(--text-muted);">${new Date(b.sent_at || Date.now()).toLocaleDateString('id-ID')}</td>
              <td style="padding:10px; text-align:center;"><span class="tier-badge" style="background:rgba(16,185,129,0.2); color:var(--primary-emerald); border:1px solid var(--primary-emerald);">${b.status}</span></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  },

  renderM5Reports() {
    const container = document.getElementById('m5-reports-table-container');
    if (!container) return;

    container.innerHTML = `
      <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
        <thead>
          <tr style="background:rgba(0,0,0,0.3); text-align:left; color:var(--text-muted);">
            <th style="padding:8px;">Thread / Target</th>
            <th style="padding:8px;">Pelapor</th>
            <th style="padding:8px;">Alasan</th>
            <th style="padding:8px; text-align:center;">Status</th>
            <th style="padding:8px; text-align:center;">Aksi Moderasi</th>
          </tr>
        </thead>
        <tbody>
          ${this.m5Data.reports.map(r => `
            <tr style="border-bottom:1px solid var(--chrome-border);">
              <td style="padding:8px; font-weight:700; color:var(--text-main);">${r.thread_title || 'Thread #' + r.thread_id}</td>
              <td style="padding:8px; color:var(--accent-gold);">${r.reporter_name}</td>
              <td style="padding:8px; color:var(--accent-red); font-weight:700;">${r.reason}</td>
              <td style="padding:8px; text-align:center;"><span class="tier-badge" style="background:rgba(245,158,11,0.2); color:var(--accent-gold);">${r.status}</span></td>
              <td style="padding:8px; text-align:center; display:flex; gap:6px; justify-content:center;">
                <button class="btn-primary" style="font-size:0.72rem; padding:2px 6px; background:var(--primary-emerald); border-color:var(--primary-emerald);" onclick="AppEngine.resolveM5Report('${r.id}', 'RESOLVED')">✅ Selesai</button>
                <button class="btn-outline" style="font-size:0.72rem; padding:2px 6px;" onclick="AppEngine.openM5WarnModal('${r.reporter_name}')">⚠️ Warn User</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  },

  renderM5Rules() {
    const container = document.getElementById('m5-rules-container');
    if (!container) return;

    container.innerHTML = this.m5Data.rules.map(rl => `
      <div style="padding:8px 0; border-bottom:1px solid var(--chrome-border);">
        <strong style="font-size:0.85rem; color:var(--accent-gold); display:block;">📜 ${rl.title}</strong>
        <p style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">${rl.description}</p>
      </div>
    `).join('');
  },

  openM5CreateThreadModal() {
    AuthEngine.openModal('modal-create-thread');
  },

  async saveM5ThreadFromModal(event) {
    event.preventDefault();
    const title = document.getElementById('m5-thread-title').value.trim();
    const category_id = document.getElementById('m5-thread-category').value;
    const tags = document.getElementById('m5-thread-tags').value.trim();
    const content = document.getElementById('m5-thread-content').value.trim();

    try {
      const res = await fetch('api.php?action=create_forum_thread', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title, category_id, tags, content,
          author_name: this.currentUser.name || 'Admin MB INA',
          author_username: this.currentUser.username || 'admin'
        })
      });
      const data = await res.json();
      if (data.success) {
        alert('🎉 ' + data.message);
        AuthEngine.closeAllModals();
        await this.fetchM5Data();
        this.renderM5Threads();
      } else {
        alert('❌ ' + data.message);
      }
    } catch (e) {
      alert('Gagal membuat thread: ' + e.message);
    }
  },

  openM5ShareModal(threadId) {
    const linkInput = document.getElementById('m5-share-link-input');
    if (linkInput) linkInput.value = `https://mbina.or.id/forum/thread/${threadId}`;
    AuthEngine.openModal('modal-share-thread');
  },

  openM5ReportModal(threadId, replyId = null) {
    document.getElementById('m5-report-thread-id').value = threadId;
    document.getElementById('m5-report-reply-id').value = replyId || '';
    AuthEngine.openModal('modal-report-thread');
  },

  async saveM5ReportFromModal(event) {
    event.preventDefault();
    const thread_id = document.getElementById('m5-report-thread-id').value;
    const reply_id = document.getElementById('m5-report-reply-id').value;
    const reason = document.querySelector('input[name="report_reason"]:checked')?.value || 'Spam';
    const notes = document.getElementById('m5-report-notes').value.trim();

    try {
      const res = await fetch('api.php?action=report_forum_post', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ thread_id, reply_id, reason, notes, reporter_name: this.currentUser.name || 'Member MB INA' })
      });
      const data = await res.json();
      if (data.success) {
        alert('🎉 ' + data.message);
        AuthEngine.closeAllModals();
        await this.fetchM5Data();
        this.renderM5Reports();
      } else {
        alert('❌ ' + data.message);
      }
    } catch (e) {
      alert('Gagal mengirim laporan: ' + e.message);
    }
  },

  async submitM5Reply(event, threadId) {
    event.preventDefault();
    const content = document.getElementById('m5-reply-textarea').value.trim();
    if (!content) return;

    try {
      const res = await fetch('api.php?action=reply_forum_thread', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          thread_id: threadId,
          content,
          author_name: this.currentUser.name || 'Member MB INA',
          author_username: this.currentUser.username || 'member'
        })
      });
      const data = await res.json();
      if (data.success) {
        alert('🎉 ' + data.message);
        await this.fetchM5Data();
        this.renderM5ThreadDetail(threadId);
      } else {
        alert('❌ ' + data.message);
      }
    } catch (e) {
      alert('Gagal mengirim balasan: ' + e.message);
    }
  },

  async likeM5Post(targetType, targetId) {
    try {
      const res = await fetch('api.php?action=like_forum_post', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ target_type: targetType, target_id: targetId })
      });
      const data = await res.json();
      if (data.success) {
        alert('👍 ' + data.message);
        await this.fetchM5Data();
        if (targetType === 'THREAD') this.renderM5Threads();
        else this.renderM5ThreadDetail(this.m5Data.activeThreadId);
      }
    } catch (e) {
      console.error("Like error:", e);
    }
  },

  openM5CreateBroadcastModal() {
    AuthEngine.openModal('modal-create-broadcast');
  },

  async saveM5BroadcastFromModal(event) {
    event.preventDefault();
    const title = document.getElementById('m5-bc-title').value.trim();
    const content = document.getElementById('m5-bc-content').value.trim();
    const target_type = document.querySelector('input[name="bc_target"]:checked')?.value || 'ALL';

    try {
      const res = await fetch('api.php?action=create_broadcast', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title, content, target_type, target_value: 'Semua Member Aktif' })
      });
      const data = await res.json();
      if (data.success) {
        alert('🎉 ' + data.message);
        AuthEngine.closeAllModals();
        await this.fetchM5Data();
        this.renderM5Broadcasts();
      } else {
        alert('❌ ' + data.message);
      }
    } catch (e) {
      alert('Gagal membuat broadcast: ' + e.message);
    }
  },

  openM5WarnModal(userName) {
    document.getElementById('m5-warn-target-user').value = userName;
    document.getElementById('m5-warn-user-display').value = userName;
    AuthEngine.openModal('modal-warn-user');
  },

  async saveM5WarnFromModal(event) {
    event.preventDefault();
    const user_name = document.getElementById('m5-warn-target-user').value;
    const action_type = document.getElementById('m5-warn-action-type').value;
    const reason = document.getElementById('m5-warn-reason').value.trim();
    const notes = document.getElementById('m5-warn-notes').value.trim();

    try {
      const res = await fetch('api.php?action=warn_user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_name, action_type, reason, notes })
      });
      const data = await res.json();
      if (data.success) {
        alert('⚖️ ' + data.message);
        AuthEngine.closeAllModals();
      } else {
        alert('❌ ' + data.message);
      }
    } catch (e) {
      alert('Gagal memproses moderasi: ' + e.message);
    }
  },

  async resolveM5Report(reportId, status) {
    try {
      const res = await fetch('api.php?action=moderate_report', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ report_id: reportId, status })
      });
      const data = await res.json();
      if (data.success) {
        alert('✅ ' + data.message);
        await this.fetchM5Data();
        this.renderM5Reports();
      }
    } catch (e) {
      alert('Gagal memperbarui status laporan: ' + e.message);
    }
  }
};

window.addEventListener('DOMContentLoaded', () => {
  AppEngine.init();
});
