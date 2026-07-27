</main>

<?php if (empty($isFullWidthPage)): ?>
<footer class="border-t border-gray-200 dark:border-gray-800 mt-12 py-6 text-center text-sm text-gray-500 dark:text-gray-400 transition-theme">
  <p>&copy; <?= date('Y') ?> KIP Kuliah oleh <a href="https://abdulwachid.com" target="_blank" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">Abdul Wachid</a>.</p>
  <p class="mt-1 text-xs">Dikembangkan oleh <a href="https://sintesacorp.id" target="_blank" class="font-medium text-purple-600 dark:text-purple-400 hover:underline">Sintesa Corp</a>.</p>
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
