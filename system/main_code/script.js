let obatCount = 1;

document.getElementById('add-row').addEventListener('click', function () {
  obatCount++;
  const container = document.getElementById('obat-container');
  const newRow = document.createElement('div');
  newRow.classList.add('obat-row');

  newRow.innerHTML = `
    <h4>Obat ${obatCount}</h4>
    <button type="button" class="remove-obat-btn">Hapus</button>

    <label for="obat-${obatCount}">Nama Obat</label>
    <select name="obat[]" id="obat-${obatCount}" class="select-obat" required>
      <option value="">Pilih Obat</option>
      <option value="obat1">Obat 1</option>
      <option value="obat2">Obat 2</option>
      <option value="obat3">Obat 3</option>
    </select>

    <label for="dosis-${obatCount}">Dosis</label>
    <input type="text" name="dosis[]" id="dosis-${obatCount}" placeholder="Masukkan dosis" required />

    <label for="aturanpakai-${obatCount}">Aturan Pakai</label>
    <input type="text" name="aturanpakai[]" id="aturanpakai-${obatCount}" placeholder="Masukkan aturan pakai obat" required />
  `;

  container.appendChild(newRow);

  // Aktifkan Select2 jika tersedia
  if (typeof $ !== 'undefined' && typeof $.fn.select2 === 'function') {
    const selectElement = newRow.querySelector('select');
    $(selectElement).select2({
      placeholder: "Pilih Obat",
      allowClear: true
    });
  }
});

// Hapus baris resep jika tombol Hapus diklik
document.addEventListener('click', function (e) {
  if (e.target.classList.contains('remove-obat-btn')) {
    e.target.closest('.obat-row').remove();
  }
});
