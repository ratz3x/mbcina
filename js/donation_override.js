/**
 * donation_override.js - v1
 * Override bersih semua fungsi modal donasi. Dimuat SETELAH app.js.
 */
(function() {
  'use strict';

  function getDS() {
    if (window.AppEngine && window.AppEngine.donationData &&
        Array.isArray(window.AppEngine.donationData.donations) &&
        window.AppEngine.donationData.donations.length > 0)
      return window.AppEngine.donationData;
    if (window.M6Engine && window.M6Engine.donationData &&
        Array.isArray(window.M6Engine.donationData.donations))
      return window.M6Engine.donationData;
    return null;
  }

  function forceOpen(id) {
    document.querySelectorAll('.modal-backdrop,.modal-overlay').forEach(function(m) {
      if (m.id !== id) {
        m.classList.remove('active');
        m.style.cssText = 'display:none !important;opacity:0 !important;pointer-events:none !important;visibility:hidden !important;';
      }
    });
    var el = document.getElementById(id);
    if (!el) { console.error('[DON] Not found: #' + id); return false; }
    el.style.cssText = '';
    el.classList.add('active');
    el.style.setProperty('display','flex','important');
    el.style.setProperty('opacity','1','important');
    el.style.setProperty('pointer-events','auto','important');
    el.style.setProperty('visibility','visible','important');
    el.style.setProperty('z-index','99999','important');
    document.body.style.overflow = 'hidden';
    return true;
  }

  function forceClose() {
    document.querySelectorAll('.modal-backdrop,.modal-overlay').forEach(function(m) {
      m.classList.remove('active');
      m.style.cssText = 'display:none !important;opacity:0 !important;pointer-events:none !important;visibility:hidden !important;';
    });
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
  }

  function txt(id, val) {
    var el = document.getElementById(id);
    if (el) el.innerText = val || '-';
  }

  function openVerify(donationId) {
    var ds  = getDS();
    var don = ds && ds.donations
      ? (ds.donations.find(function(d){ return String(d.id)===String(donationId)||String(d.trx_code)===String(donationId); })||{})
      : {};
    if (window.AppEngine) window.AppEngine.activeDonationVerifyId = don.id || donationId;
    if (window.M6Engine)  window.M6Engine.activeDonationVerifyId  = don.id || donationId;

    var camps = (ds && ds.campaigns) || [];
    var camp  = camps.find(function(c){ return c.id === don.campaign_id; }) || {};
    var st    = don.status || 'PENDING';
    var proof = don.payment_proof_url || 'assets/mb_hero.jpg';

    txt('verify-don-code',      don.trx_code || don.id || donationId);
    txt('verify-don-name',      don.donor_name || 'Hamba Allah');
    txt('verify-don-member-id', don.member_id  || 'Non-Member');
    txt('verify-don-amount',    'Rp ' + parseFloat(don.amount||0).toLocaleString('id-ID'));
    txt('verify-don-method',    (don.payment_method||'TRANSFER') + ' • ' + (don.created_at||'Hari Ini'));
    txt('verify-don-campaign',  camp.title || don.campaign_id || 'Donasi MB INA 2026');

    var badge = document.getElementById('verify-don-status-badge');
    if (badge) {
      if (st==='SUCCESS'||st==='CONFIRMED') {
        badge.style.cssText='background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3);font-size:.75rem;padding:4px 12px;border-radius:9999px;font-weight:600;display:inline-block;';
        badge.innerText='TERVERIFIKASI (SUCCESS)';
      } else if (st==='REJECTED') {
        badge.style.cssText='background:rgba(244,63,94,.15);color:#fb7185;border:1px solid rgba(244,63,94,.3);font-size:.75rem;padding:4px 12px;border-radius:9999px;font-weight:600;display:inline-block;';
        badge.innerText='DITOLAK (REJECTED)';
      } else {
        badge.style.cssText='background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.3);font-size:.75rem;padding:4px 12px;border-radius:9999px;font-weight:600;display:inline-block;';
        badge.innerText='MENUNGGU VERIFIKASI (PENDING)';
      }
    }

    var img=document.getElementById('verify-don-proof-img');
    var lnk=document.getElementById('verify-don-proof-link');
    if(img){img.src=proof;img.onerror=function(){this.src='assets/mb_hero.jpg';};}
    if(lnk) lnk.href=proof;

    var acts=document.getElementById('verify-don-actions');
    if(acts){
      if(st==='PENDING'){
        acts.innerHTML='<button type="button" onclick="window.DON.reject()" style="border:1px solid rgba(244,63,94,.4);color:#fb7185;background:transparent;padding:9px 20px;border-radius:12px;font-weight:600;font-size:.8rem;cursor:pointer;margin-right:8px;">✕ Tolak Donasi</button>'
          +'<button type="button" onclick="window.DON.approve()" style="background:#10b981;color:#042f2e;font-weight:700;padding:9px 24px;border-radius:12px;border:none;cursor:pointer;">✓ Setujui &amp; Terbitkan Kwitansi</button>';
      } else if(st==='SUCCESS'||st==='CONFIRMED'){
        acts.innerHTML='<button type="button" onclick="window.DON.closeModal()" style="border:1px solid rgba(255,255,255,.1);color:#94a3b8;background:transparent;padding:9px 20px;border-radius:12px;font-size:.8rem;cursor:pointer;margin-right:8px;">Tutup</button>'
          +'<button type="button" onclick="window.DON.closeModal();setTimeout(function(){window.DON.openReceipt(\'' + donationId + '\')},100)" style="border:1px solid rgba(52,211,153,.3);background:rgba(16,185,129,.1);color:#34d399;padding:9px 22px;border-radius:12px;font-weight:600;font-size:.8rem;cursor:pointer;">🧾 Buka Kwitansi Resmi</button>';
      } else {
        acts.innerHTML='<button type="button" onclick="window.DON.closeModal()" style="border:1px solid rgba(255,255,255,.1);color:#94a3b8;background:transparent;padding:9px 20px;border-radius:12px;font-size:.8rem;cursor:pointer;margin-right:8px;">Tutup</button>'
          +'<span style="background:rgba(244,63,94,.1);color:#fb7185;border:1px solid rgba(244,63,94,.25);font-size:.75rem;padding:8px 16px;border-radius:10px;font-weight:600;">Transaksi Telah Ditolak</span>';
      }
    }
    forceOpen('modal-donation-verify');
  }

  function openReceipt(donationId) {
    var ds = getDS();
    if (!ds) { console.warn('[DON] No data for receipt'); return; }
    var don  = ds.donations.find(function(d){return String(d.id)===String(donationId);})||{};
    var rcpts = ds.receipts || [];
    var rcpt = rcpts.find(function(r){return String(r.donation_id)===String(donationId);});
    if (!rcpt) {
      rcpt = { id:'rec_'+Date.now(), donation_id:donationId,
        receipt_number:'REC-'+new Date().getFullYear()+'-'+Math.floor(1000+Math.random()*9000),
        created_at:new Date().toLocaleDateString('id-ID') };
      ds.receipts = rcpts;
      ds.receipts.unshift(rcpt);
      if (window.AppEngine) window.AppEngine.donationData = ds;
    }
    var camp = (ds.campaigns||[]).find(function(c){return c.id===don.campaign_id;})||{};
    if (window.AppEngine) window.AppEngine.activeReceiptId = rcpt.id;

    txt('rcpt-num',        rcpt.receipt_number);
    txt('rcpt-date',       rcpt.created_at);
    txt('rcpt-donor-name', don.donor_name || 'Hamba Allah');
    txt('rcpt-member-id',  don.member_id  || 'Non-Member');
    txt('rcpt-camp-title', camp.title || 'Donasi Bakti Sosial MB INA 2026');
    txt('rcpt-method',     don.payment_method || 'TRANSFER');
    txt('rcpt-amount',     'Rp '+parseFloat(don.amount||0).toLocaleString('id-ID'));

    forceOpen('modal-digital-receipt');
  }

  window.DON = {
    closeModal: forceClose,
    approve: function() {
      forceClose();
      if (window.AppEngine && typeof window.AppEngine.confirmVerifyDonation==='function')
        window.AppEngine.confirmVerifyDonation(true);
    },
    reject: function() {
      forceClose();
      if (window.AppEngine && typeof window.AppEngine.confirmVerifyDonation==='function')
        window.AppEngine.confirmVerifyDonation(false);
    },
    openReceipt: openReceipt
  };

  function applyOverrides() {
    var engines = [window.AppEngine, window.M6Engine].filter(Boolean);
    engines.forEach(function(eng) {
      eng.openDonationVerifyModal = openVerify;
      eng.openDigitalReceiptModalByDonationId = openReceipt;
      eng.openDigitalReceiptModal = function(rcptId) {
        var ds = getDS();
        var rcpt = ds && ds.receipts ? (ds.receipts.find(function(r){return r.id===rcptId;})||{}) : {};
        openReceipt(rcpt.donation_id || rcptId);
      };
    });
    window.openDonationVerifyModal = openVerify;

    // Override AuthEngine close functions
    if (window.AuthEngine) {
      window.AuthEngine.closeAllModals = forceClose;
      window.AuthEngine.closeModal = forceClose;
      window.AuthEngine.openModal = function(modalId) { forceOpen(modalId); };
    }

    // Patch backdrop click handlers
    ['modal-donation-verify','modal-digital-receipt'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) {
        el.onclick = function(e) { if (e.target===el) forceClose(); };
        var btns = el.querySelectorAll('[onclick*="closeModal"],[onclick*="closeAllModals"],.modal-close-btn');
        btns.forEach(function(b) { b.onclick = function(e) { e.stopPropagation(); forceClose(); }; });
      }
    });

    console.log('[donation_override] ✅ Applied');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ setTimeout(applyOverrides, 300); });
  } else {
    setTimeout(applyOverrides, 300);
  }
})();
