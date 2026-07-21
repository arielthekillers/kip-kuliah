/**
 * pendaftaran.js
 * Mengatur navigasi wizard 4-step, auto-save ke server, upload dokumen,
 * dan ringkasan review sebelum submit.
 */

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('formPendaftaran');
  const sections = document.querySelectorAll('.step-section');
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const btnSubmit = document.getElementById('btnSubmit');
  const btnSaveDraft = document.getElementById('btnSaveDraft');
  const alertSukses = document.getElementById('autosaveAlert');
  const alertError = document.getElementById('autosaveError');

  let currentStep = window.CURRENT_STEP || 1;
  let highestStepReached = currentStep;
  const totalSteps = 5;
  const kodeTransaksi = window.KODE_TRANSAKSI;
  const baseUrl = window.APP_BASE_URL;

  initWilayahDropdown();
  showStep(currentStep);
  initUploadDokumen();

  // Make step circles clickable
  document.querySelectorAll('[data-step-circle]').forEach((circle) => {
    circle.classList.add('cursor-pointer', 'hover:scale-110', 'active:scale-95', 'transition-transform');
    circle.addEventListener('click', () => {
      const num = parseInt(circle.dataset.stepCircle, 10);
      if (num <= highestStepReached && num !== currentStep) {
        // Optional: auto-save current step before jumping? No, just jump.
        currentStep = num;
        showStep(currentStep);
      } else if (num > highestStepReached) {
        // Could show a toast saying "Selesaikan langkah sebelumnya dulu"
      }
    });
  });

  // ---------------------------------------------------------
  // Navigasi antar step
  // ---------------------------------------------------------
  function showStep(step) {
    sections.forEach((sec) => {
      sec.classList.toggle('hidden', parseInt(sec.dataset.step, 10) !== step);
    });

    // Update indikator visual
    document.querySelectorAll('[data-step-circle]').forEach((circle) => {
      const num = parseInt(circle.dataset.stepCircle, 10);
      circle.classList.remove('bg-primary-600', 'border-primary-600', 'text-white', 'text-primary-600', 'border-gray-300', 'text-gray-400');
      if (num < step) {
        circle.classList.add('bg-primary-600', 'border-primary-600', 'text-white');
        circle.innerHTML = '&#10003;';
      } else if (num === step) {
        circle.classList.add('border-primary-600', 'text-primary-600');
        circle.innerHTML = num;
      } else {
        circle.classList.add('border-gray-300', 'text-gray-400');
        circle.innerHTML = num;
      }
    });

    btnPrev.classList.toggle('invisible', step === 1);
    btnNext.classList.toggle('hidden', step === totalSteps);
    btnSubmit.classList.toggle('hidden', step !== totalSteps);

    if (step === totalSteps) renderRingkasan();

    window.scrollTo({ top: form.offsetTop - 90, behavior: 'smooth' });
  }

  btnPrev.addEventListener('click', () => {
    if (currentStep > 1) {
      currentStep -= 1;
      showStep(currentStep);
    }
  });

  btnNext.addEventListener('click', async () => {
    if (!validasiStepAktif()) return;
    const ok = await simpanDraft(currentStep < totalSteps ? currentStep + 1 : currentStep);
    if (ok && currentStep < totalSteps) {
      currentStep += 1;
      highestStepReached = Math.max(highestStepReached, currentStep);
      showStep(currentStep);
    }
  });

  btnSaveDraft.addEventListener('click', async () => {
    await simpanDraft(currentStep);
  });

  btnSubmit.addEventListener('click', async () => {
    if (!validasiStepAktif()) return;

    const checks = form.querySelectorAll('input[type=checkbox][required]');
    for (const chk of checks) {
      if (!chk.checked) {
        tampilkanError('Anda harus menyetujui seluruh pernyataan sebelum mengirim pendaftaran.');
        return;
      }
    }

    if (!confirm('Setelah dikirim, data pendaftaran tidak dapat diubah selama proses verifikasi. Lanjutkan?')) return;

    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Mengirim...';

    try {
      const formData = new FormData(form);
      formData.append('action', 'submit_final');

      const res = await fetch(`${baseUrl}/ajax/simpan_step`, { method: 'POST', body: formData, credentials: 'same-origin' });
      const json = await res.json();

      if (json.success) {
        window.location.href = `${baseUrl}/detail_pendaftaran/${kodeTransaksi}?sent=1`;
      } else {
        tampilkanError(json.message || 'Gagal mengirim pendaftaran.');
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Kirim Pendaftaran';
      }
    } catch (err) {
      tampilkanError('Terjadi kesalahan jaringan. Silakan coba lagi.');
      btnSubmit.disabled = false;
      btnSubmit.textContent = 'Kirim Pendaftaran';
    }
  });

  // ---------------------------------------------------------
  // Validasi sederhana step aktif (native browser validity)
  // ---------------------------------------------------------
  function validasiStepAktif() {
    const activeSection = document.querySelector(`.step-section[data-step="${currentStep}"]`);
    const inputs = activeSection.querySelectorAll('input, select');
    for (const input of inputs) {
      if (!input.checkValidity()) {
        input.reportValidity();
        return false;
      }
    }
    return true;
  }

  // ---------------------------------------------------------
  // Auto-save / Simpan Draf ke server (AJAX)
  // ---------------------------------------------------------
  async function simpanDraft(nextStep) {
    try {
      const formData = new FormData(form);
      formData.append('action', 'save_draft');
      formData.append('step', currentStep);
      formData.append('next_step', nextStep);

      const res = await fetch(`${baseUrl}/ajax/simpan_step`, { method: 'POST', body: formData, credentials: 'same-origin' });
      const json = await res.json();

      if (json.success) {
        tampilkanSukses();
        return true;
      }
      tampilkanError(json.message || 'Gagal menyimpan data.');
      return false;
    } catch (err) {
      tampilkanError('Terjadi kesalahan jaringan saat menyimpan data.');
      return false;
    }
  }

  function tampilkanSukses() {
    alertError.classList.add('hidden');
    alertSukses.classList.remove('hidden');
    setTimeout(() => alertSukses.classList.add('hidden'), 2500);
  }

  function tampilkanError(msg) {
    alertSukses.classList.add('hidden');
    alertError.textContent = msg;
    alertError.classList.remove('hidden');
  }

  // ---------------------------------------------------------
  // Upload Dokumen (Step 3)
  // ---------------------------------------------------------
  function initUploadDokumen() {
    document.querySelectorAll('.upload-card').forEach((card) => {
      const btnPilih = card.querySelector('.btn-pilih-file');
      const inputFile = card.querySelector('.input-dokumen');
      const statusEl = card.querySelector('.doc-status');
      const docType = card.dataset.docType;

      btnPilih.addEventListener('click', () => inputFile.click());

      inputFile.addEventListener('change', async () => {
        const file = inputFile.files[0];
        if (!file) return;

        const maxSize = 3 * 1024 * 1024;
        const allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
        const ext = file.name.split('.').pop().toLowerCase();

        if (!allowedExt.includes(ext)) {
          statusEl.textContent = 'Format file tidak didukung (PDF/JPG saja).';
          statusEl.className = 'doc-status text-xs mb-3 text-red-500 font-medium';
          return;
        }
        if (file.size > maxSize) {
          statusEl.textContent = 'Ukuran file melebihi 3MB.';
          statusEl.className = 'doc-status text-xs mb-3 text-red-500 font-medium';
          return;
        }

        statusEl.textContent = 'Mengunggah...';
        statusEl.className = 'doc-status text-xs mb-3 text-gray-400';

        try {
          const fd = new FormData();
          fd.append('kode_transaksi', kodeTransaksi);
          fd.append('jenis_dokumen', docType);
          fd.append('file_dokumen', file);
          fd.append('csrf_token', form.querySelector('[name=csrf_token]').value);

          const res = await fetch(`${baseUrl}/ajax/upload_dokumen`, { method: 'POST', body: fd, credentials: 'same-origin' });
          const json = await res.json();

          if (json.success) {
            statusEl.innerHTML = '&#10003; ' + json.nama_file;
            statusEl.className = 'doc-status text-xs mb-3 text-green-600 dark:text-green-400 font-medium';
            btnPilih.textContent = 'Ganti File';
            tampilkanSukses();
          } else {
            statusEl.textContent = json.message || 'Gagal mengunggah file.';
            statusEl.className = 'doc-status text-xs mb-3 text-red-500 font-medium';
          }
        } catch (err) {
          statusEl.textContent = 'Terjadi kesalahan jaringan.';
          statusEl.className = 'doc-status text-xs mb-3 text-red-500 font-medium';
        }
      });
    });
  }

  // ---------------------------------------------------------
  // Ringkasan Review (Step 4)
  // ---------------------------------------------------------
  function renderRingkasan() {
    const fd = new FormData(form);
    const val = (name) => (fd.get(name) || '-');

    const jk = val('jenis_kelamin') === 'L' ? 'Laki-laki' : (val('jenis_kelamin') === 'P' ? 'Perempuan' : '-');

    const html = `
      <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2 bg-gray-50 dark:bg-gray-700/40 rounded-xl p-5">
        <div><span class="text-gray-400">NIK</span><br><span class="font-medium">${val('nik')}</span></div>
        <div><span class="text-gray-400">Nama Lengkap</span><br><span class="font-medium">${val('nama_lengkap')}</span></div>
        <div><span class="text-gray-400">Tempat, Tanggal Lahir</span><br><span class="font-medium">${val('tempat_lahir')}, ${val('tanggal_lahir')}</span></div>
        <div><span class="text-gray-400">Jenis Kelamin</span><br><span class="font-medium">${jk}</span></div>
        <div class="sm:col-span-2"><span class="text-gray-400">Alamat</span><br><span class="font-medium inline-block leading-relaxed">${val('alamat_jalan')}, RT ${val('rt')}/RW ${val('rw')}<br>${document.getElementById('hidKelurahanNama').value || '-'}, ${document.getElementById('hidKecamatanNama').value || '-'}<br>${document.getElementById('hidKabupatenNama').value || '-'}, ${document.getElementById('hidProvinsiNama').value || '-'} ${val('kode_pos')}</span></div>
        <div><span class="text-gray-400">No. WA Aktif</span><br><span class="font-medium">${val('no_wa_aktif')}</span></div>
        <div><span class="text-gray-400">Email Aktif</span><br><span class="font-medium">${val('email_aktif')}</span></div>
      </div>
      <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2 bg-gray-50 dark:bg-gray-700/40 rounded-xl p-5">
        <div><span class="text-gray-400">Perguruan Tinggi</span><br><span class="font-medium">${val('nama_lembaga')}</span></div>
        <div><span class="text-gray-400">Program Studi</span><br><span class="font-medium">${val('program_studi')}</span></div>
        <div><span class="text-gray-400">Jenjang / Jalur</span><br><span class="font-medium">${val('jenjang')} / ${val('jalur_masuk')}</span></div>
        <div><span class="text-gray-400">NISN / NIM</span><br><span class="font-medium">${val('nisn')} / ${val('nim')}</span></div>
        <div><span class="text-gray-400">Tahun Masuk</span><br><span class="font-medium">${val('tahun_masuk')}</span></div>
      </div>
    `;
    document.getElementById('ringkasanData').innerHTML = html;
  }
});
