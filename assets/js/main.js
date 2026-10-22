/* ============================================================
   C Cube – Main JavaScript (vanilla, no dependencies)
   ============================================================ */
'use strict';

// ─── Navbar scroll effect ────────────────────────────────────
const nav = document.getElementById('mainNav');
if (nav) {
  const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 20);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

// ─── Mobile nav toggle ───────────────────────────────────────
const navToggle = document.getElementById('navToggle');
const navLinks  = document.getElementById('navLinks');
if (navToggle && navLinks) {
  navToggle.addEventListener('click', () => {
    navToggle.classList.toggle('active');
    navLinks.classList.toggle('open');
  });
  // Close on link click
  navLinks.querySelectorAll('a').forEach(a =>
    a.addEventListener('click', () => {
      navToggle.classList.remove('active');
      navLinks.classList.remove('open');
    })
  );
  // Close on outside click
  document.addEventListener('click', e => {
    if (!nav.contains(e.target)) {
      navToggle.classList.remove('active');
      navLinks.classList.remove('open');
    }
  });
}

// ─── User dropdown ───────────────────────────────────────────
const userMenuBtn  = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
if (userMenuBtn && userDropdown) {
  userMenuBtn.addEventListener('click', e => {
    e.stopPropagation();
    userDropdown.classList.toggle('open');
  });
  document.addEventListener('click', () => userDropdown.classList.remove('open'));
}

// ─── Scroll Reveal ───────────────────────────────────────────
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('revealed');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('[data-reveal], [data-reveal-stagger]').forEach(el =>
  revealObserver.observe(el)
);

// ─── Animated Counters ───────────────────────────────────────
function animateCounter(el) {
  const target = parseInt(el.getAttribute('data-target') || el.textContent, 10);
  if (isNaN(target)) return;
  const duration = 1600;
  const start    = performance.now();
  const from     = 0;
  const easeOut  = t => 1 - Math.pow(1 - t, 3);

  const tick = (now) => {
    const elapsed  = now - start;
    const progress = Math.min(elapsed / duration, 1);
    el.textContent = Math.round(from + (target - from) * easeOut(progress));
    if (progress < 1) requestAnimationFrame(tick);
    else el.textContent = target.toLocaleString();
  };
  requestAnimationFrame(tick);
}

const counterObserver = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      animateCounter(e.target);
      counterObserver.unobserve(e.target);
    }
  });
}, { threshold: 0.5 });

document.querySelectorAll('[data-counter]').forEach(el => counterObserver.observe(el));

// ─── Toast notifications (JS-triggered) ──────────────────────
function showToast(message, type = 'info', duration = 4500) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = { success:'fa-check-circle', error:'fa-times-circle', info:'fa-info-circle', warning:'fa-exclamation-triangle' };
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `<i class="fa ${icons[type]||icons.info}"></i><span>${message}</span>
    <button class="toast-close" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>`;
  container.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add('show'));
  setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 350); }, duration);
}

// Auto-dismiss flash toasts
setTimeout(() => {
  document.querySelectorAll('#flashToast').forEach(t => {
    t.classList.remove('show');
    setTimeout(() => t.remove(), 350);
  });
}, 4000);

// ─── Password visibility toggle ──────────────────────────────
document.querySelectorAll('.input-group-icon[data-toggle-pw]').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.closest('.input-group').querySelector('input');
    if (!input) return;
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.querySelector('i').className = `fa ${isText ? 'fa-eye' : 'fa-eye-slash'}`;
  });
});

// ─── Confirmation dialogs ────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    const msg = el.getAttribute('data-confirm') || 'Are you sure?';
    if (!confirm(msg)) e.preventDefault();
  });
});

// ─── Category filter on events page ──────────────────────────
document.querySelectorAll('.cat-pill[data-filter]').forEach(pill => {
  pill.addEventListener('click', () => {
    const filter = pill.getAttribute('data-filter');
    document.querySelectorAll('.cat-pill[data-filter]').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');

    document.querySelectorAll('.event-card-wrap').forEach(card => {
      const cat = card.getAttribute('data-category') || '';
      card.style.display = (!filter || filter === 'all' || cat === filter) ? '' : 'none';
    });
  });
});

// ─── Search filter ────────────────────────────────────────────
const liveSearch = document.getElementById('eventSearch');
if (liveSearch) {
  liveSearch.addEventListener('input', () => {
    const q = liveSearch.value.toLowerCase().trim();
    document.querySelectorAll('.event-card-wrap').forEach(card => {
      const title = card.querySelector('.event-card-title')?.textContent.toLowerCase() || '';
      const desc  = card.querySelector('.event-card-desc')?.textContent.toLowerCase()  || '';
      card.style.display = (!q || title.includes(q) || desc.includes(q)) ? '' : 'none';
    });
  });
}

// ─── Smooth scroll for anchor links ──────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) {
      e.preventDefault();
      const offset = 80;
      const top = target.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({ top, behavior: 'smooth' });
    }
  });
});

