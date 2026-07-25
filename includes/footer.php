</main>

<?php if (empty($isFullWidthPage)): ?>
<footer class="border-t border-gray-200 dark:border-gray-800 mt-12 py-6 text-center text-sm text-gray-500 dark:text-gray-400 transition-theme">
  &copy; <?= date('Y') ?> Sistem Pendaftaran Beasiswa KIP Kuliah. Seluruh hak cipta dilindungi.
</footer>
<?php endif; ?>


<script src="<?= BASE_URL ?>/assets/js/dark_mode.js"></script>
<script>
  // Dropdown profil
  const profileBtn = document.getElementById('profileMenuBtn');
  const profileMenu = document.getElementById('profileMenu');
  if (profileBtn && profileMenu) {
    profileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      profileMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', () => profileMenu.classList.add('hidden'));
  }
</script>
</body>
</html>
