<?php
$pageTitle = 'Beranda Admin - KIP Kuliah';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch stats
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn(),
    'pending' => $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'menunggu_verifikasi'")->fetchColumn(),
    'verified' => $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'diverifikasi'")->fetchColumn(),
    'rejected' => $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'tidak_lolos_verifikasi'")->fetchColumn(),
    'revision' => $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'menunggu_perbaikan'")->fetchColumn(),
];

// Fetch recent registrations (latest 5 submitted)
$stmt = $db->query("
    SELECT p.*, u.nama_lengkap, u.email, pp.nama_periode
    FROM pendaftaran p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN periode_pendaftaran pp ON pp.id = p.periode_id
    WHERE p.status != 'draft'
    ORDER BY p.submitted_at DESC
    LIMIT 5
");
$recent = $stmt->fetchAll();
?>

<div class="mb-8">
  <h1 class="text-2xl sm:text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-purple-600 dark:from-primary-400 dark:to-purple-400">Dashboard Admin</h1>
  <p class="text-gray-500 dark:text-gray-400 mt-1">Ringkasan data pendaftaran beasiswa KIP Kuliah.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-10">
  <!-- Total Card -->
  <div class="lg:col-span-1 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-2xl p-4 shadow-xl border border-white/20 dark:border-gray-700/50 flex flex-col items-center justify-center text-center transition-transform hover:-translate-y-1">
    <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-2 shadow-inner">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
    </div>
    <h3 class="text-3xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['total']) ?></h3>
    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-1">Total Pendaftar</p>
  </div>

  <!-- Status Breakdown -->
  <div class="lg:col-span-3 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-2xl p-4 shadow-xl border border-white/20 dark:border-gray-700/50 flex flex-col justify-center">
    <h3 class="font-bold text-base text-gray-900 dark:text-white mb-3 border-b border-gray-100 dark:border-gray-800 pb-2">Rincian Status Pendaftaran</h3>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
      
      <!-- Pending -->
      <div class="flex flex-col items-center text-center group">
        <div class="w-10 h-10 rounded-xl bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-0.5"><?= number_format($stats['pending']) ?></h4>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium leading-tight">Menunggu<br>Verifikasi</p>
      </div>

      <!-- Revision -->
      <div class="flex flex-col items-center text-center group">
        <div class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </div>
        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-0.5"><?= number_format($stats['revision']) ?></h4>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium leading-tight">Menunggu<br>Perbaikan</p>
      </div>

      <!-- Verified -->
      <div class="flex flex-col items-center text-center group">
        <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-0.5"><?= number_format($stats['verified']) ?></h4>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium leading-tight">Lolos<br>Verifikasi</p>
      </div>

      <!-- Rejected -->
      <div class="flex flex-col items-center text-center group">
        <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-0.5"><?= number_format($stats['rejected']) ?></h4>
        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium leading-tight">Tidak<br>Lolos</p>
      </div>

    </div>
  </div>
</div>

<!-- Recent Submissions -->
<div class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 dark:border-gray-700/50 transition-theme overflow-hidden animate-fade-in-up" style="animation-delay: 100ms;">
  <div class="px-8 py-6 border-b border-gray-100/50 dark:border-gray-700/50 bg-white/50 dark:bg-gray-800/50 flex justify-between items-center">
    <div>
      <h2 class="font-bold text-xl">Pendaftar Terbaru</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Data pendaftaran yang baru saja dikirim oleh user.</p>
    </div>
    <a href="pendaftar" class="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:underline">Lihat Semua &rarr;</a>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
      <thead class="bg-gray-50/50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 uppercase text-xs font-semibold tracking-wider">
        <tr>
          <th class="px-8 py-4">Nama Lengkap & NIK</th>
          <th class="px-8 py-4">Kode Pendaftaran</th>
          <th class="px-8 py-4">Status</th>
          <th class="px-8 py-4">Waktu Submit</th>
          <th class="px-8 py-4 text-center">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        <?php if (empty($recent)): ?>
          <tr>
            <td colspan="5" class="px-8 py-12 text-center text-gray-400 dark:text-gray-500 bg-white/30 dark:bg-gray-800/30">
              Belum ada data pendaftar terbaru.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($recent as $r): $badge = statusBadge($r['status']); ?>
            <tr class="hover:bg-primary-50/50 dark:hover:bg-gray-800/50 transition-colors">
              <td class="px-8 py-4">
                <p class="font-bold text-gray-900 dark:text-white"><?= e($r['nama_lengkap']) ?></p>
                <p class="text-xs text-gray-500"><?= e($r['nik']) ?></p>
              </td>
              <td class="px-8 py-4">
                <p class="font-semibold text-primary-600 dark:text-primary-400"><?= e($r['kode_pendaftaran'] ?: '-') ?></p>
                <p class="text-xs text-gray-500"><?= e($r['nama_periode'] ?? '-') ?></p>
              </td>
              <td class="px-8 py-4">
                <span class="inline-block text-xs font-bold px-3 py-1 rounded-full shadow-sm <?= $badge['class'] ?>"><?= e($badge['label']) ?></span>
              </td>
              <td class="px-8 py-4 text-gray-500 dark:text-gray-400"><?= $r['submitted_at'] ? date('d M Y, H:i', strtotime($r['submitted_at'])) : '-' ?></td>
              <td class="px-8 py-4">
                <div class="flex justify-center">
                  <a href="verifikasi/<?= e($r['kode_transaksi']) ?>" class="text-xs font-semibold px-4 py-2 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 text-white hover:from-primary-500 hover:to-purple-500 transition shadow-md">
                    Verifikasi
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
