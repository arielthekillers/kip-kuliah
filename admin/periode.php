<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$pageTitle = 'Periode Pendaftaran - Admin';

$db = getDB();

// Aksi Tambah/Edit/Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama = trim($_POST['nama_periode']);
        $buka = $_POST['tanggal_buka'];
        $tutup = $_POST['tanggal_tutup'];
        $status = $_POST['status_periode'] === 'aktif' ? 'aktif' : 'nonaktif';
        
        if ($nama && $buka && $tutup) {
            $stmt = $db->prepare("INSERT INTO periode_pendaftaran (nama_periode, tanggal_buka, tanggal_tutup, status_periode) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama, $buka, $tutup, $status]);
            setFlash('success', 'Periode pendaftaran berhasil ditambahkan.');
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $nama = trim($_POST['nama_periode']);
        $buka = $_POST['tanggal_buka'];
        $tutup = $_POST['tanggal_tutup'];
        $status = $_POST['status_periode'] === 'aktif' ? 'aktif' : 'nonaktif';
        
        if ($id && $nama && $buka && $tutup) {
            $stmt = $db->prepare("UPDATE periode_pendaftaran SET nama_periode = ?, tanggal_buka = ?, tanggal_tutup = ?, status_periode = ? WHERE id = ?");
            $stmt->execute([$nama, $buka, $tutup, $status, $id]);
            setFlash('success', 'Periode pendaftaran berhasil diperbarui.');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            // Cek apakah sudah ada pendaftar di periode ini
            $cek = $db->prepare("SELECT COUNT(*) FROM pendaftaran WHERE periode_id = ?");
            $cek->execute([$id]);
            if ($cek->fetchColumn() > 0) {
                setFlash('error', 'Gagal dihapus: Sudah ada peserta yang mendaftar di periode ini.');
            } else {
                $stmt = $db->prepare("DELETE FROM periode_pendaftaran WHERE id = ?");
                $stmt->execute([$id]);
                setFlash('success', 'Periode berhasil dihapus.');
            }
        }
    }
    redirect('/admin/periode');
}

require_once __DIR__ . '/includes/header.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$totalPeriods = $db->query("SELECT COUNT(*) FROM periode_pendaftaran")->fetchColumn();
$totalPages = ceil($totalPeriods / $limit);

