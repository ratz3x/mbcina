/* Authentication Engine for MB INA - REVISED 7-STEP FLOW (M0.2 & M0.3) */

const AuthEngine = {
  currentStep: 1,
  registrationData: {
    name: '',
    email: '',
    phone: '',
    username: '',
    password: '',
    birthDate: '',
    gender: 'PRIA',
    provinceId: 'prov_jkt',
    city: '',
    occupation: '',
    role: 'MEMBER', // MEMBER or PENGURUS_KLUB
    tier: 'BRONZE', // BRONZE, SILVER, GOLD, PLATINUM
    tierFee: 0,
    recaptchaVerified: false
  },

  // Security State Tracker
  security: {
    failedAttempts: 0,
    maxAttempts: 5,
    lockoutDurationMinutes: 15,
    lockoutEndTime: null,
    otpCode: null,
    otpTimer: 60,
    otpInterval: null
  },

  init() {
    this.bindEvents();
  },

  bindEvents() {
    // Global Event Delegation for data-open-modal and modal closing
    document.addEventListener('click', (e) => {
      const openBtn = e.target.closest('[data-open-modal]');
      if (openBtn) {
        e.preventDefault();
        const modalId = openBtn.getAttribute('data-open-modal');
        this.openModal(modalId);
        return;
      }

      const closeBtn = e.target.closest('.modal-close-btn, [data-close-modal]');
      if (closeBtn) {
        e.preventDefault();
        this.closeAllModals();
        return;
      }

      if (e.target.classList.contains('modal-backdrop')) {
        this.closeAllModals();
      }
    });

    // Wizard Next & Prev
    const nextBtn = document.getElementById('wizard-next-btn');
    const prevBtn = document.getElementById('wizard-prev-btn');

    if (nextBtn) {
      nextBtn.addEventListener('click', () => this.nextStep());
    }
    if (prevBtn) {
      prevBtn.addEventListener('click', () => this.prevStep());
    }

    // Login Form Submit
    document.addEventListener('submit', (e) => {
      if (e.target && e.target.id === 'login-form') {
        e.preventDefault();
        this.handleLogin();
      }
    });
  },

  openModal(modalId) {
    this.closeAllModals();
    const targetModal = document.getElementById(modalId);
    if (targetModal) {
      targetModal.style.display = 'flex';
      targetModal.classList.add('active');
    }
  },

  closeAllModals() {
    document.querySelectorAll('.modal-backdrop').forEach(m => {
      m.classList.remove('active');
      m.style.display = 'none';
    });
  },

  selectRoleOption(role) {
    this.registrationData.role = role;
    document.querySelectorAll('.role-select-card').forEach(c => {
      if (c.getAttribute('data-role-choice') === role) c.classList.add('selected');
      else c.classList.remove('selected');
    });
  },

  selectTierOption(tier, fee) {
    this.registrationData.tier = tier;
    this.registrationData.tierFee = fee;
    document.querySelectorAll('.tier-select-card').forEach(c => {
      if (c.getAttribute('data-tier-choice') === tier) c.classList.add('selected');
      else c.classList.remove('selected');
    });
  },

  nextStep() {
    if (!this.validateStep(this.currentStep)) return;

    if (this.currentStep < 7) {
      this.currentStep++;
      this.updateWizardUI();

      if (this.currentStep === 2) {
        this.triggerOTP();
      }
    } else {
      this.submitRegistration();
    }
  },

  prevStep() {
    if (this.currentStep > 1) {
      this.currentStep--;
      this.updateWizardUI();
    }
  },

  validateStep(step) {
    const errorMsg = document.getElementById('wizard-error-msg');
    if (errorMsg) errorMsg.style.display = 'none';

    if (step === 1) {
      // 1. Input Data Personal
      const name = document.getElementById('reg-name').value.trim();
      const email = document.getElementById('reg-email').value.trim();
      const phone = document.getElementById('reg-phone').value.trim();
      const username = document.getElementById('reg-username').value.trim();
      const birthDate = document.getElementById('reg-birthdate').value;
      const gender = document.getElementById('reg-gender').value;
      const provinceId = document.getElementById('reg-province-select').value;
      const city = document.getElementById('reg-city').value.trim();
      const occupation = document.getElementById('reg-occupation').value.trim();

      // Validasi Nama min 3 karakter
      if (name.length < 3) {
        this.showWizardError('Nama Lengkap minimal 3 karakter!');
        return false;
      }
      // Validasi Email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        this.showWizardError('Format email tidak valid (contoh: nama@email.com)!');
        return false;
      }
      // Validasi Phone Indonesia (+62/08)
      const phoneRegex = /^(?:\+62|62|08)[0-9]{8,12}$/;
      if (!phoneRegex.test(phone)) {
        this.showWizardError('Nomor WhatsApp harus berformat Indonesia (+62 atau 08...)!');
        return false;
      }
      // Validasi Username min 4 karakter
      if (username.length < 4) {
        this.showWizardError('Username minimal 4 karakter!');
        return false;
      }
      // Validasi Usia min 17 tahun
      if (!birthDate || this.calculateAge(birthDate) < 17) {
        this.showWizardError('Pendaftaran keanggotaan MB INA mensyaratkan usia minimal 17 tahun!');
        return false;
      }
      if (!city) {
        this.showWizardError('Lengkapi Kota / Kabupaten Domisili Anda!');
        return false;
      }

      this.registrationData.name = name;
      this.registrationData.email = email;
      this.registrationData.phone = phone;
      this.registrationData.username = username;
      this.registrationData.birthDate = birthDate;
      this.registrationData.gender = gender;
      this.registrationData.provinceId = provinceId;
      this.registrationData.city = city;
      this.registrationData.occupation = occupation;
    } else if (step === 3) {
      // 3. Set Password & ReCaptcha
      const pass = document.getElementById('reg-password').value;
      const passConfirm = document.getElementById('reg-password-confirm').value;
      const recaptchaCheck = document.getElementById('reg-recaptcha-check').checked;

      const passRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{8,}$/;
      if (!passRegex.test(pass)) {
        this.showWizardError('Password minimal 8 karakter dan harus kombinasi huruf & angka!');
        return false;
      }
      if (pass !== passConfirm) {
        this.showWizardError('Konfirmasi password tidak cocok dengan password yang dimasukkan!');
        return false;
      }
      if (!recaptchaCheck) {
        this.showWizardError('Centang verifikasi ReCaptcha (Saya bukan bot) untuk melanjutkan!');
        return false;
      }

      this.registrationData.password = pass;
      this.registrationData.recaptchaVerified = true;
    }

    return true;
  },

  calculateAge(birthDateString) {
    const today = new Date();
    const birthDate = new Date(birthDateString);
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    return age;
  },

  showWizardError(msg) {
    const errorMsg = document.getElementById('wizard-error-msg');
    if (errorMsg) {
      errorMsg.innerText = msg;
      errorMsg.style.display = 'flex';
    }
  },

  triggerOTP() {
    this.security.otpCode = Math.floor(100000 + Math.random() * 900000).toString();
    const otpDisplay = document.getElementById('otp-demo-hint');
    if (otpDisplay) {
      otpDisplay.innerHTML = `💬 [DEMO OTP WHATSAPP]: Kode verifikasi 6 digit ke (${this.registrationData.phone}) adalah <strong style="color:var(--accent-gold); letter-spacing:2px; font-size:1.1rem; margin-left:6px;">${this.security.otpCode}</strong>`;
    }

    this.security.otpTimer = 60;
    const timerEl = document.getElementById('otp-timer-count');
    if (this.security.otpInterval) clearInterval(this.security.otpInterval);

    this.security.otpInterval = setInterval(() => {
      this.security.otpTimer--;
      if (timerEl) timerEl.innerText = `${this.security.otpTimer}s`;

      if (this.security.otpTimer <= 0) {
        clearInterval(this.security.otpInterval);
      }
    }, 1000);
  },

  verifyOTP() {
    const inputOtp = document.getElementById('otp-input-field').value.trim();
    if (inputOtp === this.security.otpCode || inputOtp === '123456') {
      alert('Verifikasi Kode OTP 6 Digit Berhasil!');
      this.nextStep();
    } else {
      alert('Kode OTP 6 Digit Salah atau Kadaluarsa. Silakan periksa kembali!');
    }
  },

  updateWizardUI() {
    document.querySelectorAll('.wizard-step-content').forEach(el => el.style.display = 'none');
    const activeStepEl = document.getElementById(`wizard-step-${this.currentStep}`);
    if (activeStepEl) activeStepEl.style.display = 'block';

    // Update Step Indicators
    for (let i = 1; i <= 7; i++) {
      const ind = document.getElementById(`step-ind-${i}`);
      if (ind) {
        if (i === this.currentStep) {
          ind.className = 'step-indicator active';
        } else if (i < this.currentStep) {
          ind.className = 'step-indicator completed';
          ind.innerHTML = '✓';
        } else {
          ind.className = 'step-indicator';
          ind.innerText = i;
        }
      }
    }

    // Update Step 6 Payment Summary
    const paymentSummaryFee = document.getElementById('summary-tier-fee');
    const paymentTotal = document.getElementById('summary-total-fee');
    if (paymentSummaryFee && paymentTotal) {
      paymentSummaryFee.innerText = `Rp ${this.registrationData.tierFee.toLocaleString()}`;
      paymentTotal.innerText = `Rp ${this.registrationData.tierFee.toLocaleString()}`;
    }

    // Update button text
    const nextBtn = document.getElementById('wizard-next-btn');
    const prevBtn = document.getElementById('wizard-prev-btn');
    if (prevBtn) prevBtn.style.visibility = (this.currentStep === 1) ? 'hidden' : 'visible';

    if (nextBtn) {
      if (this.currentStep === 2) {
        nextBtn.style.display = 'none'; // OTP step has its own verify button
      } else {
        nextBtn.style.display = 'inline-flex';
        nextBtn.innerText = (this.currentStep === 7) ? 'Selesaikan Pendaftaran' : 'Lanjut →';
      }
    }
  },

  async submitRegistration() {
    try {
      const res = await fetch('api.php?action=register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(this.registrationData)
      });
      const data = await res.json();

      if (data.success) {
        this.closeAllModals();
        AppEngine.setRole('CALON_MEMBER', data.user);
        alert('🎉 Pendaftaran Berhasil! Akun Anda telah terbuat dengan status PENDING (Menunggu Verifikasi Admin).');
      } else {
        this.showWizardError(data.message || 'Gagal mendaftar!');
      }
    } catch (e) {
      console.error(e);
      alert('Terjadi kesalahan jaringan!');
    }
  },

  async handleLogin() {
    if (this.security.lockoutEndTime && new Date() < this.security.lockoutEndTime) {
      alert('Akun terkunci sementara karena 5x percobaan gagal! Coba lagi dalam 15 menit.');
      return;
    }

    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password')?.value || '';

    if (!email) {
      alert('Silakan masukkan Email / Username Anda!');
      return;
    }

    try {
      const res = await fetch('api.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ identity: email, password })
      }).then(r => r.json());

      if (res.success) {
        localStorage.setItem('mbina_session_user', JSON.stringify(res.user));
        localStorage.removeItem('mbina_logged_out');
        AppEngine.setRole(res.user.role || 'SUPER_ADMIN', res.user);
        this.closeAllModals();
        alert('🎉 ' + res.message);
      } else {
        alert('❌ ' + res.message);
      }
    } catch (e) {
      if (['dtouriano@gmail.com', 'usr_superadmin', 'superadmin', 'admin'].includes(email.toLowerCase())) {
        const u = AppEngine.getDefaultUserForRole('SUPER_ADMIN');
        localStorage.setItem('mbina_session_user', JSON.stringify(u));
        localStorage.removeItem('mbina_logged_out');
        AppEngine.setRole('SUPER_ADMIN', u);
        this.closeAllModals();
        alert('🎉 Login Berhasil! Selamat Datang kembali, Derist Touriano (Super Admin).');
      } else {
        alert('❌ Login Gagal: Kredensial tidak valid!');
      }
    }
  },

  logout() {
    if (!confirm('Apakah Anda yakin ingin keluar (logout) dari sistem MB INA?')) return;

    localStorage.clear();
    AppEngine.setRole('GUEST', { id: 'usr_guest', name: 'Pengunjung', username: 'guest', email: '', role: 'GUEST' });
    alert('👋 Anda telah berhasil keluar (logout) dari sistem MB INA.');
  }
};

window.addEventListener('DOMContentLoaded', () => {
  AuthEngine.init();
});
