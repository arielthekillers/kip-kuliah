<?php
require_once __DIR__ . '/../config.php';

if (isLoggedIn()) redirect('dashboard');

$pageTitle = 'Registrasi Akun - KIP Kuliah';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto mt-10 animate-fade-in-up">
  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-tr from-green-500 to-primary-500 text-white mb-4 shadow-lg shadow-green-500/30">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
    </div>
    <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-green-600 dark:from-primary-400 dark:to-green-400">Buat Akun Baru</h1>
    <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">Daftar untuk memulai pendaftaran Beasiswa KIP Kuliah</p>
  </div>

  <div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 p-6 sm:p-10 transition-theme">
    
    <div id="alertBox" class="hidden mb-5 rounded-lg px-4 py-3 text-sm flex gap-3 items-start border">
      <span id="alertMessage"></span>
    </div>

    <form id="registerForm" method="POST" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
      <div>
        <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" required autofocus
               class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Email Aktif</label>
        <input type="email" name="email" required
               class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">No. WhatsApp Aktif</label>
        <input type="tel" name="no_wa" placeholder="08xxxxxxxxxx" pattern="08[0-9]{7,12}" title="Gunakan format awalan 08 (minimal 9 digit)"
               class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Password</label>
        <div class="relative">
          <input type="password" name="password" id="inputPassword" required minlength="8"
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
          <button type="button" tabindex="-1" onclick="togglePassword('inputPassword', 'iconPassword')" class="absolute right-4 top-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <svg id="iconPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          </button>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Konfirmasi Password</label>
        <div class="relative">
          <input type="password" name="password_confirm" id="inputPasswordConfirm" required minlength="8"
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition">
          <button type="button" tabindex="-1" onclick="togglePassword('inputPasswordConfirm', 'iconPasswordConfirm')" class="absolute right-4 top-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <svg id="iconPasswordConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" id="btnSubmit" class="w-full bg-gradient-to-r from-primary-600 to-green-600 hover:from-primary-500 hover:to-green-500 text-white font-semibold py-3 rounded-xl transform hover:-translate-y-1 transition-all shadow-lg hover:shadow-primary-500/30">
        Daftar Sekarang
      </button>
    </form>

    <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
      Sudah punya akun? <a href="<?= BASE_URL ?>/auth/login" class="text-primary-600 dark:text-primary-400 font-medium hover:underline">Masuk di sini</a>
    </p>
  </div>
</div>

<script>
function togglePassword(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  if (input.type === 'password') {
    input.type = 'text';
    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
  } else {
    input.type = 'password';
    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
  }
}

// AJAX Register Submission
document.getElementById('registerForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const form = this;
  const btnSubmit = document.getElementById('btnSubmit');
  const alertBox = document.getElementById('alertBox');
  const alertMessage = document.getElementById('alertMessage');
  
  // Client-side validation for password match
  const password = form.querySelector('[name="password"]').value;
  const confirmPassword = form.querySelector('[name="password_confirm"]').value;
  if (password !== confirmPassword) {
      alertBox.className = 'mb-5 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg px-4 py-3 text-sm';
      alertMessage.innerHTML = 'Konfirmasi password tidak cocok.';
      alertBox.classList.remove('hidden');
      return;
  }
  
  btnSubmit.disabled = true;
  btnSubmit.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...`;
  
  alertBox.classList.add('hidden');
  
  const formData = new FormData(form);
  
  fetch('<?= BASE_URL ?>/ajax/auth_register.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alertBox.className = 'mb-5 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-lg px-4 py-3 text-sm';
      alertMessage.innerHTML = data.message;
      alertBox.classList.remove('hidden');
      window.location.href = data.redirect;
    } else {
      alertBox.className = 'mb-5 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg px-4 py-3 text-sm';
      alertMessage.innerHTML = data.message;
      alertBox.classList.remove('hidden');
      btnSubmit.disabled = false;
      btnSubmit.innerHTML = 'Daftar Sekarang';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alertBox.className = 'mb-5 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg px-4 py-3 text-sm';
    alertMessage.innerHTML = 'Terjadi kesalahan sistem.';
    alertBox.classList.remove('hidden');
    btnSubmit.disabled = false;
    btnSubmit.innerHTML = 'Daftar Sekarang';
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
