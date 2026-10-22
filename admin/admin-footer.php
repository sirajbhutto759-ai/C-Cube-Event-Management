  </main>
</div>

<button class="mobile-sidebar-toggle" id="mobileSidebarBtn"><i class="fa fa-bars"></i></button>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script src="<?= BASE_URL ?>assets/js/dashboard.js"></script>
<script>
const sidebar=document.getElementById('sidebar'),overlay=document.getElementById('sidebarOverlay'),btn=document.getElementById('mobileSidebarBtn');
btn?.addEventListener('click',()=>{sidebar?.classList.toggle('open');overlay?.classList.toggle('open');});
overlay?.addEventListener('click',()=>{sidebar?.classList.remove('open');overlay?.classList.remove('open');});

// Global modal helpers
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}
document.querySelectorAll('.modal-backdrop').forEach(mb => {
  mb.addEventListener('click', e => {
    if (e.target === mb) mb.classList.remove('open');
  });
});
</script>
</body>
</html>
