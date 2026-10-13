// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
// Tema gelap/terang halaman publik. Pilihan tersimpan di localStorage 'tema';
// tanpa pilihan, mengikuti preferensi OS (diterapkan skrip kecil di <head>
// agar halaman tidak berkedip saat dimuat).
(function () {
  function saatIni() {
    return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
  }
  function catIkon(btn) {
    if (!btn) return;
    var gelap = saatIni() === 'dark';
    btn.innerHTML = '<i data-lucide="' + (gelap ? 'sun' : 'moon') + '"></i>';
    btn.setAttribute('title', gelap ? 'Mode terang' : 'Mode gelap');
    if (window.lucide) lucide.createIcons();
  }
  function terapkan(mode) {
    if (mode === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    else document.documentElement.removeAttribute('data-theme');
    try { localStorage.setItem('tema', mode); } catch (e) {}
  }
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('theme-toggle');
    catIkon(btn);
    if (btn) btn.addEventListener('click', function () {
      terapkan(saatIni() === 'dark' ? 'light' : 'dark');
      catIkon(btn);
    });
  });
})();
