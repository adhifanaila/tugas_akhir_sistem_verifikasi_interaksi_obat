import os
import pandas as pd
import json
import time
import tracemalloc
import psutil
from tabulate import tabulate

# === Debug Flag ===
DEBUG = True  # Ganti ke False jika tidak ingin melihat debug output

# Mulai benchmark
tracemalloc.start()
start_time = time.time()

# === 1. Load data normalisasi
df_norm = pd.read_csv("data_normalisasi.csv")
df_norm['Nama_Obat'] = df_norm['Nama_Obat'].str.strip().str.lower()
df_norm['Zat_Aktif'] = df_norm['Zat_Aktif'].str.strip().str.lower()

# === 2. Load resep
with open("resep_tinggi_20250814_180218.json", "r") as f:
    resep_input = json.load(f)

if DEBUG:
    print(f"Jumlah resep: {len(resep_input)}")

# === 3. Ekstrak nama obat & cari zat aktif
nama_obat_input = [obat.lower() for resep in resep_input for obat in resep["obat"]]
zat_aktif_ditemukan = set()
obat_tidak_dikenal = []

for obat_input in nama_obat_input:
    cocok = False
    for _, row in df_norm.iterrows():
        if row['Nama_Obat'] in obat_input:
            zat_aktif_ditemukan.add(row['Zat_Aktif'])
            cocok = True
    if not cocok:
        obat_tidak_dikenal.append(obat_input)

if DEBUG:
    print(f"Total nama obat unik input: {len(set(nama_obat_input))}")
    print(f"Total zat aktif ditemukan  : {len(zat_aktif_ditemukan)}")
    if obat_tidak_dikenal:
        print(f"Obat tidak dikenali di data normalisasi: {sorted(set(obat_tidak_dikenal))}")

zat_aktif_input = sorted(zat_aktif_ditemukan)

# === 4. Cek interaksi berdasarkan file index
hasil_interaksi = []
interaksi_terdeteksi = set()
total_combination_checked = 0
index_file_missing = []

for i in range(len(zat_aktif_input)):
    for j in range(i + 1, len(zat_aktif_input)):
        a = zat_aktif_input[i]
        b = zat_aktif_input[j]
        key = tuple(sorted([a, b]))
        total_combination_checked += 1

        if key in interaksi_terdeteksi:
            continue

        file_path = f"index/{a}.json"
        if not os.path.exists(file_path):
            index_file_missing.append(a)
            continue

        with open(file_path, "r", encoding="utf-8") as f:
            data_interaksi = json.load(f)
            for item in data_interaksi:
                if item["target"] == b:
                    hasil_interaksi.append({
                        "zat_aktif_1": a,
                        "zat_aktif_2": b,
                        "level": item["level"]
                    })
                    interaksi_terdeteksi.add(key)
                    break

if DEBUG:
    print(f"\n Total kombinasi zat aktif dicek: {total_combination_checked}")
    print(f"Total file index yang hilang  : {len(set(index_file_missing))}")
    if index_file_missing:
        print(f"File index tidak ditemukan untuk zat aktif: {sorted(set(index_file_missing))}")

# === 5. Tampilkan hasil
print("\n=== HASIL INTERAKSI (file-indexed) ===")
for item in hasil_interaksi:
    print(f"{item['zat_aktif_1']} + {item['zat_aktif_2']} --> Level {item['level']}")
print(f"\nTotal interaksi yang ditemukan: {len(hasil_interaksi)}")

# === 6. Simpan hasil ke CSV
pd.DataFrame(hasil_interaksi).to_csv("hasil_interaksi_output_fileindexed.csv", index=False)

# === 7. Benchmarking
current, peak = tracemalloc.get_traced_memory()
end_time = time.time()
duration = round(end_time - start_time, 4)
peak_memory_mb = peak / (1024 * 1024)
rss_memory_mb = psutil.Process(os.getpid()).memory_info().rss / (1024 * 1024)

print(f"\n=== HASIL MONITORING ===")
print(tabulate([[ 
    "file_indexed",
    len(hasil_interaksi),
    f"{duration:.3f} s",
    f"{peak_memory_mb:.2f} MB",
    f"{rss_memory_mb:.2f} MB"
]], headers=["Script", "Total Interaksi", "Durasi", "Heap Memori", "RSS Memori"]))

# === 8. Simpan log ke JSON
os.makedirs("logs", exist_ok=True)
log_data = {
    "script": "multiindex_pandas_filebased",
    "jumlah_interaksi": len(hasil_interaksi),
    "waktu_eksekusi": duration,
    "heap_memory_mb": round(peak_memory_mb, 2),
    "rss_memory_mb": round(rss_memory_mb, 2),
    "kombinasi_dicek": total_combination_checked,
    "file_index_missing": sorted(set(index_file_missing)),
    "obat_tidak_dikenal": sorted(set(obat_tidak_dikenal))
}
with open("logs/file_indexed.json", "w") as log_file:
    json.dump(log_data, log_file, indent=2)

# === Selesai
tracemalloc.stop()
print("\n=== SELESAI ===")