// ─── Button ripple effect ─────────────────────────────────────
document.querySelectorAll('.btn').forEach(btn => {
  btn.addEventListener('click', e => {
    const r = document.createElement('span');
    r.className = 'ripple';
    const rect = btn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    r.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px;`;
    btn.appendChild(r);
    setTimeout(() => r.remove(), 700);
  });
});

// ─── Form client-side validation ─────────────────────────────
function validateForm(form) {
  let valid = true;
  form.querySelectorAll('[required]').forEach(field => {
    const err = field.closest('.form-group')?.querySelector('.form-error');
    if (!field.value.trim()) {
      field.classList.add('is-invalid');
      if (err) { err.innerHTML = '<i class="fa fa-exclamation-circle"></i> This field is required.'; err.style.display='flex'; }
      valid = false;
    } else {
      field.classList.remove('is-invalid');
      if (err) err.style.display='none';
    }
    if (field.type === 'email' && field.value.trim()) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!re.test(field.value)) {
        field.classList.add('is-invalid');
        if (err) { err.innerHTML = '<i class="fa fa-exclamation-circle"></i> Enter a valid email address.'; err.style.display='flex'; }
        valid = false;
      }
    }
  });
  // Password match
  const pw  = form.querySelector('[name="password"]');
  const cpw = form.querySelector('[name="confirm_password"]');
  if (pw && cpw && pw.value !== cpw.value) {
    cpw.classList.add('is-invalid');
    const err = cpw.closest('.form-group')?.querySelector('.form-error');
    if (err) { err.innerHTML = '<i class="fa fa-exclamation-circle"></i> Passwords do not match.'; err.style.display='flex'; }
    valid = false;
  }
  return valid;
}

document.querySelectorAll('form[data-validate]').forEach(form => {
  form.addEventListener('submit', e => {
    if (!validateForm(form)) e.preventDefault();
  });
});

// Live field validation
document.querySelectorAll('form[data-validate] [required]').forEach(field => {
  field.addEventListener('blur', () => {
    const err = field.closest('.form-group')?.querySelector('.form-error');
    if (!field.value.trim()) {
      field.classList.add('is-invalid');
      if (err) { err.innerHTML = '<i class="fa fa-exclamation-circle"></i> This field is required.'; err.style.display='flex'; }
    } else {
      field.classList.remove('is-invalid');
      if (err) err.style.display = 'none';
    }
  });
});

// ─── Loading state on form submit ────────────────────────────
document.querySelectorAll('form[data-loading]').forEach(form => {
  form.addEventListener('submit', () => {
    const btn = form.querySelector('[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner"></span> Processing…';
    }
  });
});

// ─── Team members dynamic form ────────────────────────────────
const teamSection   = document.getElementById('teamSection');
const addMemberBtn  = document.getElementById('addMemberBtn');
const membersList   = document.getElementById('membersList');
let memberCount     = 1;

if (addMemberBtn && membersList) {
  addMemberBtn.addEventListener('click', () => {
    memberCount++;
    const div = document.createElement('div');
    div.className = 'card mb-2';
    div.style.padding = '1rem';
    div.innerHTML = `
      <div class="d-flex justify-between align-center mb-2">
        <strong class="text-sm">Member ${memberCount}</strong>
        <button type="button" class="btn-icon btn-icon-danger remove-member"><i class="fa fa-trash"></i></button>
      </div>
      <div class="form-row">
        <div class="form-group mb-0">
          <label class="form-label">Name <span class="required">*</span></label>
          <input type="text" name="member_name[]" class="form-control" required>
        </div>
        <div class="form-group mb-0">
          <label class="form-label">Email</label>
          <input type="email" name="member_email[]" class="form-control">
        </div>
      </div>
      <div class="form-group mt-1 mb-0">
        <label class="form-label">Institution</label>
        <input type="text" name="member_institution[]" class="form-control">
      </div>`;
    membersList.appendChild(div);
    div.querySelector('.remove-member').addEventListener('click', () => div.remove());
  });
}

// ─── Active nav link highlight ────────────────────────────────
const currentPath = window.location.pathname;
document.querySelectorAll('.nav-link, .sidebar-nav a').forEach(a => {
  if (a.getAttribute('href') && currentPath.endsWith(a.getAttribute('href').split('/').pop())) {
    a.classList.add('active');
  }
});

// ─── Modal helpers ────────────────────────────────────────────
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}
document.querySelectorAll('[data-modal-open]').forEach(btn =>
  btn.addEventListener('click', () => openModal(btn.getAttribute('data-modal-open')))
);
document.querySelectorAll('[data-modal-close]').forEach(btn =>
  btn.addEventListener('click', () => closeModal(btn.getAttribute('data-modal-close')))
);
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
});

// Expose helpers globally
window.CCube = { showToast, openModal, closeModal };
