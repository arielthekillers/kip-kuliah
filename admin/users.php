<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

// Handle hapus user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hapus_user') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId) {
        $db2 = getDB();
        // Cegah admin hapus diri sendiri
        if ($userId === (int)currentUserId()) {
            setFlash('error', 'Tidak dapat menghapus akun Anda sendiri.');
        } else {
            $db2->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$userId]);
            setFlash('success', 'User berhasil dihapus.');
        }
    }
    redirect('admin/users');
}

// Handle Aktivasi Manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'manual_activation') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId) {
        $db2 = getDB();
        $db2->prepare("UPDATE users SET status_akun = 'aktif', token_aktivasi = NULL WHERE id = ?")->execute([$userId]);
        logActivity((int)currentUserId(), "Aktivasi manual akun ID: $userId");
        setFlash('success', 'Akun berhasil diaktifkan secara manual.');
    }
    redirect('admin/users');
}

// Handle Resend Email Aktivasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend_email') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId) {
        $db2 = getDB();
        $stmt2 = $db2->prepare("SELECT nama_lengkap, email, token_aktivasi FROM users WHERE id = ?");
        $stmt2->execute([$userId]);
        $uData = $stmt2->fetch();
        
        if ($uData) {
            $token = $uData['token_aktivasi'];
            // Generate token baru jika kosong
            if (empty($token)) {
                $token = generateToken();
                $db2->prepare("UPDATE users SET token_aktivasi = ? WHERE id = ?")->execute([$token, $userId]);
            }
            
            $activationLink = BASE_URL . '/auth/activate?token=' . $token;
            $subject = 'Kirim Ulang: Aktivasi Akun KIP Kuliah';
            $message = '<p>Halo ' . e($uData['nama_lengkap']) . ',</p>';
            $message .= '<p>Ini adalah pengingat untuk mengaktifkan akun Anda di KIP Kuliah. Silakan klik link berikut untuk mengaktifkan akun Anda:</p>';
            $message .= '<p><a href="' . $activationLink . '">' . $activationLink . '</a></p>';
            $message .= '<p>Jika Anda merasa tidak pernah mendaftar, abaikan email ini.</p>';
            
            if (sendAppEmail($uData['email'], $subject, $message)) {
                logActivity((int)currentUserId(), "Resend email aktivasi untuk ID: $userId");
                setFlash('success', 'Email aktivasi berhasil dikirim ulang ke ' . e($uData['email']));
            } else {
                setFlash('error', 'Gagal mengirim email aktivasi.');
            }
        }
    }
    redirect('admin/users');
}

$db = getDB();
$search = $_GET['search'] ?? '';

$query = "SELECT u.id, u.nama_lengkap, u.email, u.created_at, u.status_akun,
                 COUNT(p.id) as jumlah_pendaftaran
          FROM users u
          LEFT JOIN pendaftaran p ON p.user_id = u.id
          WHERE u.role = 'user'";
$params = [];

