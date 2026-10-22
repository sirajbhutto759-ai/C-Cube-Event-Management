/* ============================================================
   C Cube – Dashboard JavaScript (Participant & Admin Panels)
   ============================================================ */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  // ─── Sidebar Mobile Drawer ─────────────────────────────────
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const toggleBtn = document.getElementById('mobileSidebarBtn');

  if (toggleBtn && sidebar && overlay) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay.classList.toggle('open');
    });

    overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('open');
    });
  }

  // ─── Copy Reference Number Helper ──────────────────────────
  document.querySelectorAll('[data-copy]').forEach(el => {
    el.style.cursor = 'pointer';
    el.title = 'Click to copy';
    el.addEventListener('click', () => {
      const text = el.getAttribute('data-copy') || el.textContent.trim();
      navigator.clipboard.writeText(text).then(() => {
        if (typeof showToast === 'function') {
          showToast(`Copied "${text}" to clipboard!`, 'success', 2500);
        } else {
          alert(`Copied "${text}" to clipboard!`);
        }
      });
    });
  });

  // ─── Close Modals on Escape Key ────────────────────────────
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
    }
  });

  // ─── Image Preview on File Input ───────────────────────────
  document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
    input.addEventListener('change', function () {
      if (this.files && this.files[0]) {
        const file = this.files[0];
        if (file.size > 5 * 1024 * 1024) {
          alert('File size exceeds 5MB limit. Please choose a smaller image.');
          this.value = '';
          return;
        }
      }
    });
  });
});
