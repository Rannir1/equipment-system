/* ═══════════════════════════════════════════════════════════
   Equipment System — Main JS
   ════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {

  // ── Sidebar toggle (mobile) ───────────────────────────────
  const sidebar    = document.getElementById('sidebar');
  const overlay    = document.getElementById('overlay');
  const menuToggle = document.getElementById('menuToggle');
  const closeBtn   = document.getElementById('sidebarClose');

  function openSidebar()  { sidebar?.classList.add('open'); overlay?.classList.add('open'); }
  function closeSidebar() { sidebar?.classList.remove('open'); overlay?.classList.remove('open'); }

  menuToggle?.addEventListener('click', openSidebar);
  closeBtn?.addEventListener('click',   closeSidebar);
  overlay?.addEventListener('click',    closeSidebar);

  // ── Auto-dismiss flash messages ──────────────────────────
  const flash = document.getElementById('flashMsg');
  if (flash) setTimeout(() => flash.remove(), 5000);

  // ── Confirm forms ────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

});

// ── Modal helpers (global) ────────────────────────────────
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
}
function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
}

// Close modal on backdrop click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal')) {
    e.target.classList.remove('open');
  }
});

// Close modal on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal.open').forEach(m => m.classList.remove('open'));
  }
});
