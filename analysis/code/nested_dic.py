import pandas as pd
import itertools
import json
import time
import csv
import os
import tracemalloc
import psutil

tracemalloc.start()
start_time = time.time()

# 1. Load data normalisasi
df_norm = pd.read_csv("data_normalisasi.csv")
df_norm['Nama_Obat'] = df_norm['Nama_Obat'].str.strip().str.lower()
df_norm['Zat_Aktif'] = df_norm['Zat_Aktif'].str.strip().str.lower()

# 2. Load data interaksi
df_inter = pd.read_csv("data_interaksi.csv")
df_inter['zat_aktif_1'] = df_inter['zat_aktif_1'].str.strip().str.lower()
df_inter['zat_aktif_2'] = df_inter['zat_aktif_2'].str.strip().str.lower()

# 3. Buat struktur multilevel index
multilevel_index = {}
for _, row in df_inter.iterrows():
    a, b, level = row['zat_aktif_1'], row['zat_aktif_2'], row['level']
    if a not in multilevel_index:
        multilevel_index[a] = {}
    if b not in multilevel_index:
        multilevel_index[b] = {}
    multilevel_index[a][b] = level
    multilevel_index[b][a] = level  # karena relasi simetris

# 4. Load input resep
with open("resep_tinggi_20250814_180218.json", "r") as f:
    resep_input = json.load(f)

# 5. Ambil nama obat dari semua resep
nama_obat_input = [obat.lower() for resep in resep_input for obat in resep["obat"]]

# 6. Cari zat aktif dari nama obat
zat_aktif_input = set()
for obat_input in nama_obat_input:
    for _, row in df_norm.iterrows():
        if row['Nama_Obat'] in obat_input:
            zat_aktif_input.add(row['Zat_Aktif'])

zat_aktif_input = list(zat_aktif_input)

# >>> TAMBAHAN: Tampilkan zat aktif yang ditemukan
print("\n=== ZAT AKTIF YANG DITEMUKAN ===")
for zat in zat_aktif_input:
    print(f"- {zat}")
print(f"\nTotal zat aktif yang ditemukan: {len(zat_aktif_input)}")

# 7. Cek kombinasi interaksi via multilevel index
hasil_interaksi = []
for a, b in itertools.combinations(zat_aktif_input, 2):
    level = multilevel_index.get(a, {}).get(b)
    if level:
        hasil_interaksi.append({
            "zat_aktif_1": a,
            "zat_aktif_2": b,
            "level": level
        })

# 8. Output hasil
print("\n=== HASIL INTERAKSI ===")
for interaksi in hasil_interaksi:
    print(f"{interaksi['zat_aktif_1']} + {interaksi['zat_aktif_2']} --> Level {interaksi['level']}")

# Tambahan: Jumlah interaksi
print(f"\nTotal interaksi yang ditemukan: {len(hasil_interaksi)}")

# 9. Simpan ke CSV
pd.DataFrame(hasil_interaksi).to_csv("hasil_interaksi_output_multilevel.csv", index=False)

# 10. Logging waktu eksekusi
current, peak = tracemalloc.get_traced_memory()
end_time = time.time()
duration = round(end_time - start_time, 2)

# Info penggunaan memori sistem
process = psutil.Process(os.getpid())
rss_memory = process.memory_info().rss  # dalam byte

# Konversi ke satuan lebih mudah dibaca
peak_memory_mb = peak / (1024 * 1024)
rss_memory_mb = rss_memory / (1024 * 1024)
    
# Cetak hasil
print(f"\n=== HASIL MONITORING ===")
print(f"Durasi eksekusi     : {duration:.4f} detik")
print(f"Peak memory (heap)  : {peak_memory_mb:.2f} MB")
print(f"RSS memory (sistem) : {rss_memory_mb:.2f} MB")

# Simpan log benchmark
with open("benchmark_log.csv", "a", newline="") as f:
    writer = csv.writer(f)
    if os.stat("benchmark_log.csv").st_size == 0:
        writer.writerow(["script", "duration_seconds"])
    writer.writerow(["python_multilevel", duration])

# ✅ Simpan log ringkas ke file JSON
os.makedirs("logs", exist_ok=True)
log_data = {
    "script": "nested_dict",
    "jumlah_interaksi": len(hasil_interaksi),
    "waktu_eksekusi": round(duration, 4),
    "heap_memory_mb": round(peak_memory_mb, 2),
    "rss_memory_mb": round(rss_memory_mb, 2)
}
with open("logs/nested_dict.json", "w") as log_file:
    json.dump(log_data, log_file, indent=2)
    
tracemalloc.stop()