if ($search !== '') {
    $query .= " AND (u.nama_lengkap LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " GROUP BY u.id ORDER BY u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manajemen User - KIP Kuliah';
require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-8">
  <h1 class="text-2xl sm:text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-purple-600 dark:from-primary-400 dark:to-purple-400">Manajemen User</h1>
  <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola data akun pengguna terdaftar.</p>
</div>

<!-- Search -->
<div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl p-6 shadow-xl border border-white/20 dark:border-gray-700/50 mb-8 animate-fade-in-up">
  <form method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
    <div class="flex-1 w-full">
      <label class="block text-sm font-medium mb-1">Cari Nama / Email</label>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Masukkan nama atau email..."
             class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
    </div>
    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white font-semibold shadow-lg transition transform hover:-translate-y-1 w-full sm:w-auto">
      Cari
    </button>
  </form>
</div>

<!-- Table -->
<div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 overflow-hidden animate-fade-in-up" style="animation-delay: 100ms;">
  <div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
      <thead class="bg-gray-50/50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 uppercase text-xs font-semibold tracking-wider">
        <tr>
          <th class="px-6 py-4">No</th>
          <th class="px-6 py-4">Nama & Email</th>
          <th class="px-6 py-4">Terdaftar</th>
          <th class="px-6 py-4 text-center">Pendaftaran</th>
          <th class="px-6 py-4 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="5" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
              Tidak ada user ditemukan.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($users as $i => $u): ?>
            <tr class="hover:bg-primary-50/50 dark:hover:bg-gray-800/50 transition-colors">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-100"><?= $i + 1 ?></td>
              <td class="px-6 py-4">
                <p class="font-bold text-gray-900 dark:text-white"><?= e($u['nama_lengkap']) ?></p>
                <p class="text-xs text-gray-500 mb-1"><?= e($u['email']) ?></p>
                <?php if ($u['status_akun'] === 'aktif'): ?>
                  <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Aktif</span>
                <?php else: ?>
                  <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">Belum Aktif</span>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4 text-gray-500 dark:text-gray-400"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td class="px-6 py-4 text-center">
                <span class="inline-block text-xs font-bold px-3 py-1 rounded-full <?= $u['jumlah_pendaftaran'] > 0 ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' ?>">
                  <?= $u['jumlah_pendaftaran'] ?> data
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <div class="flex items-center justify-center gap-2 flex-wrap">
                  <?php if ($u['status_akun'] !== 'aktif'): ?>
                    <button onclick="konfirmasiAktivasi(<?= $u['id'] ?>, '<?= e($u['nama_lengkap']) ?>')"
                            title="Aktivasi Manual"
                            class="text-xs font-semibold px-3 py-2 rounded-xl bg-green-50 text-green-600 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/40 transition">
                      Aktivasi
                    </button>
                    <button onclick="konfirmasiResend(<?= $u['id'] ?>, '<?= e($u['email']) ?>')"
                            title="Resend Email Aktivasi"
                            class="text-xs font-semibold px-3 py-2 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40 transition">
                      Resend
                    </button>
                  <?php endif; ?>
                  <button onclick="konfirmasiHapusUser(<?= $u['id'] ?>, '<?= e($u['nama_lengkap']) ?>', <?= $u['jumlah_pendaftaran'] ?>)"
                          title="Hapus User"
                          class="text-xs font-semibold px-3 py-2 rounded-xl border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-800/30">
    <span class="text-sm text-gray-500 dark:text-gray-400">Total <?= count($users) ?> user terdaftar</span>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Modal Hapus User -->
<div id="modal-hapus-user" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm">
  <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
    <div class="flex items-center gap-4 mb-4">
      <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/40 text-red-600 flex items-center justify-center flex-shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      </div>
      <div>
        <h3 class="font-bold text-lg text-gray-900 dark:text-white">Hapus User</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat dibatalkan.</p>
      </div>
    </div>
    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">Yakin ingin menghapus akun <strong id="nama-hapus-user" class="text-gray-900 dark:text-white"></strong>?</p>
    <p id="warn-pendaftaran" class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 mb-4 hidden">⚠️ User ini memiliki data pendaftaran. Hapus terlebih dahulu pendaftarannya sebelum menghapus akun.</p>
    <form method="POST" id="form-hapus-user">
      <input type="hidden" name="action" value="hapus_user">
      <input type="hidden" name="user_id" id="id-hapus-user">
      <div class="flex gap-3 justify-end">
        <button type="button" onclick="tutupModalHapusUser()" class="px-5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-800 transition">Batal</button>
        <button type="submit" id="btn-hapus-user" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed">Ya, Hapus</button>
      </div>
    </form>
  </div>
</div>

<script>
function konfirmasiHapusUser(id, nama, jumlahPendaftaran) {
  document.getElementById('nama-hapus-user').textContent = nama;
  document.getElementById('id-hapus-user').value = id;
  const warnEl = document.getElementById('warn-pendaftaran');
  const btnHapus = document.getElementById('btn-hapus-user');
  if (jumlahPendaftaran > 0) {
    warnEl.classList.remove('hidden');
    btnHapus.disabled = true;
  } else {
    warnEl.classList.add('hidden');
    btnHapus.disabled = false;
  }
  document.getElementById('modal-hapus-user').classList.remove('hidden');
  document.getElementById('modal-hapus-user').classList.add('flex');
}
function tutupModalHapusUser() {
  document.getElementById('modal-hapus-user').classList.add('hidden');
  document.getElementById('modal-hapus-user').classList.remove('flex');
}

function konfirmasiAktivasi(id, nama) {
  if (confirm('Yakin ingin mengaktifkan akun ' + nama + ' secara manual?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="action" value="manual_activation"><input type="hidden" name="user_id" value="' + id + '">';
    document.body.appendChild(form);
    form.submit();
  }
}

function konfirmasiResend(id, email) {
  if (confirm('Kirim ulang email aktivasi ke ' + email + '?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="action" value="resend_email"><input type="hidden" name="user_id" value="' + id + '">';
    document.body.appendChild(form);
    form.submit();
  }
}
</script>
