/**
 * api_wilayah.js
 * Integrasi REST API https://wilayah.id/ untuk dropdown bertingkat:
 * Provinsi -> Kabupaten/Kota -> Kecamatan -> Kelurahan/Desa
 *
 * Dokumentasi endpoint publik wilayah.id:
 *   GET /api/provinces.json
 *   GET /api/regencies/{province_code}.json
 *   GET /api/districts/{regency_code}.json
 *   GET /api/villages/{district_code}.json
 */

const baseUrl = window.APP_BASE_URL || '';
const WILAYAH_API_PROXY = `${baseUrl}/ajax/proxy_wilayah?path=`;

const WilayahAPI = {
  async getProvinces() {
    const res = await fetch(`${WILAYAH_API_PROXY}provinces.json`);
    if (!res.ok) throw new Error('Gagal memuat data provinsi');
    const json = await res.json();
    return json.data || [];
  },
  async getRegencies(provinceCode) {
    const res = await fetch(`${WILAYAH_API_PROXY}regencies/${provinceCode}.json`);
    if (!res.ok) throw new Error('Gagal memuat data kabupaten/kota');
    const json = await res.json();
    return json.data || [];
  },
  async getDistricts(regencyCode) {
    const res = await fetch(`${WILAYAH_API_PROXY}districts/${regencyCode}.json`);
    if (!res.ok) throw new Error('Gagal memuat data kecamatan');
    const json = await res.json();
    return json.data || [];
  },
  async getVillages(districtCode) {
    const res = await fetch(`${WILAYAH_API_PROXY}villages/${districtCode}.json`);
    if (!res.ok) throw new Error('Gagal memuat data kelurahan/desa');
    const json = await res.json();
    return json.data || [];
  },
};

/**
 * Mengisi elemen <select> dengan daftar wilayah.
 * @param {HTMLSelectElement} selectEl
 * @param {Array} items - [{code, name}]
 * @param {string} placeholder
 * @param {string|null} selectedCode - kode yang harus terpilih (untuk mode edit draft)
 */
function isiSelectWilayah(selectEl, items, placeholder, selectedCode = null) {
  selectEl.innerHTML = `<option value="">${placeholder}</option>`;
  items.forEach((item) => {
    const opt = document.createElement('option');
    opt.value = item.code;
    opt.textContent = item.name;
    opt.dataset.name = item.name;
    if (selectedCode && String(item.code) === String(selectedCode)) {
      opt.selected = true;
    }
    selectEl.appendChild(opt);
  });
  selectEl.disabled = items.length === 0;
}

/**
 * Inisialisasi dropdown wilayah bertingkat pada form pendaftaran.
 * Dipanggil dari pendaftaran.js setelah DOM siap.
 */
