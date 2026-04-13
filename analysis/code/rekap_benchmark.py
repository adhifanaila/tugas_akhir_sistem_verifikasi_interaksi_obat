import json
import os
from tabulate import tabulate
import pandas as pd

log_dir = "logs"
files = [f for f in os.listdir(log_dir) if f.endswith(".json")]

rekap_data = []
for file in files:
    with open(os.path.join(log_dir, file), "r") as f:
        data = json.load(f)
        rekap_data.append([
            data.get("script", file),
            data.get("jumlah_interaksi", "-"),
            round(data.get("waktu_eksekusi", 0), 3),
            round(data.get("heap_memory_mb", 0), 2),
            round(data.get("rss_memory_mb", 0), 2)
        ])

# Print ke terminal
headers = ["Script", "Total Interaksi", "Durasi (s)", "Heap Memori (MB)", "RSS Memori (MB)"]
print("\n=== RINGKASAN BENCHMARK INTERAKSI OBAT ===\n")
print(tabulate(rekap_data, headers=headers, tablefmt="grid"))

# Simpan ke file Excel
df = pd.DataFrame(rekap_data, columns=headers)
output_excel = "rekap_benchmark_interaksi.xlsx"
df.to_excel(output_excel, index=False)

print(f"\n Rekap benchmark berhasil disimpan ke file: {output_excel}")
