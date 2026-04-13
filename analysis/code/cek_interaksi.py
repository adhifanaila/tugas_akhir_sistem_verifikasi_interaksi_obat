import pandas as pd
import itertools
import json
import time
import csv
import os
import tracemalloc
import psutil

# ========== INIT ==========
print("Memulai proses pengecekan interaksi...")

tracemalloc.start()
start_time = time.time()

# ========== LOAD DATA ==========
df_norm = pd.read_csv("data_normalisasi.csv")
df_norm['Nama_Obat'] = df_norm['Nama_Obat'].str.strip().str.lower()
df_norm['Zat_Aktif'] = df_norm['Zat_Aktif'].str.strip().str.lower()

df_inter = pd.read_csv("data_interaksi.csv")
df_inter['zat_aktif_1'] = df_inter['zat_aktif_1'].str.strip().str.lower()
df_inter['zat_aktif_2'] = df_inter['zat_aktif_2'].str.strip().str.lower()

# ========== MULTIINDEX ==========
df_inter_multi = df_inter.set_index(['zat_aktif_1', 'zat_aktif_2'])

# Buat pasangan kebalikan
df_inter_rev = df_inter.rename(columns={
    'zat_aktif_1': 'zat_aktif_2',
    'zat_aktif_2': 'zat_aktif_1'
}).set_index(['zat_aktif_1', 'zat_aktif_2'])

# Gabungkan keduanya dan hilangkan duplikat
df_inter_final = pd.concat([df_inter_multi, df_inter_rev])
df_inter_final = df_inter_final[~df_inter_final.index.duplicated(keep='first')]

# ========== LOAD INPUT ==========
with open("resep_tinggi_20250814_180218.json", "r") as f:
    resep_input = json.load(f)

# Ambil semua nama obat dari resep
nama_obat_input = [obat.lower() for resep in resep_input for obat in resep["obat"]]

# ========== KONVERSI OBAT KE ZAT AKTIF ==========
zat_aktif_input = set()
for obat_input in nama_obat_input:
    for _, row in df_norm.iterrows():
        if row['Nama_Obat'] in obat_input:
            zat_aktif_input.add(row['Zat_Aktif'])

zat_aktif_input = list(zat_aktif_input)
print(f"Total zat aktif terdeteksi: {len(zat_aktif_input)}")

# ========== CEK INTERAKSI ==========
hasil_interaksi = []
sudah_dicek = set()

for a, b in itertools.combinations(zat_aktif_input, 2):
    pasangan = tuple(sorted((a, b)))  # agar tidak duplikat a+b dan b+a
    if pasangan in sudah_dicek:
        continue
    sudah_dicek.add(pasangan)
    
    try:
        level = df_inter_final.loc[(a, b), 'level']
        hasil_interaksi.append({
            "zat_aktif_1": a,
            "zat_aktif_2": b,
            "level": level
        })
        print(f"INTERAKSI DITEMUKAN: {a} + {b} --> Level {level}")
    except KeyError:
        print(f"Tidak ada interaksi: {a} + {b}")

# ========== OUTPUT HASIL ==========
print("\n=== RINGKASAN HASIL INTERAKSI (UNIK) ===")
seen_pairs = set()
for interaksi in hasil_interaksi:
    a, b = interaksi['zat_aktif_1'], interaksi['zat_aktif_2']
    pair = tuple(sorted([a, b]))
    if pair not in seen_pairs:
        seen_pairs.add(pair)
        print(f"{a} + {b} --> Level {interaksi['level']}")

print(f"\n Total interaksi unik yang ditemukan: {len(seen_pairs)}")

# Simpan hasil ke CSV
pd.DataFrame(hasil_interaksi).to_csv("hasil_interaksi_output.csv", index=False)

# ========== MONITORING ==========
current, peak = tracemalloc.get_traced_memory()
end_time = time.time()
duration = round(end_time - start_time, 4)

process = psutil.Process(os.getpid())
rss_memory_mb = process.memory_info().rss / (1024 * 1024)
peak_memory_mb = peak / (1024 * 1024)


print(f"\n=== HASIL MONITORING ===")
print(f"Durasi eksekusi     : {duration:.2f} detik")
print(f"Peak memory (heap)  : {peak_memory_mb:.2f} MB")
print(f"RSS memory (sistem) : {rss_memory_mb:.2f} MB")

# Simpan log benchmark
with open("benchmark_log.csv", "a", newline="") as f:
    writer = csv.writer(f)
    if os.stat("benchmark_log.csv").st_size == 0:
        writer.writerow(["script", "duration_seconds"])
    writer.writerow(["python-multiindex", duration])

# ✅ Simpan log ringkas ke file JSON
os.makedirs("logs", exist_ok=True)
log_data = {
    "script": "multiindex_pandas",
    "jumlah_interaksi": len(hasil_interaksi),
    "waktu_eksekusi": round(duration, 4),
    "heap_memory_mb": round(peak_memory_mb, 2),
    "rss_memory_mb": round(rss_memory_mb, 2)
}
with open("logs/cek_interaksi.json", "w") as log_file:
    json.dump(log_data, log_file, indent=2)

tracemalloc.stop()
