# simple_dictionary_cek_interaksi

import pandas as pd
import itertools
import json
import time
import csv
import os
from datetime import datetime

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
with open("input_resep_sample.json", "r") as f:
    resep_input = json.load(f)["resep"]

# Ambil nama obat dari semua resep (lowercase)
nama_obat_input = [obat.lower() for resep in resep_input for obat in resep["obat"]]

# Cari zat aktif berdasarkan pencocokan parsial nama obat
zat_aktif_input = set()
for obat_input in nama_obat_input:
    for _, row in df_norm.iterrows():
        if row['Nama_Obat'] in obat_input:
            zat_aktif_input.add(row['Zat_Aktif'])

zat_aktif_input = list(zat_aktif_input)

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

# Output hasil
print("\n=== HASIL INTERAKSI ===")
for interaksi in hasil_interaksi:
    print(f"{interaksi['zat_aktif_1']} + {interaksi['zat_aktif_2']} --> Level {interaksi['level']}")


# Simpan ke CSV
pd.DataFrame(hasil_interaksi).to_csv("hasil_interaksi_output.csv", index=False)

end = time.time()
exec_time = end - start_time
duration = round(exec_time, 2)

print(f"\nWaktu eksekusi: {duration} detik")

# Tulis log ke file CSV
with open("benchmark_log.csv", "a", newline="") as f:
    writer = csv.writer(f)
    if os.stat("benchmark_log.csv").st_size == 0:  # hanya tulis header jika file kosong
        writer.writerow(["script", "duration_seconds"])
    writer.writerow(["python", duration])