async function initWilayahDropdown() {
  const selProvinsi = document.getElementById('selProvinsi');
  const selKabupaten = document.getElementById('selKabupaten');
  const selKecamatan = document.getElementById('selKecamatan');
  const selKelurahan = document.getElementById('selKelurahan');

  if (!selProvinsi) return;

  const preset = window.wilayahTersimpan || {};

  const setHiddenName = (id, name) => {
    const hid = document.getElementById(id);
    if (hid) hid.value = name || '';
  };

  try {
    // 1. Muat Provinsi
    const provinces = await WilayahAPI.getProvinces();
    isiSelectWilayah(selProvinsi, provinces, '-- Pilih Provinsi --', preset.provinsi_id);

    // Jika ada preset provinsi, lanjut muat kabupaten
    if (preset.provinsi_id) {
      const regencies = await WilayahAPI.getRegencies(preset.provinsi_id);
      isiSelectWilayah(selKabupaten, regencies, '-- Pilih Kabupaten/Kota --', preset.kabupaten_id);
      selKabupaten.disabled = false;

      if (preset.kabupaten_id) {
        const districts = await WilayahAPI.getDistricts(preset.kabupaten_id);
        isiSelectWilayah(selKecamatan, districts, '-- Pilih Kecamatan --', preset.kecamatan_id);
        selKecamatan.disabled = false;

        if (preset.kecamatan_id) {
          const villages = await WilayahAPI.getVillages(preset.kecamatan_id);
          isiSelectWilayah(selKelurahan, villages, '-- Pilih Kelurahan/Desa --', preset.kelurahan_id);
          selKelurahan.disabled = false;
        }
      }
    }
  } catch (err) {
    console.error(err);
  }

  function unlockKodePos() {
    const kodePosInput = document.querySelector('input[name="kode_pos"]');
    if (kodePosInput) {
      kodePosInput.value = '';
      kodePosInput.removeAttribute('readonly');
      kodePosInput.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'cursor-not-allowed', 'opacity-80');
      kodePosInput.classList.add('bg-gray-100', 'dark:bg-gray-800/50', 'focus:bg-white', 'dark:focus:bg-gray-900', 'focus:ring-2', 'focus:ring-primary-500');
    }
  }

  // Event: Provinsi berubah -> muat Kabupaten
  selProvinsi.addEventListener('change', async function () {
    const opt = this.options[this.selectedIndex];
    setHiddenName('hidProvinsiNama', opt ? opt.dataset.name : '');
    setHiddenName('hidKabupatenNama', '');
    setHiddenName('hidKecamatanNama', '');
    setHiddenName('hidKelurahanNama', '');
    unlockKodePos();

    isiSelectWilayah(selKabupaten, [], '-- Pilih Kabupaten/Kota --');
    isiSelectWilayah(selKecamatan, [], '-- Pilih Kecamatan --');
    isiSelectWilayah(selKelurahan, [], '-- Pilih Kelurahan/Desa --');
    unlockKodePos();
    selKecamatan.disabled = true;
    selKelurahan.disabled = true;

    if (!this.value) { selKabupaten.disabled = true; return; }
    try {
      const regencies = await WilayahAPI.getRegencies(this.value);
      isiSelectWilayah(selKabupaten, regencies, '-- Pilih Kabupaten/Kota --');
    } catch (err) { console.error(err); }
  });

  // Event: Kabupaten berubah -> muat Kecamatan
  selKabupaten.addEventListener('change', async function () {
    const opt = this.options[this.selectedIndex];
    setHiddenName('hidKabupatenNama', opt ? opt.dataset.name : '');
    setHiddenName('hidKecamatanNama', '');
    setHiddenName('hidKelurahanNama', '');

    isiSelectWilayah(selKecamatan, [], '-- Pilih Kecamatan --');
    isiSelectWilayah(selKelurahan, [], '-- Pilih Kelurahan/Desa --');
    selKelurahan.disabled = true;

    if (!this.value) { selKecamatan.disabled = true; return; }
    try {
      const districts = await WilayahAPI.getDistricts(this.value);
      isiSelectWilayah(selKecamatan, districts, '-- Pilih Kecamatan --');
    } catch (err) { console.error(err); }
  });

  // Event: Kecamatan berubah -> muat Kelurahan
  selKecamatan.addEventListener('change', async function () {
    const opt = this.options[this.selectedIndex];
    setHiddenName('hidKecamatanNama', opt ? opt.dataset.name : '');
    setHiddenName('hidKelurahanNama', '');

    isiSelectWilayah(selKelurahan, [], '-- Pilih Kelurahan/Desa --');
    unlockKodePos();

    if (!this.value) { selKelurahan.disabled = true; return; }
    try {
      const villages = await WilayahAPI.getVillages(this.value);
      isiSelectWilayah(selKelurahan, villages, '-- Pilih Kelurahan/Desa --');
    } catch (err) { console.error(err); }
  });

  // Event: Kelurahan berubah -> simpan nama
  selKelurahan.addEventListener('change', async function () {
    const opt = this.options[this.selectedIndex];
    const kelurahanName = opt ? opt.dataset.name : '';
    setHiddenName('hidKelurahanNama', kelurahanName);

    // Auto-fill Kode Pos
    if (kelurahanName) {
      try {
        const res = await fetch(`${baseUrl}/ajax/proxy_kodepos?q=${encodeURIComponent(kelurahanName)}`);
        if (res.ok) {
          const json = await res.json();
          if (json.data && json.data.length > 0) {
            // Find the closest match by regency if possible, or just use the first result
            const kabName = document.getElementById('hidKabupatenNama').value;
            let match = json.data.find(d => kabName.toLowerCase().includes(d.regency.toLowerCase()));
            if (!match) match = json.data[0];
            
            const kodePosInput = document.querySelector('input[name="kode_pos"]');
            if (kodePosInput) {
              kodePosInput.value = match.code;
              kodePosInput.setAttribute('readonly', 'readonly');
              kodePosInput.classList.remove('bg-gray-100', 'dark:bg-gray-800/50', 'focus:bg-white', 'dark:focus:bg-gray-900', 'focus:ring-2', 'focus:ring-primary-500');
              kodePosInput.classList.add('bg-gray-200', 'dark:bg-gray-700', 'cursor-not-allowed', 'opacity-80');
            }
          } else {
            unlockKodePos();
          }
        } else {
          unlockKodePos();
        }
      } catch (err) {
        console.error('Gagal mengambil kode pos:', err);
        unlockKodePos();
      }
    } else {
      unlockKodePos();
    }
  });
}