$stmt = $db->prepare("SELECT * FROM periode_pendaftaran ORDER BY tanggal_buka DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$periods = $stmt->fetchAll();
?>

<div class="mb-8 flex justify-between items-end">
  <div>
    <h1 class="text-2xl sm:text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-purple-600 dark:from-primary-400 dark:to-purple-400">Periode Pendaftaran</h1>
    <p class="text-gray-500 dark:text-gray-400 mt-1">Kelola gelombang pendaftaran jalur khusus (waktu buka/tutup).</p>
  </div>
  <button onclick="document.getElementById('modalForm').classList.remove('hidden'); document.getElementById('formAction').value='add'; document.getElementById('periodForm').reset(); document.getElementById('modalTitle').innerText='Tambah Periode Baru';" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white font-semibold shadow-lg hover:shadow-primary-500/30 transition transform hover:-translate-y-1">
    + Tambah Periode
  </button>
</div>

<div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 transition-theme overflow-hidden animate-fade-in-up">
  <div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
      <thead class="bg-gray-50/50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 uppercase text-xs font-semibold tracking-wider">
        <tr>
          <th class="px-6 py-4">ID</th>
          <th class="px-6 py-4">Nama Periode / Jalur</th>
          <th class="px-6 py-4">Tanggal Buka</th>
          <th class="px-6 py-4">Tanggal Tutup</th>
          <th class="px-6 py-4">Status</th>
          <th class="px-6 py-4 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        <?php if (empty($periods)): ?>
          <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">Belum ada periode pendaftaran.</td></tr>
        <?php else: ?>
          <?php foreach ($periods as $p): 
            $now = new DateTime();
            $buka = new DateTime($p['tanggal_buka']);
            $tutup = new DateTime($p['tanggal_tutup']);
            
            $isOpen = ($now >= $buka && $now <= $tutup && $p['status_periode'] === 'aktif');
            $statusLabel = $isOpen ? 'Sedang Buka' : ($now > $tutup ? 'Ditutup' : ($p['status_periode'] === 'nonaktif' ? 'Nonaktif' : 'Menunggu'));
            $statusClass = $isOpen ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
          ?>
            <tr class="hover:bg-primary-50/50 dark:hover:bg-gray-800/50 transition-colors">
              <td class="px-6 py-4 font-medium">#<?= $p['id'] ?></td>
              <td class="px-6 py-4 font-bold text-gray-900 dark:text-white"><?= e($p['nama_periode']) ?></td>
              <td class="px-6 py-4"><?= date('d M Y, H:i', strtotime($p['tanggal_buka'])) ?></td>
              <td class="px-6 py-4"><?= date('d M Y, H:i', strtotime($p['tanggal_tutup'])) ?></td>
              <td class="px-6 py-4">
                <span class="inline-block text-xs font-bold px-3 py-1 rounded-full shadow-sm <?= $statusClass ?>"><?= $statusLabel ?></span>
              </td>
              <td class="px-6 py-4">
                <div class="flex justify-center gap-2">
                  <button type="button" onclick="editPeriod(<?= htmlspecialchars(json_encode($p)) ?>)" class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-primary-200 text-primary-600 hover:bg-primary-50 dark:border-primary-800 dark:text-primary-400 dark:hover:bg-primary-900/30 transition">Edit</button>
                  <form method="POST" onsubmit="return confirm('Yakin ingin menghapus periode ini?');" class="inline-block">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30 transition">Hapus</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/30 dark:bg-gray-800/30">
    <span class="text-sm text-gray-500 dark:text-gray-400">
      Halaman <?= $page ?> dari <?= max(1, $totalPages) ?> (Total <?= $totalPeriods ?> periode)
    </span>
    <div class="flex gap-2">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition">Sebelumnya</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition">Selanjutnya</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal Form -->
<div id="modalForm" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
  <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg overflow-hidden transition-theme transform scale-100 animate-fade-in-up">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
      <h3 id="modalTitle" class="font-bold text-lg">Tambah Periode Baru</h3>
      <button type="button" onclick="document.getElementById('modalForm').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">&times;</button>
    </div>
    <form method="POST" id="periodForm" class="p-6 space-y-4">
      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="id" id="formId" value="">
      
      <div>
        <label class="block text-sm font-medium mb-1">Nama Periode / Jalur</label>
        <input type="text" name="nama_periode" id="formNama" required placeholder="Misal: KIP Kampus Jalur Prestasi"
               class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-900 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Tanggal Buka</label>
          <input type="datetime-local" name="tanggal_buka" id="formBuka" required
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-900 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Tanggal Tutup</label>
          <input type="datetime-local" name="tanggal_tutup" id="formTutup" required
                 class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-900 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Status Publikasi</label>
        <select name="status_periode" id="formStatus" class="w-full rounded-xl border-transparent bg-gray-100 dark:bg-gray-900 px-4 py-3 text-sm focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500 outline-none transition">
          <option value="aktif">Aktif (Bisa terlihat)</option>
          <option value="nonaktif">Nonaktif (Diarsipkan)</option>
        </select>
      </div>
      <div class="pt-4 flex justify-end gap-3">
        <button type="button" onclick="document.getElementById('modalForm').classList.add('hidden')" class="px-5 py-2.5 rounded-xl text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition">Batal</button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-500 hover:to-purple-500 text-white font-semibold transition shadow-md">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function editPeriod(p) {
  document.getElementById('modalTitle').innerText = 'Edit Periode';
  document.getElementById('formAction').value = 'edit';
  document.getElementById('formId').value = p.id;
  document.getElementById('formNama').value = p.nama_periode;
  document.getElementById('formBuka').value = p.tanggal_buka.replace(' ', 'T');
  document.getElementById('formTutup').value = p.tanggal_tutup.replace(' ', 'T');
  document.getElementById('formStatus').value = p.status_periode;
  document.getElementById('modalForm').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
