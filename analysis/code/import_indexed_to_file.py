# === Tahap 1: Buat index file per zat aktif ===
import os
import pandas as pd
import json
import re

# Fungsi untuk membuat nama file yang aman
def sanitize_filename(name):
    return re.sub(r'[\\/*?:"<>|]', "_", name)  # Ganti karakter ilegal dengan underscore (_)

# Baca file CSV interaksi
df = pd.read_csv("data_interaksi.csv")
df = df.apply(lambda x: x.str.strip().str.lower())

# Buat folder penyimpanan index
index_folder = "index"
os.makedirs(index_folder, exist_ok=True)

# Peta index: zat aktif -> daftar interaksi
index_map = {}

# Iterasi data dan isi index_map secara simetris (a->b dan b->a)
for _, row in df.iterrows():
    a, b, level = row['zat_aktif_1'], row['zat_aktif_2'], row['level']

    for source, target in [(a, b), (b, a)]:
        if source not in index_map:
            index_map[source] = []
        index_map[source].append({
            "target": target,
            "level": level
        })

# Simpan setiap zat aktif sebagai file JSON terpisah
for source_zat, interactions in index_map.items():
    filename = sanitize_filename(source_zat) + ".json"
    filepath = os.path.join(index_folder, filename)
    with open(filepath, "w", encoding="utf-8") as f:
        json.dump(interactions, f, indent=2, ensure_ascii=False)

print("Index file berhasil dibuat di folder 'index/'")
