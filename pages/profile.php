<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$db = getDB();
$userId = currentUserId();
$errors = [];
$successMsg = null;

$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi telah kedaluwarsa, silakan muat ulang halaman.';
    }

    // -------------------------------------------------------
    // Update Profil Dasar (+ Avatar)
    // -------------------------------------------------------
    if ($formType === 'update_profile' && empty($errors)) {
        $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
        $noWa = trim($_POST['no_wa'] ?? '');

        if ($namaLengkap === '') $errors[] = 'Nama lengkap wajib diisi.';

        $avatarFileName = $user['avatar'];

        if (!empty($_FILES['avatar']['name'])) {
            $file = $_FILES['avatar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Gagal mengunggah avatar.';
            } elseif (!in_array($ext, $allowedExt, true)) {
                $errors[] = 'Format avatar harus JPG/PNG.';
            } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
                $errors[] = 'Ukuran avatar maksimal 3MB.';
            } else {
                if (!is_dir(UPLOAD_AVATAR_DIR)) mkdir(UPLOAD_AVATAR_DIR, 0755, true);
                $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], UPLOAD_AVATAR_DIR . $newFileName)) {
                    // Hapus avatar lama
                    if ($avatarFileName && file_exists(UPLOAD_AVATAR_DIR . $avatarFileName)) {
                        @unlink(UPLOAD_AVATAR_DIR . $avatarFileName);
                    }
                    $avatarFileName = $newFileName;
                } else {
                    $errors[] = 'Gagal menyimpan file avatar di server.';
                }
            }
        }

        if (empty($errors)) {
            $upd = $db->prepare('UPDATE users SET nama_lengkap = ?, no_wa = ?, avatar = ? WHERE id = ?');
            $upd->execute([$namaLengkap, $noWa, $avatarFileName, $userId]);
            logActivity($userId, 'Memperbarui data profil');
            setFlash('success', 'Profil berhasil diperbarui.');
            redirect('profile');
        }
    }

    // -------------------------------------------------------
    // Ganti Password
    // -------------------------------------------------------
    if ($formType === 'change_password' && empty($errors)) {
        $passwordLama = $_POST['password_lama'] ?? '';
        $passwordBaru = $_POST['password_baru'] ?? '';
        $passwordKonfirmasi = $_POST['password_konfirmasi'] ?? '';

        if (!password_verify($passwordLama, $user['password'])) {
            $errors[] = 'Password lama tidak sesuai.';
        }
        if (strlen($passwordBaru) < 8) {
            $errors[] = 'Password baru minimal 8 karakter.';
        }
        if ($passwordBaru !== $passwordKonfirmasi) {
            $errors[] = 'Konfirmasi password baru tidak cocok.';
        }

        if (empty($errors)) {
            $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);
            $upd = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $upd->execute([$hash, $userId]);
            logActivity($userId, 'Mengganti password akun');
            setFlash('success', 'Password berhasil diubah.');
            redirect('profile');
        }
    }

    // Refresh data user setelah proses (jika ada error, tampilkan ulang form dgn data lama)
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}

$pageTitle = 'Profil Saya - KIP Kuliah';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
  <h1 class="text-xl font-bold">Profil Saya</h1>

  <?php if ($errors): ?>
    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg px-4 py-3 text-sm">
      <ul class="list-disc list-inside space-y-1">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- FORM DATA PROFIL & AVATAR -->
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sm:p-8 transition-theme">
    <h2 class="font-bold mb-5">Data Profil &amp; Avatar</h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-5">
      <input type="hidden" name="form_type" value="update_profile">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

      <div class="flex items-center gap-5">
        <img id="avatarPreview"
             src="<?= $user['avatar'] ? BASE_URL.'/assets/uploads/avatars/'.e($user['avatar']) : 'https://ui-avatars.com/api/?name='.urlencode($user['nama_lengkap']).'&background=2563eb&color=fff' ?>"
             class="w-20 h-20 rounded-full object-cover border-2 border-primary-200 dark:border-primary-700" alt="avatar">
        <div>
          <label class="inline-block cursor-pointer text-sm font-semibold px-4 py-2 rounded-lg border border-primary-600 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition">
            Ubah Foto
            <input type="file" name="avatar" id="avatarInput" accept=".jpg,.jpeg,.png" class="hidden">
          </label>
          <p class="text-xs text-gray-400 mt-1">JPG/PNG, maks 3MB</p>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
          <input type="text" name="nama_lengkap" value="<?= e($user['nama_lengkap']) ?>" required
                 class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Email</label>
          <input type="email" value="<?= e($user['email']) ?>" disabled
                 class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-700/50 px-3 py-2 text-sm text-gray-500">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium mb-1">No. WhatsApp</label>
          <input type="text" name="no_wa" value="<?= e($user['no_wa']) ?>"
                 class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
        </div>
      </div>

      <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-2.5 rounded-lg transition">
        Simpan Perubahan
      </button>
    </form>
  </div>

  <!-- FORM GANTI PASSWORD -->
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sm:p-8 transition-theme">
    <h2 class="font-bold mb-5">Ganti Password</h2>
    <form method="POST" class="space-y-5">
      <input type="hidden" name="form_type" value="change_password">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

      <div>
        <label class="block text-sm font-medium mb-1">Password Lama</label>
        <input type="password" name="password_lama" required
               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
      </div>
      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium mb-1">Password Baru</label>
          <input type="password" name="password_baru" required minlength="8"
                 class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Konfirmasi Password Baru</label>
          <input type="password" name="password_konfirmasi" required minlength="8"
                 class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none">
        </div>
      </div>

      <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-2.5 rounded-lg transition">
        Ubah Password
      </button>
    </form>
  </div>
</div>

<script>
  document.getElementById('avatarInput').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file) {
      document.getElementById('avatarPreview').src = URL.createObjectURL(file);
    }
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
