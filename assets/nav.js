// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
// Menu hamburger mobile: buka/tutup daftar nav (.nav-links).
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('nav-toggle');
    var menu = document.querySelector('.nav .nav-links');
    if (!btn || !menu) return;
    function set(open) {
      menu.classList.toggle('open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.setAttribute('title', open ? 'Tutup menu' : 'Buka menu');
    }
    btn.addEventListener('click', function () {
      set(!menu.classList.contains('open'));
    });
    // Tutup saat klik link (sebelum pindah halaman) atau klik area luar.
    menu.addEventListener('click', function (e) {
      if (e.target.closest('a')) set(false);
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.nav-inner')) set(false);
    });
    // Kembali ke tampilan desktop bila layar dilebarkan saat menu terbuka.
    matchMedia('(min-width:761px)').addEventListener('change', function (e) {
      if (e.matches) set(false);
    });
  });
})();
