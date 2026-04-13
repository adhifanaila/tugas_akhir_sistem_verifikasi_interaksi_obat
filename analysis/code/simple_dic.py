# Simple Dictionary / Flat Dictionary

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

# Load data normalisasi
df_norm = pd.read_csv("data_normalisasi.csv")
df_norm['Nama_Obat'] = df_norm['Nama_Obat'].str.strip().str.lower()
df_norm['Zat_Aktif'] = df_norm['Zat_Aktif'].str.strip().str.lower()

# Load data interaksi
df_inter = pd.read_csv("data_interaksi.csv")
df_inter['zat_aktif_1'] = df_inter['zat_aktif_1'].str.strip().str.lower()
df_inter['zat_aktif_2'] = df_inter['zat_aktif_2'].str.strip().str.lower()

# Load input resep
with open("resep_tinggi_20250814_180218.json", "r") as f:
    resep_input = json.load(f)

# Ambil nama obat dari semua resep (lowercase)
nama_obat_input = [obat.lower() for resep in resep_input for obat in resep["obat"]]

# Cari zat aktif berdasarkan pencocokan parsial nama obat
zat_aktif_ditemukan = {}
for obat_input in nama_obat_input:
    for _, row in df_norm.iterrows():
        if row['Nama_Obat'] in obat_input:
            zat_aktif = row['Zat_Aktif']
            if zat_aktif not in zat_aktif_ditemukan:
                zat_aktif_ditemukan[zat_aktif] = []
            zat_aktif_ditemukan[zat_aktif].append(obat_input)

zat_aktif_input = list(zat_aktif_ditemukan.keys())

# Tampilkan zat aktif yang ditemukan
print("\n=== ZAT AKTIF YANG DITEMUKAN DARI INPUT ===")
for zat, obat_list in zat_aktif_ditemukan.items():
    print(f"{zat} <- ditemukan dari: {', '.join(set(obat_list))}")
    zat_aktif_input = list(zat_aktif_input)
    print(f"Total zat aktif terdeteksi: {len(zat_aktif_input)}")

# Buat lookup interaksi (bidirectional)
interaksi_lookup = {}
for _, row in df_inter.iterrows():
    a, b = row['zat_aktif_1'], row['zat_aktif_2']
    level = row['level']
    interaksi_lookup[f"{a}|{b}"] = level
    interaksi_lookup[f"{b}|{a}"] = level

# Cek kombinasi zat aktif
hasil_interaksi = []
for a, b in itertools.combinations(zat_aktif_input, 2):
    key = f"{a}|{b}"
    if key in interaksi_lookup:
        hasil_interaksi.append({
            "zat_aktif_1": a,
            "zat_aktif_2": b,
            "level": interaksi_lookup[key]
        })

# Output hasil interaksi
print("\n=== HASIL INTERAKSI ===")
for interaksi in hasil_interaksi:
    print(f"{interaksi['zat_aktif_1']} + {interaksi['zat_aktif_2']} --> Level {interaksi['level']}")

print(f"\nTotal interaksi yang ditemukan: {len(hasil_interaksi)}")

# Simpan ke CSV
pd.DataFrame(hasil_interaksi).to_csv("hasil_interaksi_output.csv", index=False)

# Benchmarking dan penggunaan memori
current, peak = tracemalloc.get_traced_memory()
end = time.time()
duration = end - start_time

# Info penggunaan memori sistem
process = psutil.Process(os.getpid())
rss_memory = process.memory_info().rss  # dalam byte

# Konversi ke MB
peak_memory_mb = peak / (1024 * 1024)
rss_memory_mb = rss_memory / (1024 * 1024)

# Cetak hasil monitoring
print(f"\n=== HASIL MONITORING ===")
print(f"Durasi eksekusi     : {duration:.4f} detik")
print(f"Peak memory (heap)  : {peak_memory_mb:.2f} MB")
print(f"RSS memory (sistem) : {rss_memory_mb:.2f} MB")

# Tulis log ke file CSV
with open("benchmark_log.csv", "a", newline="") as f:
    writer = csv.writer(f)
    if os.stat("benchmark_log.csv").st_size == 0:
        writer.writerow(["script", "duration_seconds"])
    writer.writerow(["python", duration])
    
# ✅ Simpan log ringkas ke file JSON
os.makedirs("logs", exist_ok=True)
log_data = {
    "script": "simple_dict",
    "jumlah_interaksi": len(hasil_interaksi),
    "waktu_eksekusi": round(duration, 4),
    "heap_memory_mb": round(peak_memory_mb, 2),
    "rss_memory_mb": round(rss_memory_mb, 2)
}
with open("logs/simple_dict.json", "w") as log_file:
    json.dump(log_data, log_file, indent=2)
    
# Hentikan pelacakan memori
tracemalloc.stop()
