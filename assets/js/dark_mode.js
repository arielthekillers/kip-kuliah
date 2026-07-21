/**
 * dark_mode.js
 * Mengatur toggle Dark/Light Mode dan menyimpan preferensi di localStorage.
 */
(function () {
  const root = document.documentElement;
  const toggleBtn = document.getElementById('themeToggle');

  function applyTheme(theme) {
    if (theme === 'dark') {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
  }

  // Terapkan tema tersimpan saat load (jaga-jaga jika belum diterapkan di <head>)
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) applyTheme(savedTheme);

  if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
      const isDark = root.classList.contains('dark');
      const newTheme = isDark ? 'light' : 'dark';
      applyTheme(newTheme);
      localStorage.setItem('theme', newTheme);
    });
  }
})();
