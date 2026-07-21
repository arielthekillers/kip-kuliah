  </div>
</main>

<script>
  // Mobile Sidebar Toggle
  const sidebar = document.getElementById('sidebar');
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  if (mobileMenuBtn && sidebar) {
    mobileMenuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('-translate-x-full');
    });
  }

  // Dark Mode Toggle for Sidebar
  const themeToggleSidebar = document.getElementById('themeToggleSidebar');
  if (themeToggleSidebar) {
    themeToggleSidebar.addEventListener('click', () => {
      document.documentElement.classList.toggle('dark');
      if (document.documentElement.classList.contains('dark')) {
        localStorage.setItem('theme', 'dark');
      } else {
        localStorage.setItem('theme', 'light');
      }
    });
  }
</script>
</body>
</html>
